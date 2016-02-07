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
use Symfony\Component\HttpFoundation\Response;
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

    /** @protected    object  Ethna_Backend       backendオブジェクト */
    protected $backend;
    protected $controller;

    /** @protected    object  Ethna_Config        設定オブジェクト    */
    protected $config;

    /** @protected    object  Ethna_I18N          i18nオブジェクト */
    protected $i18n;

    /** @protected    object  Ethna_ActionError   アクションエラーオブジェクト */
    protected $action_error;

    /** @protected    object  Ethna_ActionError   アクションエラーオブジェクト(省略形) */
    protected $ae;

    /** @protected    object  Ethna_ActionForm    アクションフォームオブジェクト */
    protected $action_form;

    /** @protected    object  Ethna_ActionForm    アクションフォームオブジェクト(省略形) */
    protected $af;

    /** @protected    object  Ethna_Session       セッションオブジェクト */
    protected $session;

    /** @public    object  Ethna_Plugin        プラグインオブジェクト */
    public $plugin;

    /** @protected    object  Ethna_Logger    ログオブジェクト */
    protected $logger;

    /**#@-*/

    /**
     *  Ethna_ActionClassのコンストラクタ
     *
     *  @access public
     *  @param  object  Ethna_Backend   $backend    backendオブジェクト
     */
    public function __construct($backend)
    {
        $this->controller = $controller = $backend->getController();
        $this->backend = $backend;
        $this->config = $controller->getConfig();
        $this->i18n = $controller->getI18N();

        $this->action_error = $controller->getActionError();
        $this->ae = $this->action_error;

        $this->action_form = $controller->getActionForm();
        $this->af = $this->action_form;

        $this->session = $controller->getSession();
        $this->plugin = $controller->getPlugin();
        $this->logger = $controller->getLogger();
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

    /** @var  Ethna_ViewResolver  */
    protected $viewResolver;

    public function run(Ethna_ViewResolver $viewResolver): Response
    {
        $this->viewResolver = $viewResolver;

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
}
// }}}
