<?php
use Ethna_ContainerInterface as ContainerInterface;

/**
 *  command
 *
 */
abstract class Ethna_Command
{
    /** @protected    object  Ethna_Container       */
    protected $container;

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
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->config = $container->getConfig();
        $this->i18n = $container->getI18N();
        $this->plugin = $container->getPlugin();
        $this->logger = $container->getLogger();
    }

    /**
     *  実行
     *
     *  @access public
     */
    abstract public function runcli();

}
