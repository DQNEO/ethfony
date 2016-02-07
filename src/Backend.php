<?php
// vim: foldmethod=marker
/**
 *  Backend.php
 *
 *  @author     Masaki Fujimoto <fujimoto@php.net>
 *  @license    http://www.opensource.org/licenses/bsd-license.php The BSD License
 *  @package    Ethna
 *  @version    $Id$
 */

// {{{ Ethna_Backend
/**
 *  バックエンド処理クラス
 *
 *  @author     Masaki Fujimoto <fujimoto@php.net>
 *  @access     public
 *  @package    Ethna
 */
class Ethna_Backend
{
    /** @protected    object  Ethna_ClassFactory  クラスファクトリオブジェクト */
    public $class_factory;

    /** @protected    object  Ethna_I18N          i18nオブジェクト */
    public $i18n;

    /** @protected    object  Ethna_ActionError   アクションエラーオブジェクト */
    public $action_error;

    /** @protected    object  Ethna_ActionError   アクションエラーオブジェクト($action_errorの省略形) */
    public $ae;

    /** @protected    object  Ethna_ActionForm    アクションフォームオブジェクト */
    public $action_form;

    /** @protected    object  Ethna_ActionForm    アクションフォームオブジェクト($action_formの省略形) */
    public $af;

    /** @protected    object  Ethna_ActionClass   アクションクラスオブジェクト */
    public $action_class;

    /** @protected    object  Ethna_ActionClass   アクションクラスオブジェクト($action_classの省略形) */
    public $ac;

    /** @protected    object  Ethna_Session       セッションオブジェクト */
    public $session;

    /** @protected    object  Ethna_Plugin        プラグインオブジェクト */
    public $plugin;

    /** @protected    object  Ethna_Logger        ログオブジェクト */
    public $logger;

    /**#@-*/


    /**
     *  Ethna_Backendクラスのコンストラクタ
     *
     *  @access public
     *  @param  object  Ethna_Controller    $controller    コントローラオブジェクト
     */
    public function __construct($controller)
    {
        // オブジェクトの設定
        $this->controller = $controller;
        $this->ctl = $this->controller;

        $this->class_factory = $controller->getClassFactory();

        $this->i18n = $controller->getI18N();

        $this->action_error = $controller->getActionError();
        $this->ae = $this->action_error;

        $this->session = $this->controller->getSession();
        $this->plugin = $this->controller->getPlugin();
        $this->logger = $this->controller->getLogger();
    }

    /**
     *  controllerオブジェクトへのアクセサ(R)
     *
     *  @access public
     *  @return object  Ethna_Controller    controllerオブジェクト
     */
    public function getController()
    {
        return $this->controller;
    }

    /**
     *  設定オブジェクトへのアクセサ(R)
     *
     *  @access public
     *  @return object  Ethna_Config        設定オブジェクト
     */
    public function getConfig()
    {
        return $this->controller->getConfig();
    }

    /**
     *  アプリケーションIDを返す
     *
     *  @access public
     *  @return string  アプリケーションID
     */
    public function getAppId()
    {
        return $this->controller->getAppId();
    }

    /**
     *  I18Nオブジェクトのアクセサ(R)
     *
     *  @access public
     *  @return object  Ethna_I18N  i18nオブジェクト
     */
    public function getI18N()
    {
        return $this->i18n;
    }

    /**
     *  アクションエラーオブジェクトのアクセサ(R)
     *
     *  @access public
     *  @return object  Ethna_ActionError   アクションエラーオブジェクト
     */
    public function getActionError()
    {
        return $this->action_error;
    }

    /**
     *  アクションフォームオブジェクトのアクセサ(R)
     *
     *  @access public
     *  @return object  Ethna_ActionForm    アクションフォームオブジェクト
     */
    public function getActionForm()
    {
        return $this->action_form;
    }

    /**
     *  アクションフォームオブジェクトのアクセサ(W)
     *
     *  @access public
     */
    public function setActionForm($action_form)
    {
        $this->action_form = $action_form;
        $this->af = $action_form;
    }

    /**
     *  実行中のアクションクラスオブジェクトのアクセサ(R)
     *
     *  @access public
     *  @return mixed   Ethna_ActionClass:アクションクラス null:アクションクラス未定
     */
    public function getActionClass()
    {
        return $this->action_class;
    }

    /**
     *  実行中のアクションクラスオブジェクトのアクセサ(W)
     *
     *  @access public
     */
    public function setActionClass($action_class)
    {
        $this->action_class = $action_class;
        $this->ac = $action_class;
    }

    /**
     *  ログオブジェクトのアクセサ(R)
     *
     *  @access public
     *  @return object  Ethna_Logger    ログオブジェクト
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     *  セッションオブジェクトのアクセサ(R)
     *
     *  @access public
     *  @return object  Ethna_Session   セッションオブジェクト
     */
    public function getSession()
    {
        return $this->session;
    }

    /**
     *  プラグインオブジェクトのアクセサ(R)
     *
     *  @access public
     *  @return object  Ethna_Plugin    プラグインオブジェクト
     */
    public function getPlugin()
    {
        return $this->plugin;
    }

    /**
     *  マネージャオブジェクトへのアクセサ(R)
     *
     *  @access public
     *  @return object  Ethna_AppManager    マネージャオブジェクト
     */
    public function getManager($type)
    {
        return $this->controller->getClassFactory()->getManager($type);
    }

    /**
     *  アプリケーションのベースディレクトリを取得する
     *
     *  @access public
     *  @return string  ベースディレクトリのパス名
     */
    public function getBasedir()
    {
        return $this->controller->getBasedir();
    }

    /**
     *  アプリケーションのテンプレートディレクトリを取得する
     *
     *  @access public
     *  @return string  テンプレートディレクトリのパス名
     */
    public function getTemplatedir()
    {
        return $this->controller->getTemplatedir();
    }

    /**
     *  アプリケーションの設定ディレクトリを取得する
     *
     *  @access public
     *  @return string  設定ディレクトリのパス名
     */
    public function getEtcdir()
    {
        return $this->controller->getDirectory('etc');
    }

    /**
     *  アプリケーションのテンポラリディレクトリを取得する
     *
     *  @access public
     *  @return string  テンポラリディレクトリのパス名
     */
    public function getTmpdir()
    {
        return $this->controller->getDirectory('tmp');
    }

    /**
     *  アプリケーションのテンプレートファイル拡張子を取得する
     *
     *  @access public
     *  @return string  テンプレートファイルの拡張子
     */
    public function getTemplateext()
    {
        return $this->controller->getExt('tpl');
    }

    /**
     *  ログを出力する
     *
     *  @access public
     *  @param  int     $level      ログレベル(LOG_DEBUG, LOG_NOTICE...)
     *  @param  string  $message    ログメッセージ(printf形式)
     */
    public function log($level, $message)
    {
        $args = func_get_args();
        if (count($args) > 2) {
            array_splice($args, 0, 2);
            $message = vsprintf($message, $args);
        }
        $this->controller->getLogger()->log($level, $message);
    }

}
