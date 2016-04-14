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

    /** @var    string      アプリケーションベースディレクトリ */
    protected $base = '';

    /** @protected    string      アプリケーションベースURL */
    protected $url = '';

    /** @protected    array       アプリケーションディレクトリ */
    protected $directory = array();

    /** @protected    array       クラス設定 */
    public $class = array();

    /**
     * @protected    string ロケール名(e.x ja_JP, en_US 等),
     *                  (ロケール名は ll_cc の形式。ll = 言語コード cc = 国コード)
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
     *  アプリケーション実行後の後始末を行います。
     */
    public function terminate(Request $request, Response $response)
    {
        $this->logger->end();
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
     *  アプリケーションIDを返す
     *
     *  @return string  アプリケーションID
     */
    protected function getAppId(): string
    {
        return ucfirst(strtolower($this->appid));
    }

    /**
     *  ビューディレクトリ名を決定する
     *
     *  @return string  ビューディレクトリ
     */
    protected function getViewdir()
    {
        return $this->directory['view'] . "/";
    }

    /**
     *  (action,view以外の)テストケースを置くディレクトリ名を決定する
     *
     *  @access public
     *  @return string  テストケースを置くディレクトリ
     */
    public function getTestdir()
    {
        return (empty($this->directory['test']) ? ($this->base . (empty($this->base) ? '' : '/')) : ($this->directory['test'] . "/"));
    }

    /**
     */
    public function getDirectory(string $key)
    {
        return $this->container->getDirectory($key);
    }

    public function getExt(string $key):string
    {
        return $this->container->getExt($key);
    }

    public function getActionError(): Ethna_ActionError
    {
        return $this->container->getActionError();
    }

    /**
     *  Accessor for ActionForm
     *
     *  @access public
     *  @return object  Ethna_ActionForm    アクションフォームオブジェクト
     */
    public function getActionForm()
    {
        return $this->container->getActionForm();
    }

    /**
     *  Setter for ActionForm
     *  if the ::$action_form class is not null, then cannot set the view
     *
     *  @access public
     *  @return object  Ethna_ActionForm    アクションフォームオブジェクト
     */
    public function setActionForm($af)
    {
        $this->container->setActionForm($af);
    }


    /**
     *  Accessor for ViewClass
     *
     *  @access public
     *  @return object  Ethna_View          ビューオブジェクト
     */
    public function getView()
    {
        return $this->container->getView();
    }

    public function getConfig(): Ethna_Config
    {
        return $this->container->getConfig();
    }

    public function getI18N(): Ethna_I18N
    {
        return $this->container->getI18N();
    }

    /**
     *  ログオブジェクトのアクセサ
     */
    public function getLogger(): Ethna_Logger
    {
        return $this->container->getLogger();
    }

    /**
     *  セッションオブジェクトのアクセサ
     */
    public function getSession(): Ethna_Session
    {
        return $this->container->getSession();
    }

    /**
     *  プラグインオブジェクトのアクセサ
     */
    public function getPlugin(): Ethna_Plugin
    {
        return $this->container->getPlugin();
    }

    /**
     *  URLハンドラオブジェクトのアクセサ
     *
     *  @access public
     *  @return object  Ethna_UrlHandler    URLハンドラオブジェクト
     */
    public function getUrlHandler()
    {
        return $this->container->getUrlHandler();
    }

    /**
     *  実行中のアクション名を返す
     *
     *  @access public
     *  @return string  実行中のアクション名
     */
    public function getCurrentActionName()
    {
        return $this->container->getCurrentActionName();
    }

    /**
     *  ロケール名へのアクセサ(R)
     *
     *  @access public
     *  @return string  ロケール名(e.x ja_JP, en_US 等),
     *                  (ロケール名は、ll_cc の形式。ll = 言語コード cc = 国コード)
     *  @see http://www.gnu.org/software/gettext/manual/html_node/Locale-Names.html
     */
    public function getLocale()
    {
        return $this->locale;
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
        $this->base = BASE;

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
        $this->base = BASE;

        Ethna::setErrorCallback(array($this, 'handleError'));

        $this->container = new Ethna_Container(BASE, $this->directory, $this->class, $this->appid, $this->locale, $this->sessionName);
        $this->directory = $this->container->getDirectories();

        $config = $this->getConfig();
        $url = $config->get('url');
        if (empty($url)) {
            $url = Ethna_Util::getUrlFromRequestUri();
            $config->set('url', $url);
        }
        $this->container->url = $url;

        $plugin = $this->getPlugin();

        $this->logger = $this->getLogger();
        $plugin->setLogger($this->logger);
        $this->logger->begin();

        $actionDir = $this->directory['action'] . "/";
        $default_form_class = $this->class['form'];
        $actionResolverClass = $this->class['action_resolver'];
        /** @var Ethna_ActionResolver $actionResolver */
        $actionResolver = new $actionResolverClass($this->getAppId(), $this->logger, $default_form_class, $actionDir);
        $this->container->setActionResolver($actionResolver);
        // アクション名の取得
        $action_name = $actionResolver->resolveActionName($request, $default_action_name);
        $this->container->setCurrentActionName($action_name);

        $this->getSession()->restore();

        $i18n = $this->getI18N();
        $i18n->setLanguage($this->locale);

        // アクションフォーム初期化
        // フォーム定義、フォーム値設定
        $action_form = $actionResolver->newActionForm($action_name, $this->container);
        $this->container->setActionForm($action_form);

        $viewResolver = new Ethna_ViewResolver($this->container, $this->logger, $this->getViewdir(), $this->getAppId(), $this->class['view']);
        $callable = $actionResolver->getController($request, $action_name, $this->container, $action_form, $viewResolver);
        $arguments = [$request];
        $response = call_user_func_array($callable, $arguments);
        return $response;
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


    public function getManager($key)
    {
        return $this->container->getManager($key);
    }

    /**
     *  アプリケーションの設定ディレクトリを取得する
     *
     *  @access public
     *  @return string  設定ディレクトリのパス名
     */
    public function getEtcdir()
    {
        return $this->getDirectory('etc');
    }

    /**
     *  アプリケーションのテンポラリディレクトリを取得する
     *
     *  @access public
     *  @return string  テンポラリディレクトリのパス名
     */
    public function getTmpdir()
    {
        return $this->getDirectory('tmp');
    }

    /**
     *  アプリケーションのテンプレートファイル拡張子を取得する
     *
     *  @access public
     *  @return string  テンプレートファイルの拡張子
     */
    public function getTemplateext()
    {
        return $this->getExt('tpl');
    }

}
