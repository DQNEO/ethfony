<?php
/**
 *  smarty block:フォームタグ出力プラグイン
 */
function smarty_block_form($params, $content, &$smarty, &$repeat)
{
    if ($repeat) {
        // {form}: ブロック内部に進む前の処理

        // default
        if (isset($params['default']) === false) {
            // 指定なしのときは $form を使う
            $af = Ethna_Container::getInstance()->getActionForm();

            // c.f. http://smarty.php.net/manual/en/plugins.block.functions.php
            $smarty->_tag_stack[count($smarty->_tag_stack)-1][1]['default']
                = $af->getArray(false);
        }

        // 動的フォームヘルパを呼ぶ
        if (isset($params['ethna_action'])) {
            $ethna_action = $params['ethna_action'];
            $view = Ethna_Container::getInstance()->getView();
            $view->addActionFormHelper($ethna_action, true);
        }

        // ここで返す値は出力されない
        return '';

    } else {
        // {/form}: ブロック全体を出力

        $view = Ethna_Container::getInstance()->getView();
        if ($view === null) {
            return null;
        }

        // ethna_action
        if (isset($params['ethna_action'])) {
            $ethna_action = $params['ethna_action'];
            unset($params['ethna_action']);

            $view->addActionFormHelper($ethna_action);
            $hidden = getActionRequest($ethna_action, 'hidden');
            $content = $hidden . $content;

            //デバグ用に、送信先のアクション名を表示する
            //超絶便利。これのおかげて開発効率だいぶあがった。
            if (Ethna_Container::getInstance()->getConfig()->get('showFormActionName')) {
                echo "[" . $ethna_action. "]";
            }

        }

        // enctype の略称対応
        if (isset($params['enctype'])) {
            if ($params['enctype'] == 'file'
                || $params['enctype'] == 'multipart') {
                $params['enctype'] = 'multipart/form-data';
            } else if ($params['enctype'] == 'url') {
                $params['enctype'] = 'application/x-www-form-urlencoded';
            }
        }

        // defaultはもう不要
        if (isset($params['default'])) {
            unset($params['default']);
        }

        // $contentを囲む<form>ブロック全体を出力
        return $view->getFormBlock($content, $params);
    }
}


/**
 *  アクション名を指定するクエリ/HTMLを生成する
 *
 *  @access public
 *  @param  string  $action action to request
 *  @param  string  $type   hidden, url...
 */
function getActionRequest($action, $type = "hidden")
{
    $s = null;
    if ($type == "hidden") {
        $s = sprintf('<input type="hidden" name="action_%s" value="true" />', htmlspecialchars($action, ENT_QUOTES, mb_internal_encoding()));
    } else if ($type == "url") {
        $s = sprintf('action_%s=true', urlencode($action));
    }
    return $s;
}

