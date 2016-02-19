<?php
/**
 *  command
 *
 */
abstract class Ethna_Command
{
    /** @protected    object  Ethna_Controller       backendオブジェクト */
    protected $controller;

    /** @protected    object  Ethna_Config        設定オブジェクト    */
    protected $config;

    /** @protected    object  Ethna_I18N          i18nオブジェクト */
    protected $i18n;

    /** @public    object  Ethna_Plugin        プラグインオブジェクト */
    public $plugin;

    /** @protected    object  Ethna_Logger    ログオブジェクト */
    protected $logger;

    /**#@-*/

    /**
     *
     */
    public function __construct($controller)
    {
        $this->controller = $controller;
        $this->config = $controller->getConfig();
        $this->i18n = $controller->getI18N();
        $this->plugin = $controller->getPlugin();
        $this->logger = $controller->getLogger();
    }

    /**
     *  実行
     *
     *  @access public
     */
    abstract public function runcli();

}
