# エラー処理ポリシー
  - Ethna_Error 
  - ActionError 

## エラー処理ポリシー

アプリケーションの実行中に生じるエラーは、次の２つにわけられます。

- System error
  - システムが原因で起きるエラー。DBエラーなど。
- User error
  - ユーザがフォームに入力した値が不正である場合のエラー。

Ethnaフレームワークでは、これらのエラーは全てEthna_Error（またはPEAR_Error）によって処理されます。

[![Ethna_Error1.png](http://ethna.jp/index.php?plugin=ref&page=ethna-document-dev_guide-error-policy&src=Ethna_Error1.png "Ethna_Error1.png")](plugin=attach&refer=ethna-document-dev_guide-error-policy&openfile=Ethna_Error1.png.md "Ethna_Error1.png")

### Ethna_Error

Ethna_Errorには、エラーコードとエラーメッセージが格納されています。 Ethna_Errorはシステムとしてエラーハンドリングを行うので，実際のアプリケーションでは，Ethna_Errorをアクションクラスで処理し，ユーザに必要な情報のみを提示します．

    function perform()
        {
            $sm =& new {アプリケーションID}_SampleManager();
             $result = $sm->test();
            if (Ethna::isError($result)) {
    
                 //エラーの場合の処理
                 .....
             }
    
             ....
         }

EthnaクラスのisError()メソッドで、エラーの有無を確認できます。 ここではtest()メソッドからエラーオブジェクトが返ってきた場合に，エラー処理を行うようにしています。

アプリケーションのManagerでエラーオブジェクトを返すには、次のようにします。

    class {アプリケーションID}_SampleManager
    {
        function test($data)
        {
            // 実際には，まともなエラー処理を行う．
            if (! $data) {
                return Ethna::raiseNotice('データがありません', E_SAMPLE_TEST);
            }
            return 0;
        }
    }

エラーオブジェクトには、Notice,Warning,Errorの3つがあります。 エラーの内容に応じて，これらを使い分けます． アプリケーション固有のエラーメッセージを渡したい場合は、EthnaクラスのraiseNotice,raiseWarning,raiseErrorメソッドを使って，Ethna_Errorオブジェクトを生成します． この例では，raiseNoticeを用いてエラーオブジェクトを返しています． 引数には，メッセージとエラーコードを与えます．

**[アプリケーションエラーコードの定義](error-definecode.md)**

### ActionError

[![Ethna_ActionError.png](http://ethna.jp/index.php?plugin=ref&page=ethna-document-dev_guide-error-policy&src=Ethna_ActionError.png "Ethna_ActionError.png")](plugin=attach&refer=ethna-document-dev_guide-error-policy&openfile=Ethna_ActionError.png.md "Ethna_ActionError.png")

エラーの内容をユーザに提示した場合，アクションクラスで受け取ったエラーオブジェクトをActionErrorに格納します。 具体的には、下記のようにae(actionError)のaddObjectメソッドを使います。

    if (Ethna::isError($result)) {
                 $this->ae->addObject('testError', $result);
           }

また、エラーメッセージとエラーコードから、エラーオブジェクトを生成して、ActionErrorに追加するaddメソッドもあります。

ActionErrorの内容を表示するには，ビューで次のように書きます．

    <tr>
         <td>エラーメッセージ</td> 
         <td>{message name="testError"}</td>
      </tr>

