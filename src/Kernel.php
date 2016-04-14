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

    protected $encoding = 'UTF-8';

    /** @protected    object  Ethna_Logger        ログオブジェクト */
    protected $logger = null;

    /** @var  Ethna_Container */
    protected $container;

    protected $sessionName = 'EthnaSESSID';

    /**
     *  アプリケーションのエントリポイント
     *
     *  @access public
     *  @param  string  $class_name     アプリケーションコントローラのクラス名
     *  @param  mixed   $action_name    指定のアクション名(省略可)
     *  @static
     */
    public static function main(string $class_name, string $default_action_name = "")
    {
        /** @var Ethna_Kernel $kernel */
        $kernel = new $class_name($default_action_name);
        $request = Request::createFromGlobals();
        $response = $kernel->handle($request);
        $response->send();
        $kernel->terminate($request, $response);

    }


    /**
     *  Ethna_Kernelクラスのコンストラクタ
     *
     *  @access     public
     */
    public function __construct(string $default_action_name = '')
    {
        $this->default_action_name = $default_action_name;
    }


    /**
     *  (現在アクティブな)コントローラのインスタンスを返す
     *
     *  @access public
     *  @return object  Ethna_Kernel    コントローラのインスタンス
     *  @static
     */
    public static function getInstance()
    {
        if (isset($GLOBALS['_Ethna_controller'])) {
            return $GLOBALS['_Ethna_controller'];
        } else {
            return null;
        }
    }

    /**
     *  エンコーディング名へのアクセサ(R)
     *
     *  @access public
     *  @return string  $encoding クライアントエンコーディング名
     */
    public function getEncoding()
    {
        return $this->encoding;
    }

    /**
     *  フレームワークの処理を実行する(CLI)
     *
     *  @access private
     *  @param  mixed   $default_action_name    指定のアクション名
     */
    public function console($action_name)
    {
        $GLOBALS['_Ethna_controller'] = $this;

        Ethna::setErrorCallback(array($this, 'handleError'));

        $this->container = new Ethna_Container(BASE, $this->directory, $this->class, $this->appid, $this->locale, '');
        $this->directory = $this->container->getDirectories();
        $config = $this->container->getConfig();
        $this->container->url = $config->get('url');

        $plugin = $this->container->getPlugin();
        $this->logger = $this->container->getLogger();
        $plugin->setLogger($this->logger);
        $this->logger->begin();

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
        $default_action_name = $this->default_action_name;
        $GLOBALS['_Ethna_controller'] = $this;

        Ethna::setErrorCallback(array($this, 'handleError'));

        $this->container = new Ethna_Container(BASE, $this->directory, $this->class, $this->appid, $this->locale, $this->sessionName);
        $this->directory = $this->container->getDirectories();

        $config = $this->container->getConfig();
        $url = $config->get('url');
        if (empty($url)) {
            $url = Ethna_Util::getUrlFromRequestUri();
            $config->set('url', $url);
        }
        $this->container->url = $url;

        $plugin = $this->container->getPlugin();

        $this->logger = $this->container->getLogger();
        $plugin->setLogger($this->logger);
        $this->logger->begin();

        $actionDir = $this->directory['action'] . "/";
        $default_form_class = $this->class['form'];
        $actionResolverClass = $this->class['action_resolver'];
        /** @var Ethna_ActionResolver $actionResolver */
        $actionResolver = new $actionResolverClass($this->container->getAppId(), $this->logger, $default_form_class, $actionDir);
        $this->container->setActionResolver($actionResolver);
        // アクション名の取得
        $action_name = $actionResolver->resolveActionName($request, $default_action_name);
        $this->container->setCurrentActionName($action_name);

        $this->container->getSession()->restore();

        $i18n = $this->container->getI18N();
        $i18n->setLanguage($this->locale);

        // アクションフォーム初期化
        // フォーム定義、フォーム値設定
        $action_form = $actionResolver->newActionForm($action_name, $this->container);
        $this->container->setActionForm($action_form);

        $viewResolver = new Ethna_ViewResolver($this->container, $this->logger, $this->container->getViewdir(), $this->container->getAppId(), $this->class['view']);
        $callable = $actionResolver->getController($request, $action_name, $this->container, $action_form, $viewResolver);
        $arguments = [$request];
        $response = call_user_func_array($callable, $arguments);
        return $response;
    }

    /**
     *  アプリケーション実行後の後始末を行います。
     */
    public function terminate(Request $request, Response $response)
    {
        $this->logger->end();
    }

    /**
     *  エラーハンドラ
     *
     *  @access public
     *  @param  object  Ethna_Error     エラーオブジェクト
     */
    public function handleError($error)
    {
        // ログ出力
        list ($log_level, $dummy) = $this->logger->errorLevelToLogLevel($error->getLevel());
        $message = $error->getMessage();
        $this->logger->log($log_level, sprintf("%s [ERROR CODE(%d)]", $message, $error->getCode()));
    }


    /**
     *  アクション名を指定するクエリ/HTMLを生成する
     *
     *  @access public
     *  @param  string  $action action to request
     *  @param  string  $type   hidden, url...
     */
    public function getActionRequest($action, $type = "hidden")
    {
        $s = null;
        if ($type == "hidden") {
            $s = sprintf('<input type="hidden" name="action_%s" value="true" />', htmlspecialchars($action, ENT_QUOTES, mb_internal_encoding()));
        } else if ($type == "url") {
            $s = sprintf('action_%s=true', urlencode($action));
        }
        return $s;
    }


}
