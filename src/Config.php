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

    /** @FIXME @protected    array   設定内容 */
    public $config = null;

    /** @var string */
    protected $filename = 'config.php';

    /**
     *  Ethna_Configクラスのコンストラクタ
     *
     */
    public function __construct(string $dir)
    {
        $this->dir = $dir;
        $this->load();
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
     */
    private function load()
    {
        $config = array();
        $file = $this->getConfigFilePath();
        if (! file_exists($file)) {
            throw new Exception("file $file is not found");
        }
        include_once($file);

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
     *  設定ファイル名を取得する
     *
     *  @return string  設定ファイルへのフルパス名
     */
    protected function getConfigFilePath()
    {
        return $this->dir . '/' . $this->filename;
    }
}
// }}}
