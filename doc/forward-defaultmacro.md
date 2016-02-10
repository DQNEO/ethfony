# 遷移時のデフォルトマクロを指定する。

- 更新履歴
  - rendererに対応させた内容に変更 (2006/11/20, いちい)

## 遷移時のデフォルトマクロを指定する。

アプリケーションにあるビューの基底クラス(app/{APPID}_ViewClass.php)の_setDefaultメソッドを利用することで、 Smartyにあらかじめ値をassignするなどの共通処理をすることができます。

なお、コントローラの _setDefaultTemplateEngine() を利用した方法は現在は推奨されません。

以下は、etc/{APPID}-ini.phpに設定した、base_urlとsite_nameをアサインする処理です。

    function _setDefault(&$renderer)
       {
           $smarty =& $renderer->getEngine();
    
           $smarty->assign('BASE_URL', $this->config->get('base_url') );
           $smarty->assign('site_name', $this->config->get('site_name') );
    
       }

これを記述することで、すべてのアクションやビューでsite_nameやbase_urlを assignするというような手間をはぶくことができます。

