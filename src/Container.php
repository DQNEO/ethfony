<?php
use Ethna_ContainerInterface as ContainerInterface;

/**
 */
class Ethna_Container implements ContainerInterface
{
    protected $locale;

    /**
     * @var array
     */
    protected $directory;

    /**
     * @var string
     */
    private $base;

    /**
     * @var array
     */
    private $class;

    /**
     * @var string
     */
    private $appid;

    /** @protected    array       拡張子設定 */
    protected $ext = array(
        'php'           => 'php',
        'tpl'           => 'tpl',
    );

    protected $action_form;

    protected $currentActionName;

    /** @var  Ethna_ActionResolver */
    protected $actionResolver;

    /** @var  Ethna_ViewClass */
    public $view;

    public $url;

    /** @var  Ethna_AppDataContainer */
    protected $dataContainer;

    /**
     * @return Ethna_AppDataContainer
     */
    public function getDataContainer(): Ethna_AppDataContainer
    {
        return $this->dataContainer;
    }

    /**
     *  アプリケーションベースURLを返す
     *
     *  @access public
     *  @return string  アプリケーションベースURL
     */
    public function getURL()
    {
        return $this->url;
    }

    /** @var  Ethna_Container */
    private static $instance;

    /**
     * @var
     */
    protected $sessionName;

    public static function getInstance(): Ethna_ContainerInterface
    {
        return static::$instance;
    }

    /**
     * Ethna_Container constructor.
     * @param $directory (absolute)
     */
    public function __construct(string $base, array $directory, array $class, string $appid, $locale, $sessionName)
    {
        $this->base = $base;
        $this->class = $class;
        $this->appid = $appid;
        $this->locale = $locale;
        $this->sessionName = $sessionName;

        $this->dataContainer = new Ethna_AppDataContainer();
        /**
         * ディレクトリ設定を絶対パスに変換
         */
        // ディレクトリ名の設定(相対パス->絶対パス)
        foreach ($directory as $key => $value) {
            if ($key == 'plugins') {
                // Smartyプラグインディレクトリは配列で指定する
                $tmp = array();
                foreach (to_array($value) as $elt) {
                    $tmp[] = $this->base . '/' . $elt;
                }
                $directory[$key] = $tmp;
            } else {
                $directory[$key] = $this->base . '/' . $value;
            }
        }

        $this->directory = $directory;
        static::$instance = $this;
    }

    /**
     *  アプリケーションベースディレクトリを返す
     *
     *  @access public
     *  @return string  アプリケーションベースディレクトリ
     */
    public function getBasedir()
    {
        return $this->base;
    }

    public function getDirectories(): array
    {
        return $this->directory;
    }


    /**
     *  アプリケーションディレクトリ設定を返す
     *
     *  @access public
     *  @param  string  $key    ディレクトリタイプ("tmp", "template"...)
     *  @return string  $keyに対応したアプリケーションディレクトリ(設定が無い場合はnull)
     */
    public function getDirectory(string $key)
    {
        if (isset($this->directory[$key]) == false) {
            return null;
        }
        return $this->directory[$key];
    }

    /**
     *  設定オブジェクトのアクセサ
     */
    public function getConfig(): Ethna_Config
    {
        static $obj = null;
        if ($obj === null) {
            $class_name = $this->class['config'];
            $obj = new $class_name($this->getDirectory('etc'));
        }
        return $obj;
    }

    /**
     *  i18nオブジェクトのアクセサ(R)
     */
    public function getI18N(): Ethna_I18N
    {
        static $obj = null;
        if ($obj === null) {
            $class_name = $this->class['i18n'];
            $obj = new $class_name($this->getDirectory('locale'), $this->getAppId());
        }
        return $obj;
    }

    /**
     *  ログオブジェクトのアクセサ
     */
    public function getLogger(): Ethna_Logger
    {
        static $obj = null;
        if ($obj === null) {
            $class_name = $this->class['logger'];
            $obj = new $class_name($this);
        }
        return $obj;
    }

    /**
     *  セッションオブジェクトのアクセサ
     */
    public function getSession(): Ethna_Session
    {
        static $obj = null;
        if ($obj === null) {
            $class_name = $this->class['session'];
            $obj = new $class_name($this, $this->sessionName);
        }
        return $obj;
    }

    /**
     *  URLハンドラオブジェクトのアクセサ
     *
     *  @access public
     *  @return object  Ethna_UrlHandler    URLハンドラオブジェクト
     */
    public function getUrlHandler()
    {
        $class_name = $this->class['url_handler'];
        return $class_name::getInstance();
    }

    /**
     *  アクションエラーオブジェクトのアクセサ
     */
    public function getActionError(): Ethna_ActionError
    {
        static $obj = null;
        if ($obj === null) {
            $class_name = $this->class['error'];
            $obj = new $class_name($this->getLogger());
        }
        return $obj;
    }

