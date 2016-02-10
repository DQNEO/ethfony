# フォームへルパ
フォームヘルパとは、テンプレートでフォーム(<form>, <input>タグなど)を書くときに、 アクションフォームであらかじめ定義された情報から適切なタグを自動的に生成し、フォームを簡単に記述することができる機能です。

少し複雑な部分もありますが、アクションフォームと連携する部分を理解すれば、これほど強力な武器はありません。現状は Smartyの利用を前提としていますが、将来的には他のテンプレートエンジンや、素のPHPへの対応も考えています。

- フォームへルパ 
  - はじめに 
  - フォームヘルパの基本と機能 
    - (入力)フォームタグの自動生成 
    - フォーム値の補完 
  - 基本的な書き方 
    - 選択肢がないフォーム 
    - 選択肢があるフォーム 
  - フォーム定義が配列の場合 
  - 生成されるHTML に style 等のパラメータを指定したい場合 
    - パラメータを指定する場合の注意事項 
  - フォーム値の補完の詳細 
  - 複数 {form}{/form} を指定する場合の注意事項 
    - name 属性を必ず指定する 
    - default 属性を指定する 
  - サンプルコード 
  - フォームヘルパで使用できるすべてのタグ 
  - TODO 

| 書いた人 | ------ | ---------- | 新規作成 |
| 書いた人 | mumumu | 2009-01-22 | 最新版に追随する形で全面的に修正 |

### はじめに

このページの説明では、以下のようにプロジェクトとアクションクラス、テンプレートを作成し、フォーム定義を行ったとします。

    $ ethna add-project sample
    $ cd sample
    $ ethna add-action formhelper
    $ ethna add-view -t formhelper

    //
    // app/action/Formhelper.php
    //
    class Sample_Form_Formhelper extends Sample_ActionForm
    {
        var $form = array(
            'sample' => array(
                'type' => VAR_TYPE_STRING,
                'form_type' => FORM_TYPE_TEXT,
                'name' => 'サンプルテキストフォーム',
             ),
        );
    }

### フォームヘルパの基本と機能

#### (入力)フォームタグの自動生成

テンプレートに以下のように書くことで、自動的に上記のアクションフォームの定義を読み取って、外側の <form></form> タグ及び、入力テキストフォームとSubmitボタンを自動生成することができます。

    {* formhelper.tpl *}
    {form ethna_action="formhelper"}
      サンプル:{form_input name="sample"}<br>
      {form_submit value="Submit!"}
    {/form}

出力は以下のようなものです。

    <form method="post">
      <input type="hidden" name="action_formhelper" value="true">
      サンプル:<input type="text" name="sample" value="" /><br>
      <input value="Submit!" type="submit" />
    </form>

このように、{form} {/form} ブロックタグを外側に配置し、その内側で {form_input} タグや {form_submit} タグを使うのが基本になります。{form} ブロックタグには、 ethna_action という属性に、フォーム定義を読み取らせるアクション名を指定します。 ethna_action が指定されない場合は、現在のアクションにあるフォーム定義が使われます。

hidden タグも生成されていますが、それはこの後で説明します。

アクションフォームのフォーム定義で特に重要なのは以下の部分です。form_type の値によって、生成される入力フォームが決まります。ここでは、FORM_TYPE_TEXT が指定されているため、テキスト入力フォームが生成されます。

    'form_type' => FORM_TYPE_TEXT,

#### フォーム値の補完

ethna_action と現在のアクションが同じ場合、フォームヘルパは自動的に Submit した値を入力フォームに補完してくれます。具体的には、Submitしたあとに同じ画面に戻 ってくるときが典型例です。

これは、Submit した値を検証した結果エラーになって入力をやり直させる場合に、ユーザ が同じことを入力させなくて良いようにとのフレームワークの配慮です。

先ほどの具体例で、hidden タグが以下のように出力されていました。これは、ethna_action を指定した場合に、ethna_action と submit 先のアクションが同じになるようにする ためです。

    <form method="post">
      <input type="hidden" name="action_formhelper" value="true">
      (... 以下略)
    </form>

### 基本的な書き方

既に述べたように、フォームヘルパの基本的な使い方は、外側に {form} {/form} ブロッ クタグを外側に配置し、その内側で {form_input} タグや {form_submit} タグを使 うのが基本になります。

但し、フォーム定義によって、出力されるタグが異なってきます。ここでは、それについて具体例を交えて説明します。

#### 選択肢がないフォーム

