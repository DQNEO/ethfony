# フォームヘルパ タグリファレンス
[フォームヘルパのページ](view-form_helper.md) で説明した通り、フォームヘルパはアクションフォームのフォーム定義を読み取り、入力フォームを自動生成してくれる優れた機能です。ここでは、フォームヘルパで使えるタグのすべてをリファレンスとして示します。

- フォームヘルパ タグリファレンス 
  - {form}{/form} ブロックタグ 
  - {form_input} 
    - パラメータの渡し方 
    - もう少し細かく 
    - 一般的なフォーム 
    - 配列で指定されたフォーム 
    - 選択肢が必要なフォーム 
  - {form_name} 
  - {form_submit} 
  - 注意事項 

| 書いた人 | mumumu | 2009-01-23 | 新規作成 |

### {form}{/form} ブロックタグ

{form}ブロックタグの一番の役割は、ブロックの中身を <form>...</form> タグで囲み、アクションフォームの定義を読み取ることです。

{form}ブロックが受け取れるパラメータは以下の通りです。

- name
  - フォーム名を指定します。ひとつのテンプレート内に複数 {form} ブロックタグを指定する場合、この指定は必須です。それぞれの名前は重複しないようにしてください。
- ethna_action
  - フォーム定義を読み取る対象のアクション名を指定します。{form}ブロック内で{form_input name="foo"}と指定されていたとき、"foo"というフォームがどのアクションで定義されているかを指定します。省略時は現在のアクションになります(フォーム値が不正で戻ってくるときなど)
  - ethna_actionで指定したアクション名で、

    <input type="hidden" name="action_XXX" value="true">

と出力されます。
- enctype
  - フォームのenctypeを指定します。'file' のみが現在指定できます。この場合、enctype="multipart/form-data" が <form> タグに出力できます。
- default
  - {form}ブロック内でのdefaultをまとめて指定するときに使います。フォーム名をkeyとする連想配列を与えます。省略時は現在のフォーム値になります。
  - **注意** : ここで指定する値は出力時にエスケープ処理が入ります。たとえばテンプレートで {form default=$form} のように指定すると、2重にエスケープされてしまうことに注意してください。省略時の値は$formではなくActionFormから直接取得しています。
- method
  - get/postを指定します。省略時はpostになります。

参考までに、パラメータ action は{form}ブロックは理解しないので、

    {form action="index.php"}

とすると<form>タグのパラメータとしてそのままわたされて、

    <form action="index.php">

と出力されます。

### {form_input}

#### パラメータの渡し方

{form_input}タグは以下の二つでパラメータを受けとることができます。

- ActionForm での定義
- Smarty テンプレートでの指定

このうち、いわゆるMVC的なビューに属するものはテンプレートで、そうでないものはActionForm で指定するようにしているつもりですが、実用上の簡便さから両者が混ざっている部分もあります。基 本的には以下の考え方で作られています。

- アプリケーションのコンテキストによって決まるものはActionForm
  - <select>タグの選択肢をデータベースから取得するような場合
- 表示の問題でしかないもの、htmlのタグ生成に直接渡されるパラメータはテンプレート
  - style指定などはヘルパーは理解せずそのままhtmlのタグにパラメータとして渡されます。

#### もう少し細かく

正確には、タグを生成するときに

- ActionFormでの定義のうち、タグごとに決められたパラメータを解釈する
- テンプレートで渡されたパラメータのうち、タグごとに決められたパラメータを解釈し、 のこりのパラメータについてはhtmlのパラメータにそのまま渡す

という流れになります。どちらでも指定できるものについては、次の順に評価され、後ろにあるものほど優先されます。

- ActionFormでの指定
- {form_input}を囲む{form}ブロックでの指定
- {form}での指定

#### 一般的なフォーム

ほとんどの場合、フォームの種類(FORM_TYPE)に従い対応するhtmlタグを出力するだけです。

パラメータとして指定できるdefault, valueは、どちらもフォームの値を指定するものですが、次のような違いがあります。

- valueはその値が編集されることを期待しない場合に指定します。hiddenやbuttonなどです。
- defaultは、編集されるフォームに初期値を与える場合に指定します。valueが指定されている場合はdefaultよりも優先されます。

#### 配列で指定されたフォーム

選択肢が必要なフォーム以外で、たとえばふつうのテキスト入力フォームが配列で指定されている場合

    $form = array(
        "foo" => array(
             'type' => VAR_TYPE_STRING,
             'form_type' => FORM_TYPE_TEXT,
             'name' => '3つ入力してね',
        ),
    );

のように定義して、

    {form_input name="foo"}
    {form_input name="foo"}
    {form_input name="foo"}

と3つ{form_input}を並べると<input>タグが3つ生成されます。defaultが配列で指定されている場合、上から順にdefaultの値を埋めていきます。

#### 選択肢が必要なフォーム

選択肢が必要なフォームはFORM_TYPE_SELECT, FORM_TYPE_CHECKBOX, FORM_TYPE_RADIOの3つがあります。

選択肢はActionForm (またはテンプレート) で'option'パラメータによって指定します。ActionForm で、以下の option の \*\*\* の部分に以下の3通りが指定できます。

    $form = array(
        "foo" => array(
             'type' => VAR_TYPE_INT,
             'form_type' => FORM_TYPE_SELECT,
             'name' => '次から選んでください:',
             'option' => ***,
        ),
    );

- array (連想配列)
  - keyが選択肢の実際の値、valueが表示される値
- ',' を含まない string
  - そのActionFormのプロパティもしくは関数の返す値(配列)を選択肢にします。
- ',' で区切られた2つの string ('address,prefecture'など)
  - 'address'マネージャの'prefecture'プロパティの値一覧を選択肢にします。アプリケーションマネージャ(AppManager)のgetAttrList()関数を利用しています。

またselectタグについてはパラメータemptyoptionが指定できます。これは、選択肢のどれも選択されていないときに表示する値を指定できます(value=""となります)。

    {form_input emptyoption="選択してね"}

### {form_name}

フォーム定義の 'name' 属性の値をそのまま出力します。

    $form = array(
        "foo" => array(
             'type' => VAR_TYPE_STRING,
             'form_type' => FORM_TYPE_TEXT,
             'name' => '3つ入力してね',
        ),
    );

たとえば上記のようなフォーム定義があったとして、

    {form_name name="foo"}

とテンプレートに書くと、以下のように出力されます。

    3つ入力してね

### {form_submit}

submitボタンだけを作りたい場合、(テンプレートにそのままhtmlタグを書いてもいいですが)送信先のActionFormにボタンの定義をするのは面倒なので、{form_submit}を使って定義によらずに送信ボタンを出力することができます。

    {form_submit value="送信するよ!"}

### 注意事項

[{form}ブロックタグ](view-form_helper-ref.md#rbb7b355)の項にも書きましたが、タグを出力するときにまと めてエスケープ処理が入るので、パラメータとして指定する値はエスケープしないように気をつけてください。

