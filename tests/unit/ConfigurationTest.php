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
        require_once $rootPath . '/vendor/autoload.php';

        foreach(glob($rootPath . '/src/Tacnoman/**.php') as $file) {
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

    public function testPort()
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
        $this->config->setConfiguration();
        $this->assertEquals($this->config->host, 'http://myhostname.local:8888');
    }

    public function testHostnameWithDefaultPort()
    {
        $_ENV['THUMCNO_PATH'] = '/';
        $_SERVER['SERVER_PORT'] = 80;
        $_SERVER['SERVER_NAME'] = 'myhostname.local';
        $this->config->setConfiguration();
        $this->assertEquals($this->config->host, 'http://myhostname.local');
    }
}
