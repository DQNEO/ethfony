<?php
/**
 *  smarty function:フォームのsubmitボタン生成
 *
 *  @param  string  $submit   フォーム項目名
 */
function smarty_function_form_submit($params, &$smarty)
{
    $view = Ethna_Container::getInstance()->getFormHelper();
    if ($view === null) {
        return null;
    }

    //ここでi18n変換をかます
    $params['value'] = _et($params['value']);

    return $view->getFormSubmit($params);
}

