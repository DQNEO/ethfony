<?php

/**
 *  Ethna_* クラス群のオートロード
 */
spl_autoload_register(function($className){
    //Ethnaクラス
    if ($className === 'Ethna') {
        include_once __DIR__ . '/src/Ethna.php';
    }

    //Ethna_*クラス
    //単純に_区切りをディレクトリ区切りにマッピングする
    if (strpos($className, 'Ethna_') === 0) {
        $separated = explode('_', $className);
        array_shift($separated);  // remove first element
        //読み込み失敗しても死ぬ必要はないのでrequireではなくincludeする
        //see http://qiita.com/Hiraku/items/72251c709503e554c280
        include_once __DIR__ . '/src/' . join('/', $separated) . '.php';
    }
});

spl_autoload_register(function($class_name){
    $file = sprintf("%s.%s", $class_name, 'php');
    if (file_exists_ex($file)) {
        include_once $file;
        return ;
    }

    if (preg_match('/^(\w+?)_(.*)/', $class_name, $match)) {
        // try ethna app style
        // App_Foo_Bar_Baz -> Foo/Bar/App_Foo_Bar_Baz.php
        $tmp = explode("_", $match[2]);
        $tmp[count($tmp)-1] = $class_name;
        $file = sprintf('%s.%s',
            implode(DIRECTORY_SEPARATOR, $tmp),
            'php');
        if (file_exists_ex($file)) {
            include_once $file;
            return ;
        }

        // try ethna app & pear mixed style
        // App_Foo_Bar_Baz -> Foo/Bar/Baz.php
        $file = sprintf('%s.%s',
            str_replace('_', DIRECTORY_SEPARATOR, $match[2]),
            'php');
        if (file_exists_ex($file)) {
            include_once $file;
            return ;
        }

        // try ethna master style
        // Ethna_Foo_Bar -> src/Ethna/Foo/Bar.php
        $tmp = explode('_', $match[2]);
        array_unshift($tmp, 'Ethna', 'class');
        $file = sprintf('%s.%s',
            implode(DIRECTORY_SEPARATOR, $tmp),
            'php');
        if (file_exists_ex($file)) {
            include_once $file;
            return ;
        }

        // try pear style
        // Foo_Bar_Baz -> Foo/Bar/Baz.php
        $file = sprintf('%s.%s',
            str_replace('_', DIRECTORY_SEPARATOR, $class_name),
            'php');
        if (file_exists_ex($file)) {
            include_once $file;
            return ;
        }
    }


});