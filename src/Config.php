<?php
// vim: foldmethod=marker
/**
 *  Config.php
 *
 *  @author     Masaki Fujimoto <fujimoto@php.net>
 *  @license    http://www.opensource.org/licenses/bsd-license.php The BSD License
 *  @package    Ethna
 *  @version    $Id$
 */

// {{{ Ethna_Config
/**
 *  設定クラス
 *
 *  @author     Masaki Fujimoto <fujimoto@php.net>
 *  @access     public
 *  @package    Ethna
 */
class Ethna_Config
{
    /**#@+
     *  @access private
     */

    /** @protected    object  Ethna_Controller    controllerオブジェクト */
    protected $controller;

    /** @FIXME @protected    array   設定内容 */
    public $config = null;

    /** @var string */
    protected $filename = 'config.php';

    /**
     *  Ethna_Configクラスのコンストラクタ
     *
     *  @access public
     *  @param  object  Ethna_Controller    $controller    controllerオブジェクト
     */
    public function __construct($controller)
    {
        $this->controller = $controller;

        // 設定ファイルの読み込み
        $r = $this->_getConfig();
    }

    /**
     *  設定値へのアクセサ(R)
     *
     *  @access public
     *  @param  string  $key    設定項目名
     *  @return string  設定値
     */
    function get($key = null)
    {
        if (is_null($key)) {
            return $this->config;
        }
        if (isset($this->config[$key]) == false) {
            return null;
        }
        return $this->config[$key];
    }

    /**
     *  設定値へのアクセサ(W)
     *
     *  @access public
     *  @param  string  $key    設定項目名
     *  @param  string  $value  設定値
     */
    function set($key, $value)
    {
        $this->config[$key] = $value;
    }

    /**
     *  設定ファイルを読み込む
     *
     *  @access private

     */
    function _getConfig()
    {
        $config = array();
        $file = $this->getConfigFile();
        if (! file_exists($file)) {
            throw new Exception("file $file is not found");
        }
        include_once($file);

        // デフォルト値設定
        if (isset($_SERVER['HTTP_HOST']) && isset($config['url']) == false) {
            $config['url'] = sprintf("http://%s/", $_SERVER['HTTP_HOST']);
        }
        if (isset($config['dsn']) == false) {
            $config['dsn'] = "";
        }
        if (isset($config['log_facility']) == false) {
            $config['log_facility'] = "";
        }
        if (isset($config['log_level']) == false) {
            $config['log_level'] = "";
        }
        if (isset($config['log_option']) == false) {
            $config['log_option'] = "";
        }

        $this->config = $config;

        return 0;
    }

    /**
     *  設定ファイルに設定値を書き込む
     *
     *  @access private
     */
    function _setConfigValue($fp, $key, $value, $level)
    {
        fputs($fp, sprintf("%s'%s' => ", str_repeat("    ", $level+1), $key));
        if (is_array($value)) {
            fputs($fp, sprintf("array(\n"));
            foreach ($value as $k => $v) {
                $this->_setConfigValue($fp, $k, $v, $level+1);
            }
            fputs($fp, sprintf("%s),\n", str_repeat("    ", $level+1)));
        } else {
            fputs($fp, sprintf("'%s',\n", $value));
        }
    }

    /**
     *  設定ファイル名を取得する
     *
     *  @return string  設定ファイルへのフルパス名
     */
    public function getConfigFile()
    {
        return $this->controller->getDirectory('etc') . '/' . $this->filename;
    }
}
// }}}
