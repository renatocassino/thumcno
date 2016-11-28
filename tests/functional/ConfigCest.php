<?php


class ConfigCest
{
    public function _before(FunctionalTester $I)
    {
        $rootPath = dirname(dirname(__DIR__));
        require_once $rootPath.'/vendor/autoload.php';

        foreach (glob($rootPath.'/src/Tacnoman/**.php') as $file) {
            require_once $file;
        }
        $this->config = \Tacnoman\Config::getInstance();
    }

    public function _after(FunctionalTester $I)
    {
    }

    // tests
    public function tryExceptWithInvalidRoute(FunctionalTester $I)
    {
        $I->expectException(new \Exception('This route is invalid!'), function() {
            $_GET = ['zc' => 2];
            $_SERVER['REQUEST_URI'] = '/errorURL.jpg';
            $this->config->appConfigs = ['route' => '^\/(?P<w>\d+)x(?P<h>\d+)\/(?<src>[\w.-]+)', 'permit_params_with_route' => 0];
            $this->config->setUrlParams();
        });
    }
}
