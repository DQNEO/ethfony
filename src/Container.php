<?php
use Ethna_ContainerInterface as ContainerInterface;

/**
 */
class Ethna_Container implements ContainerInterface
{
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

    public $view;

    /**
     * Ethna_Container constructor.
     * @param $directory (absolute)
     */
    public function __construct(string $base, array $directory, array $class, string $appid)
    {
        $this->base = $base;
        $this->class = $class;
        $this->appid = $appid;

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
            $obj = new $class_name($this);
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
    public function getActionForm(): Ethna_ActionForm
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

        $obj = new $class_name($this,$this->getConfig(), $this->getI18N(), $this->getSession(), $this->getActionForm());

        //  生成したオブジェクトはキャッシュする
        if (isset($this->manager[$type]) == false || is_object($this->manager[$type]) == false) {
            $this->manager[$type] = $obj;
        }

        return $obj;
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



}