選択肢がないフォームとは、以下を指します。

| form_type に指定する定数 | 生成されるコントロール名 |
| FORM_TYPE_TEXT | テキストボックス |
| FORM_TYPE_PASSWORD | パスワード |
| FORM_TYPE_TEXTAREA | テキストエリア |
| FORM_TYPE_BUTTON | ボタン |
| FORM_TYPE_FILE | ファイル |
| FORM_TYPE_HIDDEN | 隠しコントロール |

選択肢がないフォームの場合は、フォーム定義の form_type に、上記の該当する値を 指定して、テンプレート側で {form_input} の name 属性に、フォーム定義の名前 を指定するだけです。

たとえばテキストエリアの場合は、以下のようになります。

    // フォーム定義
    var $form = array(
        'sample' => array( // ここの名前(sample) を form_input の name属性に指定する
            'type' => VAR_TYPE_STRING,
            'form_type' => FORM_TYPE_TEXTAREA,
            'name' => 'サンプルテキストエリア',
        ),
    );

テンプレートを以下のように指定します。

    {form_input name="sample"}

出力は以下のようになります。

    <textarea name="sample" value=""></textarea>

他の form_type の場合も、対応したタグがそれぞれ出力されます。

#### 選択肢があるフォーム

HTML で指定できるフォーム要素の中には、選択肢を作ることができるものがあります。 この場合、微妙に扱いが異なります。選択肢が指定できるフォームには以下があります。

| form_type に指定する定数 | 生成されるコントロール名 |
| FORM_TYPE_SELECT | セレクトボックス |
| FORM_TYPE_RADIO | ラジオボタン |
| FORM_TYPE_CHECKBOX | チェックボックス |

選択肢を複数使って1つのフォーム(コントロール)をつくる SELECT タグの場合、選 択肢を次のように指定できます。form_type の値と、選択肢に option で配列を 指定しているのに注目してください。

option には、input タグの value 値をキーにして、表示するラベルを値にした 配列を指定します。

    $form = array(
        'sample' => array(
             'type' => VAR_TYPE_INT,
             'form_type' => FORM_TYPE_SELECT,
             'name' => '選んでね',
             'option' => array(1 => '1番目', 2 => '2番目'),
        ),
    );

テンプレートでは テキストボックスの場合と同様に

    {form_input name="sample"}

とすれば、以下のように出力されます。

    <select name="sample">
      <option value="1">1番目</option>
      <option value="2">2番目</option>
    </select>

フォーム定義に FORM_TYPE_RADIO を指定した場合、以下のように出力されます。

    <label for="sample1_1">
      <input type="radio" name="sample" value="1" id="sample1_1" />1番目
    </label>
    <label for="sample1_2">
      <input type="radio" name="sample" value="2" id="sample1_2" />2番目
    </label>

フォーム定義に FORM_TYPE_CHECKBOX を指定した場合、以下のように出力されます。

    <label for="sample2_1">
      <input type="checkbox" name="sample" value="1" id="sample2_1" />1番目
    </label>
    <label for="sample2_2">
      <input type="checkbox" name="sample" value="2" id="sample2_2" />2番目
    </label>

### フォーム定義が配列の場合

選択肢が必要なフォーム以外、たとえばテキスト入力フォームのフォーム定義が配列で指定されている場合、 たとえば以下のように定義したとします。

    $form = array(
        'sample' => array(
             'type' => array(VAR_TYPE_STRING), // 配列指定のフォーム定義
             'form_type' => FORM_TYPE_TEXT,
             'name' => '3つ入力してね',
        ),
    );

そして、テンプレートで以下のように指定すると、配列向けのフォームが自動生成されます。

    {form_input name="sample"}
    {form_input name="sample"}
    {form_input name="sample"}

つまり、出力は以下のようになります。{form_input} を並べた数だけ、配列向けのフォームが自動 生成されるということです。

    <input type="text" name="sample[]" value="" />
     <input type="text" name="sample[]" value="" />
     <input type="text" name="sample[]" value="" />

### 生成されるHTML に style 等のパラメータを指定したい場合

これまで説明してきた、「自動生成されるHTML」に、css の style 属性や、フォームの size 属性等の HTMLな属性を付け加えたいという要求は自然なことです。フォームヘルパでは、 [フォームヘルパ タグリファレンス](view-form_helper-ref.md) にあるパラメータ以外のパラメータを渡すと、HTML の属性としてそのまま埋め込まれるようになっています。

