<?php
use Symfony\Component\HttpFoundation\Request;

/**
 * Created by PhpStorm.
 * User: DQNEO
 * Date: 2016/02/07
 * Time: 14:17
 */
class Ethna_ActionResolver
{
    protected $appId;
    protected $logger;
    protected $default_form_class;
    protected $actionDir;

    /**
     * Ethna_ActionResolver constructor.
     */
    public function __construct($appId, $logger, $default_form_class, $actionDir)
    {
        $this->appId = $appId;
        $this->logger = $logger;
        $this->default_form_class = $default_form_class;
        $this->actionDir = $actionDir;
    }

    public function resolveActionName(Request $request, string $default_action_name)
    {
        $action_name = (new Ethna_RequestWrapper($request))->getActionName($default_action_name);

        list($action_class_name,,) = $this->getClassNames($action_name);
        if (is_null($action_class_name)) {
            $this->logger->end();
            $r = Ethna::raiseError("undefined action [%s]", E_APP_UNDEFINED_ACTION, $action_name);
            throw new \Exception($r->getMessage());
        }
        return $action_name;
    }

    public function getController(Request $request, $action_name, $backend, $action_form, $viewResolver): callable
    {
        list($action_class_name ,$void ,$method) = $this->getClassNames($action_name);
        if ($action_class_name == null) {
            throw new \Exception('action class not found');
        }


        if ($method === null) {
            $method = 'run';
        }

        $ac = new $action_class_name($backend, $action_form, $viewResolver);

        return [$ac, $method];
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
     * @return array  クラス名
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
        // actionクラスが存在しなければ探索中止
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

        return [$action_class_name, $form_class_name, null];
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
        list(, $form_class_name,) = $this->getClassNames($action_name);
        if (is_null($form_class_name)) {
            return null;
        }

        return $form_class_name;
    }


}