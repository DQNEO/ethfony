<?php
/**
 *  AppManager.php
 *
 *  @author     Masaki Fujimoto <fujimoto@php.net>
 *  @license    http://www.opensource.org/licenses/bsd-license.php The BSD License
 *  @package    Ethna
 *  @version    $Id$
 */

// {{{ Ethna_AppManager
/**
 *  アプリケーションマネージャのベースクラス
 *
 *  @author     Masaki Fujimoto <fujimoto@php.net>
 *  @access     public
 *  @package    Ethna
 */
class Ethna_AppManager
{
    /**     object  Ethna_Backend       backendオブジェクト */
    public $backend;
    public $controller;

    /**     object  Ethna_Config        設定オブジェクト */
    public $config;

    /**     object  Ethna_I18N          i18nオブジェクト */
    public $i18n;

    /**     object  Ethna_ActionForm    アクションフォームオブジェクト */
    public $action_form;

    /**     object  Ethna_ActionForm    アクションフォームオブジェクト(省略形) */
    public $af;

    /**     object  Ethna_Session       セッションオブジェクト */
    public $session;

    /**#@-*/

    /**
     *  Ethna_AppManagerのコンストラクタ
     *
     *  @access public
     *  @param  object  Ethna_Backend   $backend   backendオブジェクト
     */
    public function __construct($backend)
    {
        // 基本オブジェクトの設定
        $this->backend = $backend;
        $this->controller = $controller = $backend->controller;
        $this->config = $controller->getConfig();
        $this->i18n = $controller->getI18N();
        $this->action_form = $controller->getActionForm();
        $this->session = $controller->getSession();

        $this->af = $this->action_form;
    }


}
