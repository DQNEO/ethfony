<?php
/**
 *  Kernel.php
 *
 *  @author     Masaki Fujimoto <fujimoto@php.net>
 *  @license    http://www.opensource.org/licenses/bsd-license.php The BSD License
 *  @package    Ethna
 *  @version    $Id$
 */

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\TerminableInterface;


/**
 *  コントローラクラス
 *
 *  @author     Masaki Fujimoto <fujimoto@php.net>
 *  @access     public
 *  @package    Ethna
 */
class Ethna_Kernel implements HttpKernelInterface, TerminableInterface
{
    protected $default_action_name;

    /** @var    string      アプリケーションID */
    protected $appid = 'ETHNA';

    /** @protected    array       アプリケーションディレクトリ */
    protected $directory = array();

    /** @var    array       クラス設定 */
    protected $class = array();

    /**
     * @protected    string ロケール名(e.x ja_JP, en_US 等),
     *                  (ロケール名は ll_cc の形式。ll = 言語コード cc = 国コード)
     *
     *  @see http://www.gnu.org/software/gettext/manual/html_node/Locale-Names.html
     */
    protected $locale = 'ja_JP';

    /** @var  Ethna_Container */
    protected $container;

    protected $sessionName = 'EthnaSESSID';

    /**
     *  Ethna_Kernelクラスのコンストラクタ
     *
     *  @param  string   $action_name    指定のアクション名(省略可)
     */
    public function __construct(string $default_action_name = '')
    {
        $this->default_action_name = $default_action_name;


        $this->container = new Ethna_Container(BASE, $this->directory, $this->class, $this->appid, $this->locale, $this->sessionName);

        $actionResolverClass = $this->class['action_resolver'];
        /** @var Ethna_ActionResolver $actionResolver */
        $this->actionResolver = new $actionResolverClass($this->container->getAppId(), $this->container->getLogger(), $this->class['form'], $this->container->getDirectory('action') . "/");

        Ethna::setErrorCallback(array($this, 'handleError'));
    }


    /**
     *  フレームワークの処理を実行する(CLI)
     *
     *  @access private
     *  @param  mixed   $default_action_name    指定のアクション名
     */
    public function console($action_name)
    {
        $config = $this->container->getConfig();

        $plugin = $this->container->getPlugin();
        $logger = $this->container->getLogger();
        $logger->begin();
        $plugin->setLogger($logger);

        $this->container->setCurrentActionName($action_name);

        $i18n = $this->container->getI18N();
        $i18n->setLanguage($this->locale);

        $form_class_name = $this->class['form'];
        $action_form = new $form_class_name($this->container);
        $this->container->setActionForm($action_form);
        $command_class = sprintf("%s_Command_%s", ucfirst(strtolower($this->appid)), ucfirst($action_name));
        require_once $this->container->getDirectory('command') . '/' . ucfirst($action_name) . '.php';
        $ac = new $command_class($this->container);

        $ac->runcli();
    }

    /**
     *  フレームワークの処理を実行する(WWW)
     *
     *  引数$default_action_nameに配列が指定された場合、その配列で指定された
     *  アクション以外は受け付けない(指定されていないアクションが指定された
     *  場合、配列の先頭で指定されたアクションが実行される)
     *
     */
    public function handle(Request $request, $type = self::MASTER_REQUEST, $catch = true): Response
    {
        $config = $this->container->getConfig();
        $plugin = $this->container->getPlugin();

        $logger = $this->container->getLogger();
        $plugin->setLogger($logger);

        $logger->begin();
        // アクション名の取得
        $action_name = $this->actionResolver->resolveActionName($request, $this->default_action_name);

        $this->container->getSession()->restore();

        $i18n = $this->container->getI18N();
        $i18n->setLanguage($this->locale);

        $this->container->setActionResolver($this->actionResolver);
        $this->container->setCurrentActionName($action_name);

        $callable = $this->actionResolver->getController($request, $action_name, $this->container);
        return $callable($request);
    }

    /**
     *  アプリケーション実行後の後始末を行います。
     */
    public function terminate(Request $request, Response $response)
    {
        $this->container->getLogger()->end();
    }

    /**
     *  エラーハンドラ
     *
     *  @access public
     *  @param  object  Ethna_Error     エラーオブジェクト
     */
    public function handleError($error)
    {
        $logger = $this->container->getLogger();
        // ログ出力
        list ($log_level, $dummy) = $logger->errorLevelToLogLevel($error->getLevel());
        $message = $error->getMessage();
        $logger->log($log_level, sprintf("%s [ERROR CODE(%d)]", $message, $error->getCode()));
    }


}
