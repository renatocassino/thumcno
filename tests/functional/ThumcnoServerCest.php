<?php

class ThumcnoServerCest
{
    public function _before(FunctionalTester $I)
    {
        $rootPath = dirname(dirname(__DIR__));
        require_once $rootPath.'/vendor/autoload.php';

        foreach (glob($rootPath.'/src/Tacnoman/**.php') as $file) {
            require_once $file;
        }

        $this->config = \Tacnoman\Config::getInstance();

        $_ENV['THUMCNO_PATH'] = '/tmp';
        $this->thumcnoServer = new \Tacnoman\ThumcnoServer();
    }

    public function _after(FunctionalTester $I)
    {
    }

    // setHostVars
    public function TrySetVarsWithoutSRCParam(FunctionalTester $I)
    {
        $I->expectException(new \Exception('Not found `src` param'), function() {
            $this->thumcnoServer->setHostVars();
        });
    }
}
