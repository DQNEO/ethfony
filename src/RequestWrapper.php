<?php
use Symfony\Component\HttpFoundation\Request;

class Ethna_RequestWrapper
{
    /** @var Request  */
    private $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     *
     */
    public function getHttpVars(): array
    {
        if ($this->request->isMethod('POST')) {
            $http_vars = $this->request->request->all();
        } else {
            $http_vars = $this->request->query->all();
        }

        return $http_vars;
    }


    /**
     *  フォームにより要求されたアクション名を返す
     *
     *  アプリケーションの性質に応じてこのメソッドをオーバーライドして下さい。
     *  デフォルトでは"action_"で始まるフォーム値の"action_"の部分を除いたもの
     *  ("action_sample"なら"sample")がアクション名として扱われます
     *
     * @access protected
     * @return string  フォームにより要求されたアクション名
     */
    protected function _getActionName_Form()
    {
        // フォーム値からリクエストされたアクション名を取得する
        $action_name = $sub_action_name = null;
        foreach ($this->getHttpVars() as $name => $value) {
            if ($value == "" || strncmp($name, 'action_', 7) != 0) {
                continue;
            }

            $tmp = substr($name, 7);

            // type="image"対応
            if (preg_match('/_x$/', $name) || preg_match('/_y$/', $name)) {
                $tmp = substr($tmp, 0, strlen($tmp) - 2);
            }

            // value="dummy"となっているものは優先度を下げる
            if ($value == "dummy") {
                $sub_action_name = $tmp;
            } else {
                $action_name = $tmp;
            }
        }
        if ($action_name == null) {
            $action_name = $sub_action_name;
        }

        $form_action_name =  $action_name;
        $form_action_name = preg_replace('/[^a-z0-9\-_]+/i', '', $form_action_name);
        return $form_action_name;
    }


    /**
     *  実行するアクション名を返す
     *
     * @access protected
     * @param  mixed $default_action_name 指定のアクション名
     * @return string  実行するアクション名
     */
    public function getActionName(string $default_action_name)
    {
        // フォームから要求されたアクション名を取得する
        $form_action_name = $this->_getActionName_Form();
        //$this->logger->log(LOG_DEBUG, 'form_action_name[%s]', $form_action_name);

        // フォームからの指定が無い場合はエントリポイントに指定されたデフォルト値を利用する
        if ($form_action_name == "" && $default_action_name) {
            //$this->logger->log(LOG_DEBUG, '-> default_action_name[%s]', $default_action_name);
            return $default_action_name;
        } else {
            //$this->logger->log(LOG_DEBUG, '<<< action_name[%s] >>>', $form_action_name);
            return $form_action_name;
        }

    }


}
