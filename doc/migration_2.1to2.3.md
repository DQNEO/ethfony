# Ethna 2.1.0 から 2.3.0 への移行ガイド
Ethna 2.1.x で作った古いプロジェクトを新しいバージョン 2.3.x 系に対応させるためのガイドラインです。(これに従えばうまくいく、というわけではありません。必ずバックアップを用意した上で、確認しながら作業するようにしてください。)

※ Ethna 2.3.0 から 2.5.0 への移行については、 [こちら](ethna-document-dev_guide-misc-migrate_project230to250.md "ethna-document-dev_guide-misc-migrate_project230to250 (737d)") を御覧下さい。

- Ethna 2.1.0 から 2.3.0 への移行ガイド 
  - タグの説明 
    - [必須]最新バージョンで追加されたファイルをインストールする 
    - [必須]アプリケーションの基底クラスを使うように変更する 
    - [必須] アプリケーションマネージャのアクセス方法を変更 
    - [必須] Ethna_Renderer導入への対応 
    - [任意] Ethna_Pluginで導入されたものを使う 
    - [使っていれば必須] Ethna_Pluginで廃止されたものを 
    - [任意] Ethna_UrlHandlerを使う 

| 書いた人 | いちい | | |
| 書いた人 | halt | 2006-11-22 20 | 勝手に編集 |

**この内容はまだ途中です!!**

### タグの説明

- [必須]は、やっておかないとダメだよね。[任意]を実現するには[必須]が必要
- [任意]は、やる事で上位バージョンの恩恵を受ける事ができるけどやらなくても動くよ。
- [下位互換]は、これをやる事で最新のEthnaを使っていても前と下位と同じ(か似たような)挙動になるよ。

#### [必須]最新バージョンで追加されたファイルをインストールする

古いプロジェクトを上書きするようにadd-projectしてください。これにより、新しいEthnaのプロジェクトファイルに必要だが存在しないファイルのみ新規に追加されます。

    $ ls
    sampleapp
    $ ethna add-project sampleapp

#### [必須]アプリケーションの基底クラスを使うように変更する

アクションやビューに基底となるクラスを追加する事でアプリケーション全体で利用されるアクションやビューのふるまいを制御する事ができるようになります。

- すべてのActionFormの継承先変更

    -class Sampleapp_Form_Index extends Ethna_ActionForm
    +class Sampleapp_Form_Index extends Sampleapp_ActionForm

- すべてのActionの継承先変更

    -class Sampleapp_Action_Index extends Ethna_ActionClass
    +class Sampleapp_Action_Index extends Sampleapp_ActionClass

- すべてのViewの継承先変更

    -class Sampleapp_View_Index extends Ethna_ViewClass
    +class Sampleapp_View_Index extends Sampleapp_ViewClass

全てのアクション、ビューのファイルを変更しないといけないので、 "extends Ethna_" などで検索するとかして変更忘れがないように気をつけてください(変更忘れは発見しにくいバグのもとになりやすいです)。

- アプリケーションの基底クラスをコントローラに追加する

