<?php
use Symfony\Component\HttpFoundation\Request;
use Ethna_ContainerInterface as ContainerInterface;
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
    public function __construct(string $appId, Ethna_Logger $logger, string $default_form_class, string  $actionDir)
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
            throw new \Exception("undefined action [$action_name]");
        }
        return $action_name;
    }

    public function getController(Request $request, $action_name, ContainerInterface $container, $void, $viewResolver): callable
    {

        list($action_class_name ,$void ,$method) = $this->getClassNames($action_name);
        if ($action_class_name == null) {
            throw new \Exception('action class not found');
        }


        if ($method === null) {
            $method = 'run';
        }

        // アクションフォーム初期化
        // フォーム定義、フォーム値設定
        /** @var Ethna_ActionClass $ac */
        $ac = new $action_class_name($container, null, $viewResolver);
        $form_class_name = $this->getActionFormName($action_name);

        // form定義を外から注入
        if (! empty($ac->form)) {
            $form_injection = $ac->form;
        } else {
            $form_injection = null;
        }

        $action_form =  new $form_class_name($container, $form_injection);
        $ac->setActionForm($action_form);
        return [$ac, $method];
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
     * @param  string $key アクション名
     * @return array  クラス名
     */
    protected function getClassNames($key)
    {

        // アクションスクリプトのインクルード
        // "foo_bar" -> "/Foo/Bar.php"となる
        $postfix1 = preg_replace_callback('/_(.)/', function (array $matches) {
                return '/' . strtoupper($matches[1]);
            }, ucfirst($key));
        $class_path = $postfix1 . '.php';
        $this->logger->log(LOG_DEBUG, "default action path [%s]", $class_path);
        if (file_exists($this->actionDir . $class_path)) {
            include_once $this->actionDir . $class_path;
        } else {
            $this->logger->log(LOG_INFO, 'file not found:' . $this->actionDir . $class_path);
        }

        $postfix = preg_replace_callback('/_(.)/', function (array $matches) {
            return strtoupper($matches[1]);
        }, ucfirst($key));
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
     */
    public function getActionFormName($action_name): string
    {
        list(, $form_class_name,) = $this->getClassNames($action_name);
        return $form_class_name;
    }


}