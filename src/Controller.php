<?php
// vim: foldmethod=marker
/**
 *  Controller.php
 *
 *  @author     Masaki Fujimoto <fujimoto@php.net>
 *  @license    http://www.opensource.org/licenses/bsd-license.php The BSD License
 *  @package    Ethna
 *  @version    $Id$
 */

// {{{ Ethna_Controller
/**
 *  コントローラクラス
 *
 *  @todo       gatewayでswitchしてるところがダサダサ
 *
 *  @author     Masaki Fujimoto <fujimoto@php.net>
 *  @access     public
 *  @package    Ethna
 */
class Ethna_Controller
{
    /**#@+
     *  @access protected
     */

    /** @var    string      アプリケーションID */
    protected $appid = 'ETHNA';

    /** @var    string      アプリケーションベースディレクトリ */
    protected $base = '';

    /** @protected    string      アプリケーションベースURL */
    protected $url = '';

    /** @protected    array       アプリケーションディレクトリ */
    protected $directory = array();

    /** @protected    array       拡張子設定 */
    protected $ext = array(
        'php'           => 'php',
        'tpl'           => 'tpl',
    );

    /** @protected    array       クラス設定 */
    public $class = array();


    /**
     * @protected    string ロケール名(e.x ja_JP, en_US 等),
     *                  (ロケール名は ll_cc の形式。ll = 言語コード cc = 国コード)
     */
    protected $locale;

    protected $encoding = 'UTF-8';

    /** FIXME: UnitTestCase から動的に変更されるため、public */
    /** @protected    string  現在実行中のアクション名 */
    public $action_name;

    /** @protected    array   アプリケーションマネージャ定義 */
    protected $manager = array();

    /** @protected    object  レンダラー */
    protected $renderer = null;

    /** @protected    object  Ethna_ClassFactory  クラスファクトリオブジェクト */
    public $class_factory = null;

    /** @protected    object  Ethna_ActionForm    フォームオブジェクト */
    protected $action_form = null;

    /** @protected    object  Ethna_View          ビューオブジェクト */
    protected $view = null;

    /** @protected    object  Ethna_Config        設定オブジェクト */
    protected $config = null;

    /** @protected    object  Ethna_Logger        ログオブジェクト */
    protected $logger = null;

    /** @protected    object  Ethna_Plugin        プラグインオブジェクト */
    protected $plugin = null;

    /** @protected    string  リクエストのゲートウェイ(www/cli/rest/soap...) */
    protected $gateway = GATEWAY_WWW;

    /**#@-*/

    /**
     *  アプリケーションのエントリポイント
     *
     *  @access public
     *  @param  string  $class_name     アプリケーションコントローラのクラス名
     *  @param  mixed   $action_name    指定のアクション名(省略可)
     *  @param  mixed   $fallback_action_name   アクションが決定できなかった場合に実行されるアクション名(省略可)
     *  @static
     */
    public static function main($class_name, $action_name = "", $fallback_action_name = "")
    {
        $c = new $class_name(GATEWAY_WWW);
        $c->trigger($action_name, $fallback_action_name);
    }

    /**
     *  CLIアプリケーションのエントリポイント
     *
     *  @access public
     *  @param  string  $class_name     アプリケーションコントローラのクラス名
     *  @param  string  $action_name    実行するアクション名
     *  @static
     */
    public static function main_CLI($class_name, $action_name)
    {
        $_SERVER['HTTP_USER_AGENT'] = '';
        $_SERVER['REMOTE_ADDR'] = '';

        $c = new $class_name(GATEWAY_CLI);
        $c->trigger($action_name);
    }


    /**
     *  Ethna_Controllerクラスのコンストラクタ
     *
     *  @access     public
     */
    public function __construct($gateway)
    {
        $this->gateway = $gateway;

    }

    /**
     *  アプリケーション実行後の後始末を行います。
     *
     *  @access protected
     */
    protected function end()
    {
        //  必要に応じてオーバライドして下さい。
        $this->logger->end();
    }

    /**
     *  (現在アクティブな)コントローラのインスタンスを返す
     *
     *  @access public
     *  @return object  Ethna_Controller    コントローラのインスタンス
     *  @static
     */
    public static function getInstance()
    {
        if (isset($GLOBALS['_Ethna_controller'])) {
            return $GLOBALS['_Ethna_controller'];
        } else {
            $_ret_object = null;
            return $_ret_object;
        }
    }

