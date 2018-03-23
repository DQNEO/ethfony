<?php
// vim: foldmethod=marker
/**
 *  ActionError.php
 *
 *  @author     Masaki Fujimoto <fujimoto@php.net>
 *  @license    http://www.opensource.org/licenses/bsd-license.php The BSD License
 *  @package    Ethna
 *  @version    $Id$
 */

// {{{ Ethna_ActionError
/**
 *  アプリケーションエラー管理クラス
 *
 *  @access     public
 *  @author     Masaki Fujimoto <fujimoto@php.net>
 *  @package    Ethna
 *  @todo   配列フォームを扱えるようにする
 */
class Ethna_ActionError
{
    /**#@+
     *  @access private
     */

    /** @protected    array   エラーオブジェクトの一覧 */
    protected $error_list = array();

    /** @protected   Ethna_ActionForm    アクションフォームオブジェクト */
    public $action_form;

    /** @protected   Ethna_Logger        ログオブジェクト */
    protected $logger;
    /**#@-*/

    /**
     *  Ethna_ActionErrorクラスのコンストラクタ
     *
     */
    public function __construct(Ethna_Logger $logger)
    {
        $this->logger = $logger;
    }

    /**
     *  エラーオブジェクトを生成/追加する
     *
     *  @access public
     *  @param  string  $name       エラーの発生したフォーム項目名(不要ならnull)
     *  @param  string  $message    i18n翻訳後のエラーメッセージ
     *  @param  int     $code       エラーコード
     *  @return Ethna_Error エラーオブジェクト
     */
    public function add($name, $message, $code = null)
    {
        if (func_num_args() > 3) {
            $userinfo = array_slice(func_get_args(), 3);
            $error = Ethna::raiseNotice($message, $code, $userinfo);
        } else {
            $error = Ethna::raiseNotice($message, $code);
        }
        $this->addObject($name, $error);
        return $error;
    }

    /**
     *  Ethna_Errorオブジェクトを追加する
     *
     */
    public function addObject(?string $name, Ethna_Error $error)
    {
        $elt = array();
        $elt['name'] = $name;
        $elt['object'] = $error;
        $this->error_list[] = $elt;

    }

    /**
     *  エラーオブジェクトの数を返す
     *
     *  @access public
     *  @return int     エラーオブジェクトの数
     */
    public function count()
    {
        return count($this->error_list);
    }

    /**
     *  エラーオブジェクトの数を返す(count()メソッドのエイリアス)
     *
     *  @access public
     *  @return int     エラーオブジェクトの数
     */
    public function length()
    {
        return count($this->error_list);
    }

    /**
     *  登録されたエラーオブジェクトを全て削除する
     *
     *  @access public
     */
    public function clear()
    {
        $this->error_list = array();
    }

    /**
     *  指定されたフォーム項目にエラーが発生しているかどうかを返す
     *
     *  @access public
     *  @param  string  $name   フォーム項目名
     *  @return bool    true:エラーが発生している false:エラーが発生していない
     */
    public function isError($name)
    {
        foreach ($this->error_list as $error) {
            if (strcasecmp($error['name'], $name) == 0) {
                return true;
            }
        }
        return false;
    }

    /**
     *  指定されたフォーム項目に対応するエラーメッセージを返す
     *
     *  @access public
     *  @param  string  $name   フォーム項目名
     *  @return string  エラーメッセージ(エラーが無い場合はnull)
     */
    function getMessage($name)
    {
        foreach ($this->error_list as $error) {
            if (strcasecmp($error['name'], $name) == 0) {
                return $this->getMessageByEntry($error);
            }
        }
        return null;
    }

    /**
     *  エラーオブジェクトを配列にして返す
     *
     *  @access public
     *  @return array   エラーオブジェクトの配列
     */
    function getErrorList()
    {
        return $this->error_list;
    }

    /**
     *  エラーメッセージを配列にして返す
     *
     *  @access public
     *  @return array   エラーメッセージの配列
     */
    function getMessageList()
    {
        $message_list = array();

        foreach ($this->error_list as $error) {
            $message_list[] = $this->getMessageByEntry($error);
        }
        return $message_list;
    }

    /**
     *  アプリケーションエラーメッセージを取得する
     *
     *  @param  array   エラーエントリ
     *  @return string  エラーメッセージ
     */
    protected function getMessageByEntry(&$error)
    {
        $form_name = $this->action_form->getName($error['name']);
        return str_replace("{form}", _et($form_name), $error['object']->getMessage());
    }


}
// }}}
