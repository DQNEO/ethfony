<?php
/**
 *  smarty function:フォームのsubmitボタン生成
 *
 *  @param  string  $submit   フォーム項目名
 */
function smarty_function_form_submit($params, &$smarty)
{
    $formHelper = Ethna_Container::getInstance()->getFormHelper();
    //ここでi18n変換をかます
    $params['value'] = _et($params['value']);

    return $formHelper->getFormSubmit($params);
}