これを利用すれば、任意のHTML の属性を埋め込むことが出来ます。

以下は、sample という名前の入力フォームにスタイルを指定する例です。

    {* 境界線のスタイルを青、2px、1本線に指定する *}
    {form_input name="sample" style="border: solid 2px #0000ff"}
    
    {* superstyle という CSSクラス名を指定する *}
    {form_input class="superstyle"}

「使用例」にも、この性質を利用したサンプルがあります。

#### パラメータを指定する場合の注意事項

フォームヘルパでは、HTMLタグを出力するときにまとめてエスケープ処理が入ります。パラメータとして指定する値はエスケープしないように気をつけてください。

### フォーム値の補完の詳細

入力フォームを自動生成するために使う {form_input} は、設定する値(<input type="..." value="hoge" /> の hoge の部分) の属性として default と value 属性が用意されています。

    {form_input name="sample" default="1"}
    {form_input name="sample" value="1"}

- valueはその値が編集されることを期待しない場合に指定します。また、submitされて戻ってきた場合は、このvalue属性に値が補完されます。
- defaultは、編集されるフォームに初期値を与える場合に指定します。valueが指定されている場合はdefaultよりも優先されます。

{form_input} タグの default 属性が配列で指定された場合、上から順に 同じ名前のdefaultの値を埋めていきます。これは、以下のようにフォーム定義が配列の場合に有用です。

    // Viewやアクション側で以下のように設定する
    $this->af->setApp('default', 
                       array('a', 'b', 'c')
    );

    {* テンプレート側では以下のように値が補完される
      {form_input name="sample[]" default="$app.default"} {* a が補完される *}
      {form_input name="sample[]" default="$app.default"} {* b が補完される *}
      {form_input name="sample[]" default="$app.default"} {* c が補完される *}

また、{form}{/form} ブロックタグの default 属性が以下のように指定されると、その値はこのブロックタグで囲まれた {form_input} のdefault属性すべてに適用されます。例を以下に示します。

    // アクションや View で以下のように指定してみる
    $this->af->setApp('sample',
                      array(
                          'sample' => 'a',
                          'sample1' => 'b',
                      )
    );

    {* テンプレート側の例 *}
    {* value属性は default属性に優先するので、 *}
    {* エラーで戻ってきた場合は submit された値が補完される *}
    {form action="ethna_action" default=$app.sample}
        {form_input name='sample'} {* default 属性に a という値が補完される *}
        {form_input name='sample1'} {* default 属性に b という値が補完される *}
    {/form}

### 複数 {form}{/form} を指定する場合の注意事項

    以下の記述は、Ethna 2.5.0 preview3 以降に当てはまります。

#### name 属性を必ず指定する

1テンプレートに {form}{/form} ブロックタグを指定する場合は、少し注意が必要です。それは {form} タグに必ず name 属性を「重複しない」名前を指定することです。これは、エラー等で同じ画面に戻ってきた場合に、submit したフォーム値を補完フォームを区別するためです。

    {form name="hoge1"}{/form}
    {form name="hoge2"}{/form}

この場合は、以下のように ethna_fid というフォームを識別するための隠しフィールドが出力されます。\*1

    <form name="hoge1">
      <input type="hidden" name="ethna_fid" value="hoge1" />
    </form>
    <form name="hoge2">
      <input type="hidden" name="ethna_fid" value="hoge2" />
    </form>

#### default 属性を指定する

複数のフォームを並べていくと、{form_input} タグの value や default 属性の指定が非常に大変になります。その場合こそ、{form}{/form} タグの default 属性の使用を検討すべきです。

### サンプルコード

[フォームヘルパ サンプル集](view-form_helper-samples.md) のページを参照してください。

### フォームヘルパで使用できるすべてのタグ

[フォームヘルパ タグリファレンス](view-form_helper-ref.md) のページを参照してください。

### TODO

実装の大部分はEthna_Renderer_SmartyではなくEthna_ViewClassが持っているため、本当はSmartyに限らずさまざまなレンダラで利用可能なはずです。しかし、現時点ではSmartyしかレンダラが用意されていません。素のPHPや、flexy等、他のテンプレートエンジンもサポートすべきだと考えています。


* * *
\*1Ethna 2.3.5 以前は、1テンプレートに複数 {form}{/form} ブロックタグを指定した場合は、エラーで戻ってきた場合等に、すべてのフォームに submit した値を補完するバグがありました。2.5.0 preview3 以降、この問題は改善しています。  