    /**
     *  アプリケーションIDを返す
     *
     *  @access public
     *  @return string  アプリケーションID
     */
    public function getAppId()
    {
        return ucfirst(strtolower($this->appid));
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

    /**
     *  クライアントタイプ/言語からテンプレートディレクトリ名を決定する
     *  デフォルトでは [appid]/template/ja_JP/ (ja_JPはロケール名)
     *  ロケール名は _getDefaultLanguage で決定される。
     *
     *  @access public
     *  @return string  テンプレートディレクトリ
     *  @see    Ethna_Controller#_getDefaultLanguage
     */
    public function getTemplatedir()
    {
        $template = $this->getDirectory('template');

        // 言語別ディレクトリ
        // _getDerfaultLanguageメソッドでロケールが指定されていた場合は、
        // テンプレートディレクトリにも自動的にそれを付加する。
        if (!empty($this->locale)) {
            $template .= '/' . $this->locale;
        }

        return $template;
    }

    /**
     *  アクションディレクトリ名を決定する
     *
     *  @access public
     *  @return string  アクションディレクトリ
     */
    public function getActiondir($gateway = null)
    {
        $key = 'action';
        $gateway = is_null($gateway) ? $this->getGateway() : $gateway;
        switch ($gateway) {
        case GATEWAY_WWW:
            $key = 'action';
            break;
        case GATEWAY_CLI:
            $key = 'action_cli';
            break;
        }

        return (empty($this->directory[$key]) ? ($this->base . (empty($this->base) ? '' : '/')) : ($this->directory[$key] . "/"));
    }

    /**
     *  ビューディレクトリ名を決定する
     *
     *  @access public
     *  @return string  ビューディレクトリ
     */
    public function getViewdir()
    {
        return (empty($this->directory['view']) ? ($this->base . (empty($this->base) ? '' : '/')) : ($this->directory['view'] . "/"));
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
     *  アプリケーションディレクトリ設定を返す
     *
     *  @access public
     *  @param  string  $key    ディレクトリタイプ("tmp", "template"...)
     *  @return string  $keyに対応したアプリケーションディレクトリ(設定が無い場合はnull)
     */
    public function getDirectory($key)
    {
        if (isset($this->directory[$key]) == false) {
            return null;
        }
        return $this->directory[$key];
    }
    /**
     *  アプリケーションディレクトリ設定を返す
     *
     *  @access public
     *  @param  string  $key    type
     *  @return string  $key    directory
     */
    public function setDirectory($key, $value)
    {
        $this->directory[$key] = $value;
    }


    /**
     *  アプリケーション拡張子設定を返す
     *
     *  @access public
     *  @param  string  $key    拡張子タイプ("php", "tpl"...)
     *  @return string  $keyに対応した拡張子(設定が無い場合はnull)
     */
    public function getExt($key)
    {
        if (isset($this->ext[$key]) == false) {
            return null;
        }
        return $this->ext[$key];
    }

    /**
     *  クラスファクトリオブジェクトのアクセサ(R)
     *
     *  @access public
     *  @return object  Ethna_ClassFactory  クラスファクトリオブジェクト
     */
    public function getClassFactory()
    {
        return $this->class_factory;
    }

    /**
     *  アクションエラーオブジェクトのアクセサ
     *
     *  @access public
     *  @return object  Ethna_ActionError   アクションエラーオブジェクト
     */
    public function getActionError()
    {
        return $this->class_factory->getObject('error');
    }

    /**
     *  Accessor for ActionForm
     *
     *  @access public
     *  @return object  Ethna_ActionForm    アクションフォームオブジェクト
     */
    public function getActionForm()
    {
        // 明示的にクラスファクトリを利用していない
        return $this->action_form;
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
        if ($this->action_form !== null) {
            return false;
        }
        $this->action_form = $af;
        return true;
    }


    /**
     *  Accessor for ViewClass
     *
     *  @access public
     *  @return object  Ethna_View          ビューオブジェクト
     */
    public function getView()
    {
        // 明示的にクラスファクトリを利用していない
        return $this->view;
    }

    /**
     *  backendオブジェクトのアクセサ
     *
     *  @access public
     *  @return object  Ethna_Backend   backendオブジェクト
     */
    public function getBackend()
    {
        return $this->class_factory->getObject('backend');
    }

    /**
     *  設定オブジェクトのアクセサ
     *
     *  @access public
     *  @return object  Ethna_Config    設定オブジェクト
     */
    public function getConfig()
    {
        return $this->class_factory->getObject('config');
    }

    /**
     *  i18nオブジェクトのアクセサ(R)
     *
     *  @access public
     *  @return object  Ethna_I18N  i18nオブジェクト
     */
    public function getI18N()
    {
        return $this->class_factory->getObject('i18n');
    }

    /**
     *  ログオブジェクトのアクセサ
     *
     *  @access public
     *  @return object  Ethna_Logger        ログオブジェクト
     */
    public function getLogger()
    {
        return $this->class_factory->getObject('logger');
    }

    /**
     *  セッションオブジェクトのアクセサ
     *
     *  @access public
     *  @return object  Ethna_Session       セッションオブジェクト
     */
    public function getSession()
    {
        return $this->class_factory->getObject('session');
    }

    /**
     *  プラグインオブジェクトのアクセサ
     *
     *  @access public
     *  @return object  Ethna_Plugin    プラグインオブジェクト
     */
    public function getPlugin()
    {
        return $this->class_factory->getObject('plugin');
    }

    /**
     *  URLハンドラオブジェクトのアクセサ
     *
     *  @access public
     *  @return object  Ethna_UrlHandler    URLハンドラオブジェクト
     */
    public function getUrlHandler()
    {
        return $this->class_factory->getObject('url_handler');
    }

    /**
     *  マネージャ一覧を返す
     *
     *  @access public
     *  @return array   マネージャ一覧
     *  @obsolete
     */
    public  function getManagerList()
    {
        return $this->manager;
    }

    /**
     *  実行中のアクション名を返す
     *
     *  @access public
     *  @return string  実行中のアクション名
     */
    public function getCurrentActionName()
    {
        return $this->action_name;
    }

    /**
     *  ロケール設定、使用言語を取得する
     *
     *  @access public
     *  @return array   ロケール名(e.x ja_JP, en_US 等),
     *                  クライアントエンコーディング名 の配列
     *                  (ロケール名は、ll_cc の形式。ll = 言語コード cc = 国コード)
     *  @see http://www.gnu.org/software/gettext/manual/html_node/Locale-Names.html
     */
    public function getLanguage()
    {
        return array($this->locale, $this->encoding);
    }

    /**
     *  ロケール名へのアクセサ(R)
     *
     *  @access public
     *  @return string  ロケール名(e.x ja_JP, en_US 等),
     *                  (ロケール名は、ll_cc の形式。ll = 言語コード cc = 国コード)
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     *  ロケール名へのアクセサ(W)
     *
     *  @access public
     *  @param $locale ロケール名(e.x ja_JP, en_US 等),
     *                 (ロケール名は、ll_cc の形式。ll = 言語コード cc = 国コード)
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;
        $i18n = $this->getI18N();
        $i18n->setLanguage($this->locale);
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
     *  ゲートウェイを取得する
     *
     *  @access public
     */
    public function getGateway()
    {
        return $this->gateway;
    }


    /**
     *  フレームワークの処理を実行する(WWW)
     *
     *  引数$default_action_nameに配列が指定された場合、その配列で指定された
     *  アクション以外は受け付けない(指定されていないアクションが指定された
     *  場合、配列の先頭で指定されたアクションが実行される)
     *
     *  @access private
     *  @param  mixed   $default_action_name    指定のアクション名
     *  @param  mixed   $fallback_action_name   アクション名が決定できなかった場合に実行されるアクション名
     */
    private function trigger($default_action_name = "", $fallback_action_name = "")
    {

        mb_internal_encoding($this->encoding);
        mb_regex_encoding($this->encoding);
        $GLOBALS['_Ethna_controller'] = $this;
        if ($this->base === "") {
            // EthnaコマンドなどでBASEが定義されていない場合がある
            if (defined('BASE')) {
                $this->base = BASE;
            }
        }


        // クラスファクトリオブジェクトの生成
        $class_factory = $this->class['class'];
        $this->class_factory = new $class_factory($this, $this->class);

        // エラーハンドラの設定
        Ethna::setErrorCallback(array($this, 'handleError'));

        // ディレクトリ名の設定(相対パス->絶対パス)
        foreach ($this->directory as $key => $value) {
            if ($key == 'plugins') {
                // Smartyプラグインディレクトリは配列で指定する
                $tmp = array();
                foreach (to_array($value) as $elt) {
                    if (Ethna_Util::isAbsolute($elt) == false) {
                        $tmp[] = $this->base . (empty($this->base) ? '' : '/') . $elt;
                    }
                }
                $this->directory[$key] = $tmp;
            } else {
                if (Ethna_Util::isAbsolute($value) == false) {
                    $this->directory[$key] = $this->base . (empty($this->base) ? '' : '/') . $value;
                }
            }
        }
        // 初期設定
        $this->locale = 'ja_JP';

        $this->config = $this->getConfig();
        $this->url = $this->config->get('url');
        if (empty($this->url) && PHP_SAPI != 'cli') {
            $this->url = Ethna_Util::getUrlFromRequestUri();
            $this->config->set('url', $this->url);
        }

        // プラグインオブジェクトの用意
        $this->plugin = $this->getPlugin();

        // ログ出力開始
        $this->logger = $this->getLogger();
        $this->plugin->setLogger($this->logger);
        $this->logger->begin();

        // アクション名の取得
        $action_name = $this->_getActionName($default_action_name, $fallback_action_name);

        // アクション定義の取得
        $action_obj = $this->_getAction($action_name);
        if (is_null($action_obj)) {
            if ($fallback_action_name != "") {
                $this->logger->log(LOG_DEBUG, 'undefined action [%s] -> try fallback action [%s]', $action_name, $fallback_action_name);
                $action_obj = $this->_getAction($fallback_action_name);
            }
            if (is_null($action_obj)) {
                $this->end();
                $r = Ethna::raiseError("undefined action [%s]", E_APP_UNDEFINED_ACTION, $action_name);
                throw new \Exception($r->getMessage());

            } else {
                $action_name = $fallback_action_name;
            }
        }

        $this->action_name = $action_name;

        // オブジェクト生成
        $backend = $this->getBackend();
        $session = $this->getSession();
        $session->restore();

        // 言語切り替えフックを呼ぶ
        //   $this->localeを書き換えた場合は
        //   必ず Ethna_I18N クラスの setLanguageメソッドも呼ぶこと!
        //   さもないとカタログその他が再ロードされない！
        $i18n = $this->getI18N();
        $i18n->setLanguage($this->locale);

        // アクションフォーム初期化
        // フォーム定義、フォーム値設定
        $form_name = $this->getActionFormName($action_name);
        $this->action_form = new $form_name($this);
        $backend->setActionForm($this->action_form);
        $this->action_form->setFormDef_PreHelper();
        $this->action_form->setFormVars();

        // Action#perform 実行
        $action_class_name = $this->getActionClassName($action_name);
        $ac = new $action_class_name($backend);
        $backend->setActionClass($ac);
        $forward_name = $this->perform($ac);

        if ($forward_name === null) {
            $this->end();
        } else {
            $this->renderView($forward_name, $backend);
            $this->end();
        }


    }

    protected function renderView(string $forward_name, $backend)
    {
        $view_class_name = $this->getViewClassName($forward_name);
        $this->view = new $view_class_name($backend, $forward_name, $this->getTemplatePath($forward_name));
        $this->view->preforward();
        $this->view->forward();
    }

    /**
     *  アクションを実行する
     *
     *  @param  obj     Ethna_ActionClass アクションクラス
     *  @return mixed   (string):Forward名(nullならforwardしない) Ethna_Error:エラー
     */
    protected function perform($ac)
    {
        $forward_name = $ac->authenticate();
        if ($forward_name === false) {
            return null;
        } else if ($forward_name !== null) {
            return $forward_name;
        }

        $forward_name = $ac->prepare();
        if ($forward_name === false) {
            return null;
        } else if ($forward_name !== null) {
            return $forward_name;
        }

        return $ac->perform();
    }

    /**
     *  エラーハンドラ
     *
     *  エラー発生時の追加処理を行いたい場合はこのメソッドをオーバーライドする
     *  (アラートメール送信等−デフォルトではログ出力時にアラートメール
     *  が送信されるが、エラー発生時に別にアラートメールをここで送信
     *  させることも可能)
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
     *  エラーメッセージを取得する
     *
     *  @access public
     *  @param  int     $code       エラーコード
     *  @return string  エラーメッセージ
     */
    public function getErrorMessage($code)
    {
        $message_list = $GLOBALS['_Ethna_error_message_list'];
        for ($i = count($message_list)-1; $i >= 0; $i--) {
            if (array_key_exists($code, $message_list[$i])) {
                return $message_list[$i][$code];
            }
        }
        return null;
    }

    /**
     *  実行するアクション名を返す
     *
     *  @access protected
     *  @param  mixed   $default_action_name    指定のアクション名
     *  @return string  実行するアクション名
     */
    protected function _getActionName($default_action_name, $fallback_action_name)
    {
        // フォームから要求されたアクション名を取得する
        $form_action_name = $this->_getActionName_Form();
        $form_action_name = preg_replace('/[^a-z0-9\-_]+/i', '', $form_action_name);
        $this->logger->log(LOG_DEBUG, 'form_action_name[%s]', $form_action_name);

        // フォームからの指定が無い場合はエントリポイントに指定されたデフォルト値を利用する
        if ($form_action_name == "" && count($default_action_name) > 0) {
            $tmp = is_array($default_action_name) ? $default_action_name[0] : $default_action_name;
            if ($tmp{strlen($tmp)-1} == '*') {
                $tmp = substr($tmp, 0, -1);
            }
            $this->logger->log(LOG_DEBUG, '-> default_action_name[%s]', $tmp);
            $action_name = $tmp;
        } else {
            $action_name = $form_action_name;
        }

        // エントリポイントに配列が指定されている場合は指定以外のアクション名は拒否する
        if (is_array($default_action_name)) {
            if ($this->_isAcceptableActionName($action_name, $default_action_name) == false) {
                // 指定以外のアクション名で合った場合は$fallback_action_name(or デフォルト)
                $tmp = $fallback_action_name != "" ? $fallback_action_name : $default_action_name[0];
                if ($tmp{strlen($tmp)-1} == '*') {
                    $tmp = substr($tmp, 0, -1);
                }
                $this->logger->log(LOG_DEBUG, '-> fallback_action_name[%s]', $tmp);
                $action_name = $tmp;
            }
        }

        $this->logger->log(LOG_DEBUG, '<<< action_name[%s] >>>', $action_name);

        return $action_name;
    }

    /**
     *  フォームにより要求されたアクション名を返す
     *
     *  アプリケーションの性質に応じてこのメソッドをオーバーライドして下さい。
     *  デフォルトでは"action_"で始まるフォーム値の"action_"の部分を除いたもの
     *  ("action_sample"なら"sample")がアクション名として扱われます
     *
     *  @access protected
     *  @return string  フォームにより要求されたアクション名
     */
    protected function _getActionName_Form()
    {
        if (isset($_SERVER['REQUEST_METHOD']) == false) {
            return null;
        }

        $url_handler = $this->getUrlHandler();
        if ($_SERVER['REQUEST_METHOD'] == "GET") {
            $tmp_vars = $_GET;
        } else if ($_SERVER['REQUEST_METHOD'] == "POST") {
            $tmp_vars = $_POST;
        }

        if (empty($_SERVER['URL_HANDLER']) == false) {
            $tmp_vars['__url_handler__'] = $_SERVER['URL_HANDLER'];
            $tmp_vars['__url_info__'] = isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : null;
            $tmp_vars = $url_handler->requestToAction($tmp_vars);

            if ($_SERVER['REQUEST_METHOD'] == "GET") {
                $_GET = array_merge($_GET, $tmp_vars);
            } else if ($_SERVER['REQUEST_METHOD'] == "POST") {
                $_POST = array_merge($_POST, $tmp_vars);
            }
            $_REQUEST = array_merge($_REQUEST, $tmp_vars);
        }

        if (strcasecmp($_SERVER['REQUEST_METHOD'], 'post') == 0) {
            $http_vars = $_POST;
        } else {
            $http_vars = $_GET;
        }

        // フォーム値からリクエストされたアクション名を取得する
        $action_name = $sub_action_name = null;
        foreach ($http_vars as $name => $value) {
            if ($value == "" || strncmp($name, 'action_', 7) != 0) {
                continue;
            }

            $tmp = substr($name, 7);

            // type="image"対応
            if (preg_match('/_x$/', $name) || preg_match('/_y$/', $name)) {
                $tmp = substr($tmp, 0, strlen($tmp)-2);
            }

            // value="dummy"となっているものは優先度を下げる
            if ($value == "dummy") {
                $sub_action_name = $tmp;
            } else {
                $action_name = $tmp;
            }
        }
        if ($action_name == null) {
            $action_name = $sub_action_name;
        }

        return $action_name;
    }

    /**
     *  アクション名を指定するクエリ/HTMLを生成する
     *
     *  @access public
     *  @param  string  $action action to request
     *  @param  string  $type   hidden, url...
     *  @todo   consider gateway
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

    /**
     *  フォームにより要求されたアクション名に対応する定義を返す
     *
     *  @param  string  $action_name    アクション名
     *  @return array   アクション定義
     */
    public function _getAction($action_name)
    {
        $action = array();
        $action_obj = array();

        // アクションスクリプトのインクルード
        $this->_includeActionScript($action_name);

        $action_obj['class_name'] = $this->getDefaultActionClass($action_name);
        $action_obj['form_name'] = $this->getDefaultFormClass($action_name);

        // 必要条件の確認
        if (class_exists($action_obj['class_name']) == false) {
            $this->logger->log(LOG_NOTICE, 'action class is not defined [%s]', $action_obj['class_name']);
            return null;
        }
        if (class_exists($action_obj['form_name']) == false) {
            // フォームクラスは未定義でも良い
            $class_name = $this->class_factory->getObjectName('form');
            $this->logger->log(LOG_DEBUG, 'form class is not defined [%s] -> falling back to default [%s]', $action_obj['form_name'], $class_name);
            $action_obj['form_name'] = $class_name;
        }

        return $action_obj;
    }

    /**
     *  アクション名が実行許可されているものかどうかを返す
     *
     *  @access private
     *  @param  string  $action_name            リクエストされたアクション名
     *  @param  array   $default_action_name    許可されているアクション名
     *  @return bool    true:許可 false:不許可
     */
    private function _isAcceptableActionName($action_name, $default_action_name)
    {
        foreach (to_array($default_action_name) as $name) {
            if ($action_name == $name) {
                return true;
            } else if ($name{strlen($name)-1} == '*') {
                if (strncmp($action_name, substr($name, 0, -1), strlen($name)-1) == 0) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     *  指定されたアクションのフォームクラス名を返す(オブジェクトの生成は行わない)
     *
     *  @access public
     *  @param  string  $action_name    アクション名
     *  @return string  アクションのフォームクラス名
     */
    public function getActionFormName($action_name)
    {
        $action_obj = $this->_getAction($action_name);
        if (is_null($action_obj)) {
            return null;
        }

        return $action_obj['form_name'];
    }

    /**
     *  アクションに対応するフォームクラス名が省略された場合のデフォルトクラス名を返す
     *
     *  デフォルトでは[プロジェクトID]_Form_[アクション名]となるので好み応じてオーバライドする
     *
     *  @access public
     *  @param  string  $action_name    アクション名
     *  @return string  アクションフォーム名
     */
    public function getDefaultFormClass($action_name, $gateway = null)
    {
        $gateway_prefix = $this->_getGatewayPrefix($gateway);

        $postfix = preg_replace_callback('/_(.)/', function(array $matches){return strtoupper($matches[1]);}, ucfirst($action_name));
        $r = sprintf("%s_%sForm_%s", $this->getAppId(), $gateway_prefix ? $gateway_prefix . "_" : "", $postfix);
        $this->logger->log(LOG_DEBUG, "default action class [%s]", $r);

        return $r;
    }

    /**
     *  getDefaultFormClass()で取得したクラス名からアクション名を取得する
     *
     *  getDefaultFormClass()をオーバーライドした場合、こちらも合わせてオーバーライド
     *  することを推奨(必須ではない)
     *
     *  @access public
     *  @param  string  $class_name     フォームクラス名
     *  @return string  アクション名
     */
    public function actionFormToName($class_name)
    {
        $prefix = sprintf("%s_Form_", $this->getAppId());
        if (preg_match("/$prefix(.*)/", $class_name, $match) == 0) {
            // 不明なクラス名
            return null;
        }
        $target = $match[1];

        $action_name = substr(preg_replace('/([A-Z])/e', "'_' . strtolower('\$1')", $target), 1);

        return $action_name;
    }

    /**
     *  アクションに対応するフォームパス名が省略された場合のデフォルトパス名を返す
     *
     *  デフォルトでは_getDefaultActionPath()と同じ結果を返す(1ファイルに
     *  アクションクラスとフォームクラスが記述される)ので、好みに応じて
     *  オーバーライドする
     *
     *  @access public
     *  @param  string  $action_name    アクション名
     *  @return string  form classが定義されるスクリプトのパス名
     */
    public function getDefaultFormPath($action_name)
    {
        return $this->getDefaultActionPath($action_name);
    }

    /**
     *  指定されたアクションのクラス名を返す(オブジェクトの生成は行わない)
     *
     *  @access public
     *  @param  string  $action_name    アクションの名称
     *  @return string  アクションのクラス名
     */
    public function getActionClassName($action_name)
    {
        $action_obj = $this->_getAction($action_name);
        if ($action_obj == null) {
            return null;
        }

        return $action_obj['class_name'];
    }

    /**
     *  アクションに対応するアクションクラス名が省略された場合のデフォルトクラス名を返す
     *
     *  デフォルトでは[プロジェクトID]_Action_[アクション名]となるので好み応じてオーバライドする
     *
     *  @access public
     *  @param  string  $action_name    アクション名
     *  @return string  アクションクラス名
     */
    public function getDefaultActionClass($action_name, $gateway = null)
    {
        $gateway_prefix = $this->_getGatewayPrefix($gateway);

        $postfix = preg_replace_callback('/_(.)/', function(array $matches){return strtoupper($matches[1]);}, ucfirst($action_name));
        $r = sprintf("%s_%sAction_%s", $this->getAppId(), $gateway_prefix ? $gateway_prefix . "_" : "", $postfix);
        $this->logger->log(LOG_DEBUG, "default action class [%s]", $r);

        return $r;
    }

    /**
     *  getDefaultActionClass()で取得したクラス名からアクション名を取得する
     *
     *  getDefaultActionClass()をオーバーライドした場合、こちらも合わせてオーバーライド
     *  することを推奨(必須ではない)
     *
     *  @access public
     *  @param  string  $class_name     アクションクラス名
     *  @return string  アクション名
     */
    public function actionClassToName($class_name)
    {
        $prefix = sprintf("%s_Action_", $this->getAppId());
        if (preg_match("/$prefix(.*)/", $class_name, $match) == 0) {
            // 不明なクラス名
            return null;
        }
        $target = $match[1];

        $action_name = substr(preg_replace('/([A-Z])/e', "'_' . strtolower('\$1')", $target), 1);

        return $action_name;
    }

    /**
     *  アクションに対応するアクションパス名が省略された場合のデフォルトパス名を返す
     *
     *  デフォルトでは"foo_bar" -> "/Foo/Bar.php"となるので好み応じてオーバーライドする
     *
     *  @access public
     *  @param  string  $action_name    アクション名
     *  @return string  アクションクラスが定義されるスクリプトのパス名
     */
    public function getDefaultActionPath($action_name)
    {
        $r = preg_replace_callback('/_(.)/', function(array $matches){return '/' . strtoupper($matches[1]);}, ucfirst($action_name)) . '.' . $this->getExt('php');
        $this->logger->log(LOG_DEBUG, "default action path [%s]", $r);

        return $r;
    }

    /**
     *  遷移名に対応するビュークラス名を返す(オブジェクトの生成は行わない)
     *
     *  [appid]_View_[forward_name]となる
     *
     *
     *  @access public
     *  @param  string  $forward_name   遷移先の名称
     *  @return string  view classのクラス名
     */
    public function getViewClassName(string $forward_name)
    {
        $view_dir = $this->getViewdir();
        $view_path = preg_replace_callback('/_(.)/', function(array $matches){return '/' . strtoupper($matches[1]); }, ucfirst($forward_name)) . '.' . $this->getExt('php');
        $this->logger->log(LOG_DEBUG, "default view path [%s]", $view_path);

        if (file_exists($view_dir . $view_path)) {
            include_once $view_dir . $view_path;
        } else {
            $this->logger->log(LOG_DEBUG, 'default view file not found [%s]', $view_path);
        }

        $postfix = preg_replace_callback('/_(.)/', function(array $matches){return strtoupper($matches[1]);}, ucfirst($forward_name));
        $class_name = sprintf("%s_%sView_%s", $this->getAppId(), "", $postfix);
        $this->logger->log(LOG_DEBUG, "view class [%s]", $class_name);
        if (class_exists($class_name)) {
            return $class_name;
        } else {
            $class_name = $this->class_factory->getObjectName('view');
            $this->logger->log(LOG_DEBUG, 'view class is not defined for [%s] -> use default [%s]', $forward_name, $class_name);
            return $class_name;
        }
    }

    /**
     *  遷移名に対応するテンプレートパス名が省略された場合のデフォルトパス名を返す
     *
     *  デフォルトでは"foo_bar"というforward名が"foo/bar" + テンプレート拡張子となる
     *  ので好み応じてオーバライドする
     *
     *  @access public
     *  @param  string  $forward_name   forward名
     *  @return string  forwardパス名
     */
    public function getTemplatePath($forward_name)
    {
        return str_replace('_', '/', $forward_name) . '.tpl';
    }

    /**
     *  テンプレートパス名から遷移名を取得する
     *
     *  getDefaultForwardPath()をオーバーライドした場合、こちらも合わせてオーバーライド
     *  することを推奨(必須ではない)
     *
     *  @access public
     *  @param  string  $forward_path   テンプレートパス名
     *  @return string  遷移名
     */
    public function forwardPathToName($forward_path)
    {
        $forward_path = preg_replace('/^\/+/', '', $forward_path);
        $forward_path = preg_replace(sprintf('/\.%s$/', $this->getExt('tpl')), '', $forward_path);

        return str_replace('/', '_', $forward_path);
    }


    /**
     *  レンダラを取得する
     *
     *  @access public
     *  @return object  Ethna_Renderer  レンダラオブジェクト
     */
    public function getRenderer()
    {
        if ($this->renderer instanceof Ethna_Renderer) {
            return $this->renderer;
        }

        $this->renderer = $this->class_factory->getObject('renderer');
        if ($this->renderer === null) {
            trigger_error("cannot get renderer", E_USER_ERROR);
        }
        return $this->renderer;
    }

    /**
     *  デフォルト状態でのゲートウェイを取得する
     *
     *  @access protected
     *  @return int     ゲートウェイ定義(GATEWAY_WWW, GATEWAY_CLI...)
     */
    protected function _getDefaultGateway($gateway)
    {
        if (is_null($GLOBALS['_Ethna_gateway']) == false) {
            return $GLOBALS['_Ethna_gateway'];
        }
        return GATEWAY_WWW;
    }

    /**
     *  ゲートウェイに対応したクラス名のプレフィクスを取得する
     *
     *  @access public
     *  @param  string  $gateway    ゲートウェイ
     *  @return string  ゲートウェイクラスプレフィクス
     */
    protected function _getGatewayPrefix($gateway = null)
    {
        $gateway = is_null($gateway) ? $this->getGateway() : $gateway;
        switch ($gateway) {
        case GATEWAY_WWW:
            $prefix = '';
            break;
        case GATEWAY_CLI:
            $prefix = 'Cli';
            break;
        default:
            $prefix = '';
            break;
        }

        return $prefix;
    }

    /**
     *  マネージャクラス名を取得する
     *
     *  @access public
     *  @param  string  $name   マネージャキー
     *  @return string  マネージャクラス名
     */
    public function getManagerClassName($name)
    {
        //   アプリケーションIDと、渡された名前のはじめを大文字にして、
        //   組み合わせたものが返される
        $manager_id = preg_replace_callback('/_(.)/', function(array $matches){return strtoupper($matches[1]);}, ucfirst($name));
        return sprintf('%s_%sManager', $this->getAppId(), ucfirst($manager_id));
    }

    /**
     *  アクションスクリプトをインクルードする
     *
     *  ただし、インクルードしたファイルにクラスが正しく定義されているかどうかは保証しない
     *
     *  @access private
     *  @param  string  $action_name    アクション名
     */
    protected function _includeActionScript($action_name)
    {
        $class_path = $form_path = null;

        $action_dir = $this->getActiondir();

        $class_path = $this->getDefaultActionPath($action_name);
        if (file_exists($action_dir . $class_path)) {
            include_once $action_dir . $class_path;
        } else {
            $this->logger->log(LOG_INFO, 'file not found:'.$action_dir . $class_path);
            return;
        }

        $form_path = $this->getDefaultFormPath($action_name);
        if ($form_path == $class_path) {
            return;
        }
        if (file_exists($action_dir . $form_path)) {
            include_once $action_dir . $form_path;
        } else {
            $this->logger->log(LOG_DEBUG, 'default form file not found [%s] -> maybe falling back to default form class', $form_path);
        }
    }
}
