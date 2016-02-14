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
    private $class_factory;
    private $gatewayPrefix;
    private $actionDir;

    /**
     * Ethna_ActionResolver constructor.
     */
    public function __construct(array $httpVars, $appId, $logger, $class_factory, $gatewayPrefix, $actionDir)
    {
        $this->httpVars = $httpVars;
        $this->appId = $appId;
        $this->logger = $logger;
        $this->class_factory = $class_factory;
        $this->gatewayPrefix = $gatewayPrefix;
        $this->actionDir = $actionDir;
    }

    public function resolveActionName($default_action_name, $fallback_action_name)
    {
        $action_name = $this->_getActionName($default_action_name, $fallback_action_name);
        // アクション定義の取得
        $action_obj = $this->_getAction($action_name);
        if (is_null($action_obj)) {
            if ($fallback_action_name != "") {
                $this->logger->log(LOG_DEBUG, 'undefined action [%s] -> try fallback action [%s]', $action_name, $fallback_action_name);
                $action_obj = $this->_getAction($fallback_action_name);
            }

            if (is_null($action_obj)) {
                $this->logger->end();
                $r = Ethna::raiseError("undefined action [%s]", E_APP_UNDEFINED_ACTION, $action_name);
                throw new \Exception($r->getMessage());

            } else {
                $action_name = $fallback_action_name;
            }
        }
        unset($action_obj);
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
        return  new $form_class_name($ctl);

    }

    /**
     *  アクションに対応するアクションパス名が省略された場合のデフォルトパス名を返す
     *
     *  デフォルトでは"foo_bar" -> "/Foo/Bar.php"となるので好み応じてオーバーライドする
     *
     *  @access public
     *  @param  string  $action_name    アクション名
     *  @return string  アクションクラスが定義されるスクリプトのパス名
     */
    protected function getDefaultActionPath($action_name)
    {
        $r = preg_replace_callback('/_(.)/', function(array $matches){return '/' . strtoupper($matches[1]);}, ucfirst($action_name)) . '.php';
        $this->logger->log(LOG_DEBUG, "default action path [%s]", $r);

        return $r;
    }

    /**
     *  アクションに対応するフォームパス名が省略された場合のデフォルトパス名を返す
     *
     *  デフォルトでは_getDefaultActionPath()と同じ結果を返す(1ファイルに
     *  アクションクラスとフォームクラスが記述される)ので、好みに応じて
     *  オーバーライドする
     *
     *  @access public
     *  @param  string  $action_name    アクション名
     *  @return string  form classが定義されるスクリプトのパス名
     */
    protected function getDefaultFormPath($action_name)
    {
        return $this->getDefaultActionPath($action_name);
    }

    /**
     *  アクションに対応するアクションクラス名が省略された場合のデフォルトクラス名を返す
     *
     *  デフォルトでは[プロジェクトID]_Action_[アクション名]となるので好み応じてオーバライドする
     *
     *  @access public
     *  @param  string  $action_name    アクション名
     *  @return string  アクションクラス名
     */
    protected function getDefaultActionClass($action_name)
    {
        $gateway_prefix  = $this->gatewayPrefix;

        $postfix = preg_replace_callback('/_(.)/', function(array $matches){return strtoupper($matches[1]);}, ucfirst($action_name));
        $r = sprintf("%s_%sAction_%s", $this->appId, $gateway_prefix ? $gateway_prefix . "_" : "", $postfix);
        $this->logger->log(LOG_DEBUG, "default action class [%s]", $r);

        return $r;
    }

    /**
     *  getDefaultActionClass()で取得したクラス名からアクション名を取得する
     *
     *  getDefaultActionClass()をオーバーライドした場合、こちらも合わせてオーバーライド
     *  することを推奨(必須ではない)
     *
     *  @access public
     *  @param  string  $class_name     アクションクラス名
     *  @return string  アクション名
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
     *  アクションに対応するフォームクラス名が省略された場合のデフォルトクラス名を返す
     *
     *  デフォルトでは[プロジェクトID]_Form_[アクション名]となるので好み応じてオーバライドする
     *
     *  @access public
     *  @param  string  $action_name    アクション名
     *  @return string  アクションフォーム名
     */
    protected function getDefaultFormClass($action_name)
    {
        $gateway_prefix = $this->gatewayPrefix;

        $postfix = preg_replace_callback('/_(.)/', function(array $matches){return strtoupper($matches[1]);}, ucfirst($action_name));
        $r = sprintf("%s_%sForm_%s", $this->appId, $gateway_prefix ? $gateway_prefix . "_" : "", $postfix);
        $this->logger->log(LOG_DEBUG, "default action class [%s]", $r);

        return $r;
    }

    /**
     *  アクションスクリプトをインクルードする
     *
     *  ただし、インクルードしたファイルにクラスが正しく定義されているかどうかは保証しない
     *
     *  @access private
     *  @param  string  $action_name    アクション名
     */
    protected function _includeActionScript($action_name)
    {
        $class_path = $form_path = null;

        $action_dir = $this->actionDir;

        $class_path = $this->getDefaultActionPath($action_name);
        if (file_exists($action_dir . $class_path)) {
            include_once $action_dir . $class_path;
        } else {
            $this->logger->log(LOG_INFO, 'file not found:'.$action_dir . $class_path);
            return;
        }

        $form_path = $this->getDefaultFormPath($action_name);
        if ($form_path == $class_path) {
            return;
        }
        if (file_exists($action_dir . $form_path)) {
            include_once $action_dir . $form_path;
        } else {
            $this->logger->log(LOG_DEBUG, 'default form file not found [%s] -> maybe falling back to default form class', $form_path);
        }
    }

    /**
     *  フォームにより要求されたアクション名に対応する定義を返す
     *
     *  @param  string  $action_name    アクション名
     *  @return array   アクション定義
     */
    protected function _getAction($action_name)
    {
        $action = array();
        $action_obj = array();

        // アクションスクリプトのインクルード
        $this->_includeActionScript($action_name);

        $action_obj['class_name'] = $this->getDefaultActionClass($action_name);
        $action_obj['form_name'] = $this->getDefaultFormClass($action_name);

        // 必要条件の確認
        if (class_exists($action_obj['class_name']) == false) {
            $this->logger->log(LOG_NOTICE, 'action class is not defined [%s]', $action_obj['class_name']);
            return null;
        }
        if (class_exists($action_obj['form_name']) == false) {
            // フォームクラスは未定義でも良い
            $class_name = $this->class_factory->getObjectName('form');
            $this->logger->log(LOG_DEBUG, 'form class is not defined [%s] -> falling back to default [%s]', $action_obj['form_name'], $class_name);
            $action_obj['form_name'] = $class_name;
        }

        return $action_obj;
    }

    /**
     *  指定されたアクションのフォームクラス名を返す(オブジェクトの生成は行わない)
     *
     *  @access public
     *  @param  string  $action_name    アクション名
     *  @return string  アクションのフォームクラス名
     */
    public function getActionFormName($action_name)
    {
        $action_obj = $this->_getAction($action_name);
        if (is_null($action_obj)) {
            return null;
        }

        return $action_obj['form_name'];
    }

    /**
     *  指定されたアクションのクラス名を返す(オブジェクトの生成は行わない)
     *
     *  @access public
     *  @param  string  $action_name    アクションの名称
     *  @return string  アクションのクラス名
     */
    protected function getActionClassName($action_name)
    {
        $action_obj = $this->_getAction($action_name);
        if ($action_obj == null) {
            return null;
        }

        return $action_obj['class_name'];
    }

    /**
     *  フォームにより要求されたアクション名を返す
     *
     *  アプリケーションの性質に応じてこのメソッドをオーバーライドして下さい。
     *  デフォルトでは"action_"で始まるフォーム値の"action_"の部分を除いたもの
     *  ("action_sample"なら"sample")がアクション名として扱われます
     *
     *  @access protected
     *  @return string  フォームにより要求されたアクション名
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
                $tmp = substr($tmp, 0, strlen($tmp)-2);
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
     *  @access protected
     *  @param  mixed   $default_action_name    指定のアクション名
     *  @return string  実行するアクション名
     */
    protected function _getActionName($default_action_name  )
    {
        // フォームから要求されたアクション名を取得する
        $form_action_name = $this->_getActionName_Form();
        $form_action_name = preg_replace('/[^a-z0-9\-_]+/i', '', $form_action_name);
        $this->logger->log(LOG_DEBUG, 'form_action_name[%s]', $form_action_name);

        // フォームからの指定が無い場合はエントリポイントに指定されたデフォルト値を利用する
        if ($form_action_name == "" && count($default_action_name) > 0) {
            $tmp = is_array($default_action_name) ? $default_action_name[0] : $default_action_name;
            if ($tmp{strlen($tmp)-1} == '*') {
                $tmp = substr($tmp, 0, -1);
            }
            $this->logger->log(LOG_DEBUG, '-> default_action_name[%s]', $tmp);
            $action_name = $tmp;
        } else {
            $action_name = $form_action_name;
        }

        $this->logger->log(LOG_DEBUG, '<<< action_name[%s] >>>', $action_name);

        return $action_name;
    }



}