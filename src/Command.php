<?php
/**
 *  command
 *
 */
abstract class Ethna_Command
{
    /** @protected    object  Ethna_Backend       backendオブジェクト */
    protected $backend;
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
    public function __construct($backend)
    {
        $this->controller = $controller = $backend->getController();
        $this->backend = $backend;
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
