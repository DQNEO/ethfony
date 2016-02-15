<?php

/**
 * Created by PhpStorm.
 * User: DQNEO
 * Date: 2016/02/07
 * Time: 14:17
 */
class Ethna_ActionResolver
{
    private $httpVars;
    private $appId;
    private $logger;
    private $default_form_class;
    private $actionDir;

    /**
     * Ethna_ActionResolver constructor.
     */
    public function __construct(array $httpVars, $appId, $logger, $default_form_class, $actionDir)
    {
        $this->httpVars = $httpVars;
        $this->appId = $appId;
        $this->logger = $logger;
        $this->default_form_class = $default_form_class;
        $this->actionDir = $actionDir;
    }

    public function resolveActionName(string $default_action_name)
    {
        $action_name = $this->_getActionName($default_action_name);
        list($action_class_name,) = $this->getClassNames($action_name);
        if (is_null($action_class_name)) {
            $this->logger->end();
            $r = Ethna::raiseError("undefined action [%s]", E_APP_UNDEFINED_ACTION, $action_name);
            throw new \Exception($r->getMessage());
        }
        return $action_name;
    }

    public function newAction($action_name, $backend)
    {
        $action_class_name = $this->getActionClassName($action_name);
        return new $action_class_name($backend);
    }

    public function newActionForm($action_name, $ctl)
    {
        $form_class_name = $this->getActionFormName($action_name);
        return new $form_class_name($ctl);

    }

    /**
     *  getDefaultActionClass()で取得したクラス名からアクション名を取得する
     *
     *  getDefaultActionClass()をオーバーライドした場合、こちらも合わせてオーバーライド
     *  することを推奨(必須ではない)
     *
     * @access public
     * @param  string $class_name アクションクラス名
     * @return string  アクション名
     */
    protected function actionClassToName($class_name)
    {
        $prefix = sprintf("%s_Action_", $this->appId());
        if (preg_match("/$prefix(.*)/", $class_name, $match) == 0) {
            // 不明なクラス名
            return null;
        }
        $target = $match[1];

        $action_name = substr(preg_replace('/([A-Z])/e', "'_' . strtolower('\$1')", $target), 1);

        return $action_name;
    }

    /**
     *  フォームにより要求されたアクション名に対応する定義を返す
     *
     * @param  string $action_name アクション名
     * @return array   アクション定義
     */
    protected function getClassNames($action_name)
    {

        // アクションスクリプトのインクルード
        // "foo_bar" -> "/Foo/Bar.php"となる
        $postfix1 = preg_replace_callback('/_(.)/', function (array $matches) {
                return '/' . strtoupper($matches[1]);
            }, ucfirst($action_name));
        $class_path = $postfix1 . '.php';
        $this->logger->log(LOG_DEBUG, "default action path [%s]", $class_path);
        if (file_exists($this->actionDir . $class_path)) {
            include_once $this->actionDir . $class_path;
        } else {
            $this->logger->log(LOG_INFO, 'file not found:' . $this->actionDir . $class_path);
        }

        $postfix = preg_replace_callback('/_(.)/', function (array $matches) {
            return strtoupper($matches[1]);
        }, ucfirst($action_name));
        $action_class_name = sprintf("%s_Action_%s", $this->appId, $postfix);
        $this->logger->log(LOG_DEBUG, "default action class [%s]", $action_class_name);
        if (class_exists($action_class_name) == false) {
            $this->logger->log(LOG_NOTICE, 'action class is not defined [%s]', $action_class_name);
            return [null, null];
        }

        $form_class_name = sprintf("%s_Form_%s", $this->appId, $postfix);
        $this->logger->log(LOG_DEBUG, "default form class [%s]", $form_class_name);
        if (class_exists($form_class_name) == false) {
            // 当該フォームクラスが存在しなければ基底フォームクラスを使う
            $this->logger->log(LOG_DEBUG, 'form class is not defined [%s] -> falling back to default [%s]', $form_class_name, $this->default_form_class);
            $form_class_name = $this->default_form_class;
        }

        return [$action_class_name, $form_class_name];
    }

    /**
     *  指定されたアクションのフォームクラス名を返す(オブジェクトの生成は行わない)
     *
     * @access public
     * @param  string $action_name アクション名
     * @return string  アクションのフォームクラス名
     */
    public function getActionFormName($action_name)
    {
        list(, $form_class_name) = $this->getClassNames($action_name);
        if (is_null($form_class_name)) {
            return null;
        }

        return $form_class_name;
    }

    /**
     *  指定されたアクションのクラス名を返す(オブジェクトの生成は行わない)
     *
     * @access public
     * @param  string $action_name アクションの名称
     * @return string  アクションのクラス名
     */
    public function getActionClassName($action_name)
    {
        list($action_class_name,) = $this->getClassNames($action_name);
        if ($action_class_name == null) {
            return null;
        }

        return $action_class_name;
    }

    /**
     *  フォームにより要求されたアクション名を返す
     *
     *  アプリケーションの性質に応じてこのメソッドをオーバーライドして下さい。
     *  デフォルトでは"action_"で始まるフォーム値の"action_"の部分を除いたもの
     *  ("action_sample"なら"sample")がアクション名として扱われます
     *
     * @access protected
     * @return string  フォームにより要求されたアクション名
     */
    protected function _getActionName_Form()
    {
        $http_vars = $this->httpVars;
        // フォーム値からリクエストされたアクション名を取得する
        $action_name = $sub_action_name = null;
        foreach ($http_vars as $name => $value) {
            if ($value == "" || strncmp($name, 'action_', 7) != 0) {
                continue;
            }

            $tmp = substr($name, 7);

            // type="image"対応
            if (preg_match('/_x$/', $name) || preg_match('/_y$/', $name)) {
                $tmp = substr($tmp, 0, strlen($tmp) - 2);
            }

            // value="dummy"となっているものは優先度を下げる
            if ($value == "dummy") {
                $sub_action_name = $tmp;
            } else {
                $action_name = $tmp;
            }
        }
        if ($action_name == null) {
            $action_name = $sub_action_name;
        }

        return $action_name;
    }


    /**
     *  実行するアクション名を返す
     *
     * @access protected
     * @param  mixed $default_action_name 指定のアクション名
     * @return string  実行するアクション名
     */
    protected function _getActionName(string $default_action_name)
    {
        // フォームから要求されたアクション名を取得する
        $form_action_name = $this->_getActionName_Form();
        $form_action_name = preg_replace('/[^a-z0-9\-_]+/i', '', $form_action_name);
        $this->logger->log(LOG_DEBUG, 'form_action_name[%s]', $form_action_name);

        // フォームからの指定が無い場合はエントリポイントに指定されたデフォルト値を利用する
        if ($form_action_name == "" && $default_action_name) {
            $this->logger->log(LOG_DEBUG, '-> default_action_name[%s]', $default_action_name);
            return $default_action_name;
        } else {
            $this->logger->log(LOG_DEBUG, '<<< action_name[%s] >>>', $form_action_name);
            return $form_action_name;
        }

    }


}