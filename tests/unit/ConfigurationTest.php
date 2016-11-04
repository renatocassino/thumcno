<?php


class ConfigurationTest extends \Codeception\Test\Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    protected function _before()
    {
        foreach(glob(dirname(dirname(__DIR__)) . '/src/Tacnoman/**.php') as $file) {
            echo $file;
            require_once $file;
        }
    }

    protected function _after()
    {
    }

    // tests
    public function testSingletonConfig()
    {
        $config = \Tacnoman\Config::getInstance();
        $this->assertInstanceOf('\Tacnoman\Config', $config);
    }

    #public function testSingletonError()
    #{
    #    $this->expectException(\Exception::class, function() {
    #        new \Tacnoman\Config();
    #    });
    #}
}
