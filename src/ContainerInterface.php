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

}
