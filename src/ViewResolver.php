<?php
/**
 * ViewResolver
 */
class Ethna_ViewResolver
{
    private $backend;
    private $logger;
    private $viewDir;
    private $appId;
    private $baseViewClassName;

    public function __construct($backend, $logger, $viewDir, $appId, $baseViewClassName)
    {
        $this->backend = $backend;
        $this->logger = $logger;
        $this->viewDir = $viewDir;
        $this->appId = $appId;
        $this->baseViewClassName = $baseViewClassName;
    }

    public function getView(string $forward_name): Ethna_ViewClass
    {
        $view_path = preg_replace_callback('/_(.)/', function(array $matches){return '/' . strtoupper($matches[1]); }, ucfirst($forward_name)) . '.php';
        $this->logger->log(LOG_DEBUG, "default view path [%s]", $view_path);

        if (file_exists($this->viewDir . $view_path)) {
            include_once $this->viewDir . $view_path;
        } else {
            $this->logger->log(LOG_DEBUG, 'default view file not found [%s]', $view_path);
        }

        $postfix = preg_replace_callback('/_(.)/', function(array $matches){return strtoupper($matches[1]);}, ucfirst($forward_name));
        $class_name = sprintf("%s_%sView_%s", $this->appId, "", $postfix);
        $this->logger->log(LOG_DEBUG, "view class [%s]", $class_name);
        if (! class_exists($class_name)) {
            $class_name = $this->baseViewClassName;
            $this->logger->log(LOG_DEBUG, 'view class is not defined for [%s] -> use default [%s]', $forward_name, $class_name);

        }

        return new $class_name($this->backend, $forward_name, $this->getTemplatePath($forward_name));
    }

    /**
     *  遷移名に対応するテンプレートパス名が省略された場合のデフォルトパス名を返す
     *
     *  デフォルトでは"foo_bar"というforward名が"foo/bar" + テンプレート拡張子となる
     *  ので好み応じてオーバライドする
     *
     *  @access public
     *  @param  string  $forward_name   forward名
     *  @return string  forwardパス名
     */
    protected function getTemplatePath($forward_name)
    {
        return str_replace('_', '/', $forward_name) . '.tpl';
    }



}