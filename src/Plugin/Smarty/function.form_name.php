<?php
/**
 *  smarty function:フォーム表示名生成
 *
 *  @param  string  $name   フォーム項目名
 */
function smarty_function_form_name($params, &$smarty)
{
    // name
    if (isset($params['name'])) {
        $name = $params['name'];
        unset($params['name']);
    } else {
        return null;
    }

    // view object
    $formHelper = Ethna_Container::getInstance()->getFormHelper();
    // action
    $action = null;
    if (isset($params['action'])) {
        $action = $params['action'];
        unset($params['action']);
    } else {
        for ($i = count($smarty->_tag_stack); $i >= 0; --$i) {
            if ($smarty->_tag_stack[$i][0] === 'form') {
                if (isset($smarty->_tag_stack[$i][1]['ethna_action'])) {
                    $action = $smarty->_tag_stack[$i][1]['ethna_action'];
                }
                break;
            }
        }
    }
    if ($action !== null) {
        $formHelper->addActionFormHelper($action);
    }

    return $formHelper->getFormName($name, $action, $params);
}


