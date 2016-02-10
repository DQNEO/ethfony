
# インストール

Ethhamのインストール方法について説明します。

composerを使ってインストールすることができます。

プロジェクト名を仮に`Sample`として、Ethnamをインストールして
プロジェクトを作成するところまでを説明します。

## composerをインストール

composerをまだインストールしてない場合はインストールしておいてください。

https://getcomposer.org/doc/00-intro.md#globally

## 軽く試してみたい場合は
[README](https://github.com/DQNEO/ethnam#installtion) に書いてるとおりにやればOKです。

ただ、`composer create-project`を使う方法は一見簡単ですが、内部で何が行われているかが隠されてしまうので学習上あまり好ましくありません。
本格的にEthhamを使う場合はこれから説明するやり方で手動で環境構築することをおすすめします。


## プロジェクト用ディレクトリを作成

```sh
# プロジェクト用ディレクトリを作成
mkdir sample
cd sample
```

## composer.jsonを作成

```json
{
  "require": {
    "ethnam/ethnam": "dev-master",
    "ethnam/generator": "dev-master",
    "smarty/smarty": "2.6.*"
  }
}
```

## パッケージをインストール

```
composer install
```

## プロジェクトのスケルトンを作成

```
vendor/bin/ethnam-generator add-project -b . Sample
```

## 簡易サーバを起動

```
cd Sample
php -t www -S localhost:8000
```

ブラウザで`http://localhost:8000/` にアクセスして "Welcome to Ethnam!"の画面が表示されたらOKです。

