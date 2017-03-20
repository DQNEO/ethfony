<?php

/**
 */
interface Ethna_ContainerInterface
{

    public function getConfig(): Ethna_Config;

    public function getI18N(): Ethna_I18N;

    public function getActionError(): Ethna_ActionError;

    public function getSession(): Ethna_Session;

    public function getPlugin(): Ethna_Plugin;

    public function getLogger(): Ethna_Logger;

    public function getAppId(): string;

    public function getDirectory(string $key);

    public function getExt(string $key): string;

    public function getTmpdir();

    public function getView(): Ethna_FormHelper;

    public function getDataContainer(): Ethna_AppDataContainer;

    public function getActionResolver() :Ethna_ActionResolver;

    public function getRenderer() :Ethna_Renderer_Smarty;

}
