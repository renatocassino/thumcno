<?php

class ConfigurationTest extends \Codeception\Test\Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;
    protected $config;

    protected function _before()
    {
        $rootPath = dirname(dirname(__DIR__));
        require_once $rootPath.'/vendor/autoload.php';

        foreach (glob($rootPath.'/src/Tacnoman/**.php') as $file) {
            require_once $file;
        }

        $this->config = \Tacnoman\Config::getInstance();
    }

    // tests
    public function testSingletonConfig()
    {
        $config = \Tacnoman\Config::getInstance();
        $this->assertInstanceOf('\Tacnoman\Config', $config);
    }

    public function testThumcnoPath()
    {
        $_ENV['THUMCNO_PATH'] = '/tmp/';
        $this->config->setThumcnoPath();
        $this->assertEquals($this->config->thumcnoPath, '/tmp/');
    }

    public function testDomainWithHttpHost()
    {
        $_SERVER['HTTP_HOST'] = 'myhost:8888';
        $this->config->setDomain();
        $this->assertEquals($this->config->domain, 'myhost');
    }

    public function testDomainWithHttpHostWithoutPort()
    {
        $_SERVER['HTTP_HOST'] = 'myhosts';
        $this->config->setDomain();
        $this->assertEquals($this->config->domain, 'myhosts');
    }

    public function testDomainWithoutHttpHost()
    {
        $_SERVER['SERVER_NAME'] = 'myhostname';
        unset($_SERVER['HTTP_HOST']);

        $this->config->setDomain();
        $this->assertEquals($this->config->domain, 'myhostname');
    }

    public function testSetPort()
    {
        $_SERVER['SERVER_PORT'] = 8888;
        $this->config->setPort();
        $this->assertEquals($this->config->port, 8888);
    }

    public function testHostnameWithDifferentPort()
    {
        $_ENV['THUMCNO_PATH'] = '/';
        $_SERVER['SERVER_PORT'] = 8888;
        $_SERVER['SERVER_NAME'] = 'myhostname.local';
        $this->config->setDomain();
        $this->config->setPort();
        $this->assertEquals($this->config->getHost(), 'http://myhostname.local:8888');
    }

    public function testHostnameWithDefaultPort()
    {
        $_ENV['THUMCNO_PATH'] = '/';
        $_SERVER['SERVER_PORT'] = 80;
        $_SERVER['SERVER_NAME'] = 'myhostname.local';
        $this->config->setDomain();
        $this->config->setPort();
        $this->assertEquals($this->config->getHost(), 'http://myhostname.local');
    }

    public function testGetParseIniFileIfExists()
    {
        $_ENV['THUMCNO_PATH'] = dirname(__DIR__).'/thumcno_path';
        $configApp = $this->config->getParseIniFile('default');
        $this->assertEquals($configApp['port'], 80);
    }

    public function testSettingAppConfigsWithAValidDomain()
    {
        $_ENV['THUMCNO_PATH'] = dirname(__DIR__).'/thumcno_path';
        $_SERVER['SERVER_NAME'] = 'myhostname.local';
        $this->config->setAppConfig();
        $this->assertEquals($this->config->appConfigs['port'], 8080);
    }

    public function testSettingAppConfigWithOnceDomain()
    {
        $_ENV['THUMCNO_PATH'] = dirname(__DIR__).'/thumcno_path';
        $_ENV['PERMIT_ONLY_DOMAIN'] = true;
        $_SERVER['SERVER_NAME'] = 'doesnotexistdomain';

        $this->config->appConfigs = [];
        $this->config->setDomain();
        $this->config->setAppConfig();
        $this->assertEquals($this->config->appConfigs['port'], 80);
    }

    public function testUrlParamsWithoutRoute()
    {
        $_GET = ['w' => 200, 'h' => 300];
        $this->config->appConfigs = [];
        $this->config->setUrlParams();
        $this->assertEquals($this->config->urlParams, ['w' => 200, 'h' => 300]);
    }

    public function testUrlParamsWithRoute()
    {
        $_GET = [];
        $_SERVER['REQUEST_URI'] = '/200x300/100/dubai.jpg';
        $this->config->appConfigs = ['route' => '^\/(?P<w>\d+)x(?P<h>\d+)\/(?P<q>\d{1,3})\/(?<src>[\w.-]+)'];
        $this->config->setUrlParams();
        $this->assertEquals($this->config->urlParams['w'], 200);
        $this->assertEquals($this->config->urlParams['h'], 300);
        $this->assertEquals($this->config->urlParams['q'], 100);
        $this->assertEquals($this->config->urlParams['src'], 'dubai.jpg');
    }

    public function testUrlParamsWithRouteAndGet()
    {
        $_GET = ['q' => 80];
        $_SERVER['REQUEST_URI'] = '/200x300/dubai.jpg';
        $this->config->appConfigs = ['route' => '^\/(?P<w>\d+)x(?P<h>\d+)\/(?<src>[\w.-]+)'];
        $this->config->setUrlParams();
        $this->assertEquals($this->config->urlParams['w'], 200);
        $this->assertEquals($this->config->urlParams['h'], 300);
        $this->assertEquals($this->config->urlParams['q'], 80);
        $this->assertEquals($this->config->urlParams['src'], 'dubai.jpg');
    }
}
