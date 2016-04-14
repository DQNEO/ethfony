<?php
// vim: foldmethod=marker
/**
 *  Error.php
 *
 *  @author     Masaki Fujimoto <fujimoto@php.net>
 *  @license    http://www.opensource.org/licenses/bsd-license.php The BSD License
 *  @package    Ethna
 *  @version    $Id$
 */

// {{{ Ethna_Error
/**
 *  エラークラス
 *
 *  @author     Masaki Fujimoto <fujimoto@php.net>
 *  @access     public
 *  @package    Ethna
 */
class Ethna_Error
{
    /**#@+
     *  @access private
     */

    /** @protected    object  Ethna_I18N  i18nオブジェクト */
    protected $i18n;

    /** @protected    object  Ethna_Logger    loggerオブジェクト */
    protected $logger;

    /** @protected    string  エラーメッセージ */
    protected $message;

    /** @protected    integer エラーコード */
    protected $code;

    /** @protected    integer エラーモード */
    protected $mode;

    /** @protected    array   エラーモード依存のオプション */
    protected $options;

    /** @protected    string  ユーザー定義もしくはデバッグ関連の追加情報を記した文字列。 */
    protected $userinfo;

    /**#@-*/

    /**
     *  Ethna_Errorクラスのコンストラクタ
     *  $userinfo は第5引数に設定すること。
     *
     *  @access public
     *  @param  string  $message            エラーメッセージ
     *  @param  int     $code               エラーコード
     *  @param  int     $mode               エラーモード(Ethna_Errorはコールバックを
     *                                      常に使用するので実質無視される)
     *  @param  array   $options            エラーモード依存のオプション
     *  @param  array   $userinfo           エラー追加情報($options より後の全ての引数)
     *  @see http://pear.php.net/manual/ja/core.pear.pear-error.pear-error.php
     */
    public function __construct($message = null, $code = null, $mode = null, $options = null)
    {
        $container = Ethna_Container::getInstance();
        if ($container !== null) {
            $this->i18n = $container->getI18N();
        }

        // $options 以降の引数 -> $userinfo
        if (func_num_args() > 4) {
            $userinfo = array_slice(func_get_args(), 4);
            if (count($userinfo) == 1) {
                if (is_array($userinfo[0])) {
                    $this->userinfo = $userinfo[0];
                } else if (is_null($userinfo[0])) {
                    $this->userinfo = array();
                }
            } else {
                $this->userinfo = $userinfo[0];
            }
        } else {
            $this->userinfo = array();
        }

        // メッセージ補正処理 ($message)
        if (is_null($message)) {
            // $codeからメッセージを取得する
            $message = $this->getErrorMessage($code);
            if (is_null($message)) {
                $message = 'unknown error';
            }
        }
        $this->message = $message;

        //  その他メンバ変数設定
        $this->code = $code;
        $this->mode = $mode;
        $this->options = $options;
        $this->level = ($this->options === NULL) ? E_USER_NOTICE : $options;

        //  Ethnaフレームワークのエラーハンドラ(callback)
        Ethna::handleError($this);
    }

    /**
     *  エラーメッセージを取得する
     *
     *  @access public
     *  @param  int     $code       エラーコード
     *  @return string  エラーメッセージ
     */
    private function getErrorMessage($code)
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
     * エラーオブジェクトに関連付けられたエラーコードを返します。
     *
     * @return integer - エラー番号
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     *  levelへのアクセサ(R)
     *
     *  @access public
     *  @return int     エラーレベル
     */
    public function getLevel()
    {
        return $this->level;
    }

    /**
     *  messageへのアクセサ(R)
     *
     *  以下の処理を行う
     *  - エラーメッセージのi18n処理
     *  - $userinfoとして渡されたデータによるvsprintf()処理
     *
     *  @access public
     *  @return string  エラーメッセージ
     */
    public function getMessage()
    {
        $tmp_message = $this->i18n ? $this->i18n->get($this->message) : $this->message;
        $tmp_userinfo = to_array($this->userinfo);
        $tmp_message_arg_list = array();
        for ($i = 0; $i < count($tmp_userinfo); $i++) {
            $tmp_message_arg_list[] = $this->i18n ? $this->i18n->get($tmp_userinfo[$i]) : $tmp_userinfo[$i];
        }
        return vsprintf($tmp_message, $tmp_message_arg_list);
    }

    /**
     *  エラー追加情報へのアクセサ(R)
     *
     *  エラー追加情報配列の個々のエントリへのアクセスをサポート
     *
     *  @access public
     *  @param  int     $n      エラー追加情報のインデックス(省略可)
     *  @return mixed   message引数
     */
    public function getUserInfo($n = null)
    {
        if (is_null($n)) {
            return $this->userinfo;
        }

        if (isset($this->userinfo[$n])) {
            return $this->userinfo[$n];
        } else {
            return null;
        }
    }

    /**
     *  エラー追加情報へのアクセサ(W)
     *
     *  @access public
     *  @param  string  $info   追加するエラー情報
     */
    public function addUserInfo($info)
    {
        $this->userinfo[] = $info;
    }

    public function __toString()
    {
        return $this->getMessage();
    }
}
// }}}

