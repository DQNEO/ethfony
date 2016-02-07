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
    public $controller;

    /**
     *  Ethna_Backendクラスのコンストラクタ
     *
     *  @access public
     *  @param  object  Ethna_Controller    $controller    コントローラオブジェクト
     */
    public function __construct($controller)
    {
        $this->controller = $controller;
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
        return $this->controller->getI18N();
    }

    /**
     *  アクションエラーオブジェクトのアクセサ(R)
     *
     *  @access public
     *  @return object  Ethna_ActionError   アクションエラーオブジェクト
     */
    public function getActionError()
    {
        return $this->controller->getActionError();
    }

    /**
     *  アクションフォームオブジェクトのアクセサ(R)
     *
     *  @access public
     *  @return object  Ethna_ActionForm    アクションフォームオブジェクト
     */
    public function getActionForm()
    {
        return  $this->controller->getActionForm();
    }

    /**
     *  ログオブジェクトのアクセサ(R)
     *
     *  @access public
     *  @return object  Ethna_Logger    ログオブジェクト
     */
    public function getLogger()
    {
        return $this->controller->getLogger();
    }

    /**
     *  セッションオブジェクトのアクセサ(R)
     *
     *  @access public
     *  @return object  Ethna_Session   セッションオブジェクト
     */
    public function getSession()
    {
        return $this->controller->getSession();
    }

    /**
     *  プラグインオブジェクトのアクセサ(R)
     *
     *  @access public
     *  @return object  Ethna_Plugin    プラグインオブジェクト
     */
    public function getPlugin()
    {
        return $this->controller->getPlugin();
    }

    /**
     *  マネージャオブジェクトへのアクセサ(R)
     *
     *  @access public
     *  @return object  Ethna_AppManager    マネージャオブジェクト
     */
    public function getManager($type)
    {
        return $this->controller->getManager($type);
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
        return $this->controller->getEtcdir();
    }

    /**
     *  アプリケーションのテンポラリディレクトリを取得する
     *
     *  @access public
     *  @return string  テンポラリディレクトリのパス名
     */
    public function getTmpdir()
    {
        return $this->controller->getTmpdir();
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
        $this->controller->log($level, $message);
    }

}