    /**
     *  プラグインオブジェクトのアクセサ
     */
    public function getPlugin(): Ethna_Plugin
    {
        static $obj = null;
        if ($obj === null) {
            $class_name = $this->class['plugin'];
            $obj = new $class_name($this);
        }
        return $obj;
    }

    /**
     *  アプリケーションIDを返す
     *
     *  @access public
     *  @return string  アプリケーションID
     */
    public function getAppId(): string
    {
        return ucfirst(strtolower($this->appid));
    }

    /**
     *  アプリケーション拡張子設定を返す
     *
     *  @access public
     *  @param  string  $key    拡張子タイプ("php", "tpl"...)
     *  @return string  $keyに対応した拡張子(設定が無い場合はnull)
     */
    public function getExt(string $key):string
    {
        if (isset($this->ext[$key]) == false) {
            return null;
        }
        return $this->ext[$key];
    }

    /**
     */
    public function getActionForm(): ?Ethna_ActionForm
    {
        return $this->action_form;
    }

    /**
     */
    public function setActionForm(Ethna_ActionForm $action_form)
    {
        $this->action_form = $action_form;
    }

    /**
     *  typeに対応するアプリケーションマネージャオブジェクトを返す
     *  注意： typeは大文字小文字を区別しない
     *         (PHP自体が、クラス名の大文字小文字を区別しないため)
     *
     *  マネジャークラスをincludeすることはしないので、
     *  アプリケーション側でオートロードする必要がある。
     *
     *  @access public
     *  @param  string  $type   アプリケーションマネージャー名
     *  @return object  Ethna_AppManager    マネージャオブジェクト
     */
    public function getManager($type)
    {
        //   アプリケーションIDと、渡された名前のはじめを大文字にして、
        //   組み合わせたものが返される
        $manager_id = preg_replace_callback('/_(.)/', function(array $matches){return strtoupper($matches[1]);}, ucfirst($type));
        $class_name = sprintf('%s_%sManager', $this->getAppId(), ucfirst($manager_id));

        //  PHPのクラス名は大文字小文字を区別しないので、
        //  同じクラス名と見做されるものを指定した場合には
        //  同じインスタンスが返るようにする
        $type = strtolower($type);

        //  キャッシュがあればそれを利用
        if (isset($this->manager[$type]) && is_object($this->manager[$type])) {
            return $this->manager[$type];
        }

        $obj = new $class_name($this);

        //  生成したオブジェクトはキャッシュする
        if (isset($this->manager[$type]) == false || is_object($this->manager[$type]) == false) {
            $this->manager[$type] = $obj;
        }

        return $obj;
    }

    /** @protected    object  レンダラー */
    protected $renderer;

    /**
     *  レンダラを取得する
     *
     *  @access public
     *  @return object  Ethna_Renderer  レンダラオブジェクト
     */
    public function getRenderer()
    {
        if (isset($this->renderer)) {
            return $this->renderer;
        }

        $class_name = $this->class['renderer'];
        $this->renderer = new $class_name($this->getTemplatedir($this->locale), $this->getDirectories());
        return $this->renderer;
    }

    /**
     *  クライアントタイプ/言語からテンプレートディレクトリ名を決定する
     *  デフォルトでは [appid]/template/ja_JP/ (ja_JPはロケール名)
     *  ロケール名は _getDefaultLanguage で決定される。
     *
     *  @access public
     *  @return string  テンプレートディレクトリ
     *  @see    Ethna_Kernel#_getDefaultLanguage
     */
    public function getTemplatedir(string $locale)
    {
        $template = $this->getDirectory('template');

        // 言語別ディレクトリ
        return  $template . '/' . $locale;
    }


    /**
     *  実行中のアクション名を返す
     *
     *  @access public
     *  @return string  実行中のアクション名
     */
    public function getCurrentActionName()
    {
        return $this->currentActionName;
    }

    /**
     * @param mixed $currentActionName
     */
    public function setCurrentActionName($currentActionName)
    {
        $this->currentActionName = $currentActionName;
    }

    /**
     * @param string $action_name
     * @return string form_name
     */
    public function getActionFormName(string $action_name)
    {
        return $this->actionResolver->getActionFormName($action_name);
    }

    public function getActionResolver() :Ethna_ActionResolver
    {
        return $this->actionResolver;
    }

    /**
     * @param Ethna_ActionResolver $actionResolver
     */
    public function setActionResolver(Ethna_ActionResolver $actionResolver)
    {
        $this->actionResolver = $actionResolver;
    }

    /**
     * @return Ethna_ViewClass
     */
    public function getView(): Ethna_ViewClass
    {
        return $this->view;
    }

    /**
     * @param Ethna_ViewClass $view
     */
    public function setView(Ethna_ViewClass $view)
    {
        $this->view = $view;
    }

    /**
     *  ビューディレクトリ名を決定する
     *
     *  @return string  ビューディレクトリ
     */
    public function getViewdir()
    {
        return $this->directory['view'] . "/";
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



}