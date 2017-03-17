<?php
// vim: foldmethod=marker
/**
 *  ActionClass.php
 *
 *  @author     Masaki Fujimoto <fujimoto@php.net>
 *  @license    http://www.opensource.org/licenses/bsd-license.php The BSD License
 *  @package    Ethna
 *  @version    $Id$
 */

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Ethna_ContainerInterface as ContainerInterface;

// {{{ Ethna_ActionClass

class ActionAbortedException extends \RuntimeException
{

}

/**
 *  action実行クラス
 *
 *  @author     Masaki Fujimoto <fujimoto@php.net>
 *  @access     public
 *  @package    Ethna
 */
class Ethna_ActionClass
{
    /**#@+
     *  @access private
     */

    protected $container;

    /** @protected    object  Ethna_Config        設定オブジェクト    */
    protected $config;

    /** @protected    object  Ethna_I18N          i18nオブジェクト */
    protected $i18n;

    /** @protected    object  Ethna_ActionError   アクションエラーオブジェクト */
    protected $action_error;

    /** @protected    object  Ethna_ActionError   アクションエラーオブジェクト(省略形) */
    protected $ae;

    /** @var Ethna_ActionForm    アクションフォームオブジェクト */
    protected $action_form;

    /** @var Ethna_ActionForm    アクションフォームオブジェクト */
    protected $af;

    /** @protected    object  Ethna_Session       セッションオブジェクト */
    protected $session;

    /** @public    object  Ethna_Plugin        プラグインオブジェクト */
    public $plugin;

    /** @protected    object  Ethna_Logger    ログオブジェクト */
    protected $logger;

    /** @var Ethna_AppDataContainer */
    protected $dataContainer;

    protected $viewClassName;
    /**
     *  Ethna_ActionClassのコンストラクタ
     *
     */
    public function __construct(ContainerInterface $container, $viewClassName)
    {
        $this->container = $container;
        $this->dataContainer = $container->getDataContainer();
        $this->config = $container->getConfig();
        $this->i18n = $container->getI18N();

        $this->action_error = $container->getActionError();
        $this->ae = $this->action_error;

        $this->session = $container->getSession();
        $this->plugin = $container->getPlugin();
        $this->logger = $container->getLogger();
        $this->viewClassName = $viewClassName;
    }


    /**
     *  現在実行中のアクション名
     *
     *  @return string actionname
     */
    public function getActionName()
    {
        return $this->container->getCurrentActionName();
    }


    public function setActionForm(Ethna_ActionForm $action_form)
    {
        $this->action_form = $action_form;
        $this->af = $this->action_form;
    }

    /**
     *  アクション実行前の認証処理を行う
     *
     *  @access public
     *  @return string  遷移名(nullなら正常終了, falseなら処理終了)
     */
    public function authenticate()
    {
        return null;
    }

    /**
     *  アクション実行前の処理(フォーム値チェック等)を行う
     *
     *  @access public
     *  @return string  遷移名(nullなら正常終了, falseなら処理終了)
     */
    public function prepare()
    {
        return null;
    }

    /**
     *  アクション実行
     *
     *  @access public
     *  @return string  遷移名(nullなら遷移は行わない)
     */
    public function perform()
    {
        return null;
    }

    public function run(Request $request): Response
    {
        if ($this->af) {
            $this->af->setFormDef_PreHelper();
            $this->af->setFormVars($request);
        }


        $forward_name = $this->authenticate();
        if ($forward_name === false) {
            throw new ActionAbortedException();
        } else if ($forward_name !== null) {
            //Redirect Resposne or Ethna_ViewClass
            return $forward_name;
        }

        $forward_name = $this->prepare();
        if ($forward_name === false) {
            throw new ActionAbortedException();
        } else if ($forward_name !== null) {
            return $forward_name;
        }

        $forward_name = $this->perform();
        if ($forward_name === false) {
            throw new ActionAbortedException();
        } else if ($forward_name === null) {
            throw new ActionAbortedException();
        }


        return $forward_name;
    }

    /**
     */
    protected function view(string $forward_name, array $parameters = []):Response
    {
        $className = $this->viewClassName;
        $view = new $className($this->container, $this->af, $forward_name);
        return $view->render($parameters);
    }


}
// }}}
