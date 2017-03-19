<?php
// vim: foldmethod=marker
/**
 *  Smarty.php
 *
 *  @author     Kazuhiro Hosoi <hosoi@gree.co.jp>
 *  @license    http://www.opensource.org/licenses/bsd-license.php The BSD License
 *  @package    Ethna
 *  @version    $Id$
 */

// {{{ Ethna_Renderer_Smarty
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 *  Smarty rendere class
 *
 *  @author     Kazuhiro Hosoi <hosoi@gree.co.jp>
 *  @access     public
 *  @package    Ethna
 */
class Ethna_Renderer_Smarty extends Ethna_Renderer
{
    /** @private    string compile directory  */
    private $compile_dir;

    protected $config = array(
        'left_delimiter' => '{{',
        'right_delimiter' => '}}',
    );

    /**
     *  Ethna_Renderer_Smartyクラスのコンストラクタ
     *
     *  @access public
     */
    public function __construct(string $template_dir, array $option)
    {
        parent::__construct($template_dir, $option);

        $this->engine = new Smarty;

        // ディレクトリ関連は Kernelによって実行時に設定
        $compile_dir = $option['template_c'];

        $this->compile_dir = $compile_dir;
        $this->engine->template_dir = $this->template_dir;
        $this->engine->compile_dir = $this->compile_dir;
        $this->engine->compile_id = md5($this->template_dir);

        // delimiter setting
        $this->engine->left_delimiter = $this->config['left_delimiter'];
        $this->engine->right_delimiter = $this->config['right_delimiter'];

        // コンパイルディレクトリは必須なので一応がんばってみる
        if (is_dir($this->engine->compile_dir) === false) {
            Ethna_Util::mkdir($this->engine->compile_dir, 0755);
        }

        $this->engine->plugins_dir = array_merge(
            $option['plugins'],
            array(ETHNA_BASE . '/src/Plugin/Smarty', SMARTY_DIR . 'plugins')
        );
    }

    public function getName()
    {
        return 'smarty';
    }

    public function render(Ethna_AppDataContainer $dataContainer,$config, $ae, $form_array, $actionName, $forward_name, $forward_path ) :Response
    {
        $this->setProp('actionname', $actionName);
        $this->setProp('viewname', $forward_name);
        $this->setProp('forward_path', $forward_path);

        $app_array = $dataContainer->getAppArray();
        $app_ne_array = $dataContainer->getAppNEArray();

        $this->setPropByRef('form', $form_array);
        $this->setPropByRef('app', $app_array);
        $this->setPropByRef('app_ne', $app_ne_array);
        $message_list = Ethna_Util::escapeHtml($ae->getMessageList());
        $this->setPropByRef('errors', $message_list);
        if (isset($_SESSION)) {
            $tmp_session = Ethna_Util::escapeHtml($_SESSION);
            $this->setPropByRef('session', $tmp_session);
        }
        $this->setProp('script',
            htmlspecialchars(basename($_SERVER['SCRIPT_NAME']), ENT_QUOTES, mb_internal_encoding()));
        $this->setProp('request_uri',
            isset($_SERVER['REQUEST_URI'])
                ? htmlspecialchars($_SERVER['REQUEST_URI'], ENT_QUOTES, mb_internal_encoding())
                : '');
        $this->setProp('config', $config);

        return $this->perform($forward_path);

    }

    /**
     *  ビューを出力する
     *
     *  @param  string  $template   テンプレート名
     *  @param  bool    $capture    true ならば出力を表示せずに返す
     *
     *  @access public
     */
    public function perform($template = null) :Response
    {
        if ($template === null && $this->template === null) {
            throw new \Exception('template is not defined');
        }

        if ($template !== null) {
            $this->template = $template;
        }
        set_error_handler(null);
        if ((is_absolute_path($this->template) && is_readable($this->template))
            || is_readable($this->template_dir . $this->template)) {
            return new StreamedResponse(function() {
                $this->engine->display($this->template);
            });
        } else {
            throw new \Exception('template not found ' .$this->template_dir .  $this->template);
        }
    }

    /**
     * テンプレート変数を取得する
     *
     *  @param string $name  変数名
     *
     *  @return mixed　変数
     *
     *  @access public
     */
    public function getProp($name = null)
    {
        $property = $this->engine->get_template_vars($name);

        if ($property !== null) {
            return $property;
        }
        return null;
    }

    /**
     *  テンプレート変数を削除する
     *
     *  @param name    変数名
     *
     *  @access public
     */
    public function removeProp($name)
    {
        $this->engine->clear_assign($name);
    }

    /**
     *  テンプレート変数に配列を割り当てる
     *
     *  @param array $array
     *
     *  @access public
     */
    public function setPropArray($array)
    {
        $this->engine->assign($array);
    }

    /**
     *  テンプレート変数に配列を参照として割り当てる
     *
     *  @param array $array
     *
     *  @access public
     */
    public function setPropArrayByRef(&$array)
    {
        $this->engine->assign_by_ref($array);
    }

    /**
     *  テンプレート変数を割り当てる
     *
     *  @param string $name 変数名
     *  @param mixed $value 値
     *
     *  @access public
     */
    public function setProp($name, $value)
    {
        $this->engine->assign($name, $value);
    }

    /**
     *  テンプレート変数に参照を割り当てる
     *
     *  @param string $name 変数名
     *  @param mixed $value 値
     *
     *  @access public
     */
    public function setPropByRef($name, &$value)
    {
        $this->engine->assign_by_ref($name, $value);
    }

    /**
     *  プラグインをセットする
     *
     *  @param string $name　プラグイン名
     *  @param string $type プラグインタイプ
     *  @param mixed $plugin プラグイン本体
     *
     *  @access public
     */
    public function setPlugin($name, $type, $plugin)
    {
        //プラグイン関数の有無をチェック
        if (is_callable($plugin) === false) {
            return Ethna::raiseWarning('Does not exists.');
        }

        //プラグインの種類をチェック
        $register_method = 'register_' . $type;
        if (method_exists($this->engine, $register_method) === false) {
            return Ethna::raiseWarning('This plugin type does not exist');
        }

        // フィルタは名前なしで登録
        if ($type === 'prefilter' || $type === 'postfilter' || $type === 'outputfilter') {
            parent::setPlugin($name, $type, $plugin);
            $this->engine->$register_method($plugin);
            return;
        }

        // プラグインの名前をチェック
        if ($name === '') {
            return Ethna::raiseWarning('Please set plugin name');
        }

        // プラグインを登録する
        parent::setPlugin($name, $type, $plugin);
        $this->engine->$register_method($name, $plugin);
    }
}
// }}}
