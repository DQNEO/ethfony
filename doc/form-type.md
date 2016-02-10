# ファイルや配列にアクセスする
  - ファイルへのアクセス 
  - 配列へのアクセス 
    - ファイルの配列の場合 
  - 多次元配列 

## ファイルや配列にアクセスする

### ファイルへのアクセス

アップロードされたファイルへのアクセスは、その処理の大部分をPHPが行ってくれるので至って簡単です。

まず、 [フォーム値にアクセスする](form-overview.md)で記述した場合と同様に、フォーム値を定義します。

    'sample_file' => array(
        'type' => VAR_TYPE_FILE,
    ),

ここで大事なのは、'type'属性にVAR_TYPE_FILEを指定していることで、ここをVAR_TYPE_STRING等にしているとアップロードされたファイルにアクセスできませんのでご注意下さい。

次にファイルをアップロードするためのテンプレートを記述します。ここでは特別な点はなにもありません。

    ...
    <form method="post" enctype="multipart/form-data">
     <input type="file" name="sample_file">
     <input type="submit">
    </form>
    ...

後はprepare()あるいはperform()メソッドでアクションフォームを通じてフォーム値にアクセスするだけです。アップロードされたファイルは、PHPの$_FILES変数と同様にアクセスが可能です。従って:

    perform()
    {
        var_dump($this->af->get('sample_file'));
    }

とすると

    array(5) {
      ["name"]=>
      string(10) "sample.gif"
      ["type"]=>
      string(9) "image/gif"
      ["tmp_name"]=>
      string(14) "/tmp/php3PxT99"
      ["error"]=>
      int(0)
      ["size"]=>
      int(220)
    }

というような結果となります。各要素の詳細については [PHPマニュアルのファイルアップロードに関するセクション](http://jp2.php.net/manual/ja/features.file-upload.php)を御覧下さい。

また、フォーム値自体が送信されていない場合、

    null

となります。また、ファイルが何もアップロードされなかった場合は以下のようにerror要素に4\*1が返されます。

    array(5) {
      ["name"]=>
      string(0) ""
      ["type"]=>
      string(0) ""
      ["tmp_name"]=>
      string(0) ""
      ["error"]=>
      int(4)
      ["size"]=>
      int(0)
    }

なお、ファイルに関してもフォーム値の自動検証を利用して、必須チェック、ファイルの最大、最小サイズ等のチェックが可能です。

### 配列へのアクセス

配列を使用する場合も、特別に手間は掛かりません。まずフォーム値を以下のように定義します。

    'sample_array' => array(
        'type' => array(VAR_TYPE_STRING),
    ),

見ての通り、'type'属性に定数1つを要素にもつ配列を指定します。これにより、このフォーム値は配列であることを明示します。

テンプレートに関しても特別な点はなく、通常のPHPでのフォーム配列と同様です。

    <form method="post">
     <input type="checkbox" name="sample_array[]" value="1">
     <input type="checkbox" name="sample_array[]" value="2">
     <input type="checkbox" name="sample_array[]" value="3">
     <input type="checkbox" name="sample_array[]" value="4">
     <input type="submit">
    </form>

続いてファイル等と同様にprepare()あるいはperform()メソッドでアクションフォームを通じてフォーム値にアクセスするだけです。例えば以下のように

    perform()
    {
        var_dump($this->af->get('sample_array'));
    }

とすると

    array(2) {
      [0]=>
      string(1) "3"
      [1]=>
      string(1) "4"
    }

のように配列を取得することが出来ます。なお、フォーム値に何も入力されない、あるいはフォーム値自体が送信されなかった場合は

    null

となります。ただし、末尾のブラケット削除してフォーム値が送信された場合、その値をスカラー値として扱われます。つまり、上記の例で、

    /?sample_array=string

というアクセスがあると

    string(6) "string"

という結果になるということです。これについてはフォーム値の自動検証で抑止することが出来ます(配列指定のフォーム値にスカラー値が渡された場合に自動的にエラーとすることが出来ます)。

#### ファイルの配列の場合

$_FILESの配列とは構造が変わっています。基本的には、単一のファイルをアップロードした内容が複数並ぶだけです。たとえばふたつアップロードした場合は以下のようになります。

    array(2) {
      [0]=> array(5) {
        ["name"]=> string(11) "Sunset.jpeg"
        ["type"]=> string(10) "image/jpeg"
        ["size"]=> int(71189) 
        ["tmp_name"]=> string(14) "/tmp/php9bU0Wm"
        ["error"]=> int(0)
      }
      [1]=> array(5) {
        ["name"]=> string(11) "Sunset.jpeg"
        ["type"]=> string(10) "image/jpeg"
        ["size"]=> int(71189) 
        ["tmp_name"]=> string(14) "/tmp/php7aF1Ll"
        ["error"]=> int(0)
      }
    }

そして、複数アップロードする場合、アップロードされなかったものについては、NULLではなく、該当するフィールドのerror要素に4が設定されます。これは単一のアップロードの場合と同様です。

以下に「ひとつめのフィールド」だけアップロードし、「ふたつめのフィールド」をアップロード「しなかった」場合の例を示します。

    array(2) {
      [0]=> array(5) {
        ["name"]=> string(11) "Sunset.jpeg"
        ["type"]=> string(10) "image/jpeg"
        ["size"]=> int(71189) 
        ["tmp_name"]=> string(14) "/tmp/php9bU0Wm"
        ["error"]=> int(0)
      }
      [1]=> array(5) {
        ["name"]=> string(0) ""
        ["type"]=> string(0) ""
        ["size"]=> int(0)
        ["tmp_name"]=> string(0) ""
        ["error"]=> int(4)
      }
    }

### 多次元配列

フォーム定義を以下のように [] を使ってグループ化することで、グループ化した値を簡単に受け取ることができます。詳しくは [多次元配列にアクセスする](form-multiarray.md) のページを参照してください。

    var $form = array(
           'User[name]' => array(
               'name' => '名前',
               'type' => VAR_TYPE_STRING,
               'form_type' => FORM_TYPE_TEXT,
           ),
           'User[phone][home]' => array(
               'name' => '自宅電話番号',
               'type' => VAR_TYPE_STRING,
               'form_type' => FORM_TYPE_TEXT,
           ),
           'User[phone][mobile]' => array(
               'name' => '携帯電話番号',
               'type' => VAR_TYPE_STRING,
               'form_type' => FORM_TYPE_TEXT,
           ),
       );


* * *
\*1PHP 4.3.0以降ではUPLOAD_ERR_NO_FILEという定数が割り当てられています  