- include_onceの追加

    include_once('Ethna/Ethna.php');
     include_once('Sampleapp_Error.php');
    +include_once('Sampleapp_ActionClass.php');
    +include_once('Sampleapp_ActionForm.php');
    +include_once('Sampleapp_ViewClass.php');
    
    /**
     *

- クラスの追加

    'config' => 'Ethna_Config',
            'db' => 'Ethna_DB_PEAR',
            'error' => 'Ethna_ActionError',
    - 'form' => 'Ethna_ActionForm',
    + 'form' => 'Sampleapp_ActionForm',
            'i18n' => 'Ethna_I18N',
            'logger' => 'Ethna_Logger',
    + 'plugin' => 'Ethna_Plugin',
            'session' => 'Ethna_Session',
            'sql' => 'Ethna_AppSQL',
    - 'view' => 'Ethna_ViewClass',
    + 'view' => 'Sampleapp_ViewClass',
    + 'renderer' => 'Ethna_Renderer_Smarty',
    + 'url_handler' => 'Sampleapp_UrlHandler',

#### [必須] アプリケーションマネージャのアクセス方法を変更

class factory(Ethna_ClassFactory)の汎用化により、Ethnaで登場する多くのクラスがEthna_ClassFactory経由で取得されるようになりました。これにより、クラスのソースファイが統一された基準で自動的に検索され、統一されたインタフェースでオブジェクトを取得できるようになりました(くわしくは [クラスファクトリ](ethna-document-dev_guide-classfactory.md "ethna-document-dev_guide-classfactory (1240d)")を参照)。

これに伴い、いままでアクション(Ethna_ActionClassの継承クラス)、ビュー(Ethna_ViewClass)、アプリケーションオブジェクト(Ethna_AppObject)では自動的にオブジェクトのプロパティに設定されていましたが、これが廃止されました。

コントローラの

    var $manager = array(
        'un' => 'User',
    );

といった記述をして、アクションなどで

    $this->um->doSomething();

などとしている場合、次のように書き換えてください。

    $um =& $this->backend->getManager('User');
    $um->doSomething();

なお、アプリケーションマネージャはオブジェクト作成時にデータベースに(設定されていれば)接続しますが、これによって必要時に初めて接続するように変更されたことになります。

#### [必須] Ethna_Renderer導入への対応

これまでEthnaはSmartyに依存した作りになっていましたが、Ethna_Rendererの導入により、レンダラというレイヤを挟んで1段抽象化されました。まだSmartyに依存した部分がコントローラなどにいくつか残っていますが、今後改良される予定です。

大きな違いとして、Ethna_ViewClassがSmartyオブジェクトを直接持つのを止めて、RendererオブジェクトがSmartyを持つようにし、さらにRendererオブジェクトはEthna_ClassFactoryが管理するように変更されました。アプリのビュークラスの中で直接Smartyオブジェクトを操作していた場合、

    $this->smarty->assign('foo', $bar);

次のように修正してください。

    $renderer =& $this->_getRenderer();
    $smarty =& $renderer->getEngine();
    $smarty->assign('foo', $bar);

一度$rendererを取得するのが手間ですが、assignのような、多くのレンダラに共通であろうものについてはEthna_Rendererクラスでメソッドが用意されています。

    $renderer =& $this->_getRenderer();
    $renderer->setProp('foo', $bar);

ここで、setProp()は各テンプレートエンジンごとの「レンダラに値を設定」するプロキシメソッドです。詳細は [Ethna_Rendererの使いかた](ethna-document-dev_guide-renderer.md "ethna-document-dev_guide-renderer (1240d)")を参照してください。

#### [任意] Ethna_Pluginで導入されたものを使う

新たに追加された機能で、Ethna_Pluginを利用したものについてまとめておきます。おもに、似たような枠組でさまざまな機能を用意したいもの、アプリケーション側で手軽に拡張を追加したくなるようなものがプラグイン化されています。詳しくは各説明ページと [Ethna_Pluginのつかいかた](ethna-document-dev_guide-plugin.md "ethna-document-dev_guide-plugin (737d)")を参照してください。

- Cachemanager
  - キャッシュ機構です。(ドキュメント未整備)
- Csrf
  - CSRF対策のためのプラグイン。 [クロスサイトリクエストフォージェリの対策コードについて](ethna-document-dev_guide-csrf.md "ethna-document-dev_guide-csrf (1240d)")を参照。
- Filter
  - Ethna_Filterがプラグインになったものです。 [フィルタチェインを使用する](ethna-document-dev_guide-app-filterchain.md "ethna-document-dev_guide-app-filterchain (1240d)")を参照(内容が古いまま)
- Validator
  - フォーム値の検証をするプラグインです。 [フォーム値の自動検証を行う(プラグイン編)](ethna-document-dev_guide-form-validate_with_plugin.md "ethna-document-dev_guide-form-validate_with_plugin (513d)")を参照。
- Logwriter
  - ログ出力のプラグインです。 [ログ](ethna-document-dev_guide-log.md "ethna-document-dev_guide-log (874d)")を参照。

#### [使っていれば必須] Ethna_Pluginで廃止されたものを

Ethna_Plugin導入に伴い、フィルタ、ethnaコマンドのハンドラなど、多くのものがプラグイン化されました。アプリ側で対象となるクラスを直接使っていた場合に修正が必要になりますが、そのような場面は非常に少ないと思いますので[任意]としました。

- Ethna_Filter (実行時フィルタ、ExecutionTimeなどのこと)
  - Plugin/Ethna_Plugin_Filter.php に移行しました。後方互換性のため Ethna_Filter.php はそのまま残っていますが、将来的に廃止される予定なのでプラグインへ移行しておくことをお薦めします。

- Ethna_LogWriter
  - 廃止され、全面的に Plugin/Ethna_Plugin_Logwriter.php に移行しました。 $ETHNA_HOME/class/LogWriter/ 以下に自作の Logwriter を作っていた場合は、プラグインへの移行作業が必要になります。

- Ethna_CacheManager
  - 実装の大部分がEthna_Plugin_Cachemanagerに移動しました。Ethna_CacheManager.php は残っていますが、プラグインを呼び出すだけになりました。

- Ethna_SkeltonGenerator
  - 廃止され、Ethna_Generator.php と Plugin/Ethna_Plugin_Generator.php に移行しました。

- Ethna_Handle
  - Plugin/Ethna_Plugin_Handle.php へのプラグイン化とともにハンドラ自体も改良されたため、かなり内容が変わっています。

#### [任意] Ethna_UrlHandlerを使う

Ethna-2.3.0の新機能の目玉の一つはEthna_UrlHandlerの導入です。

エントリポイントに実行したいアクションを指示するために、これまでは

    http://example.jp/index.php?action_foo=true&param=bar

のように "action_アクション名" という名前のリクエスト変数を設定する必要がありました。この挙動はコントローラの _getActionName_Form() というメソッドをオーバーライドすることで変更できましたが、必ずしもわかりやすいものではありませんでした。

Ethna_UrlHandlerクラスはエントリポイント、path infoと、アクション名、リクエストパラメータとの対応を定義することで、相互の変換を可能にするものです。たとえば

    http://example.jp/index.php/action/foo/param/bar

というURIと

- アクション: foo
- パラメータ: param=bar という情報とを相互に変換することができます。

詳細については、 [PATH_INFOを使ったRequest-URIからのパラメータの取得](ethna-document-dev_guide-urlhandler.md "ethna-document-dev_guide-urlhandler (926d)")を参照してください。

