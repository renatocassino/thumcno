<?php

use Codeception\Util\Stub;

class ThumcnoServerTest extends \Codeception\Test\Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    protected function _before()
    {
        $this->config = \Tacnoman\Config::getInstance();
        $_ENV['THUMCNO_PATH'] = '/tmp/';
        $this->thumcnoServer = new \Tacnoman\ThumcnoServer();
    }

    protected function _after()
    {
        
    }

    // setHostVars()
    public function testIfRequestExternalImage()
    {
        $src = 'http://lorempixel.com/500/500';
        $this->config->urlParams['src'] = $src;
        $this->thumcnoServer->setHostVars();

        $this->assertEquals($this->thumcnoServer->isURL, true);
        $this->assertEquals($this->thumcnoServer->src, $src);
        $this->assertEquals($this->thumcnoServer->url, parse_url($src));
    }

    public function testIfRequestInternalImage()
    {
        $src = 'dubai.jpg';
        $this->config->urlParams['src'] = $src;
        $this->config->appConfigs['path_images'] = '/example';
        $this->thumcnoServer->setHostVars();

        $this->assertEquals($this->thumcnoServer->isURL, false);
        $this->assertEquals($this->thumcnoServer->src, '/example/' . $src);
        $this->assertEquals($this->thumcnoServer->url, parse_url('/example/' . $src));
    }

    // ifPermittedByExternalReferer()
    public function testIfProtectedByRefererWithBlockConfigured()
    {
        $_SERVER['HTTP_REFERER'] = 'http://google.com';
        $this->config->appConfigs['block_external_leechers'] = true;
        $this->assertEquals($this->thumcnoServer->ifPermittedByExternalReferer(), true);
    }

    public function testIfProtectedByRefererWithAllowReferer()
    {
        $_SERVER['HTTP_REFERER'] = 'http://google.com';
        $this->config->appConfigs['block_external_leechers'] = false;
        $this->assertEquals($this->thumcnoServer->ifPermittedByExternalReferer(), false);
    }

    public function testIfProtectedByRefererWithoutReferer()
    {
        $this->config->appConfigs['block_external_leechers'] = true;
        unset($_SERVER['HTTP_REFERER']);
        $this->assertEquals($this->thumcnoServer->ifPermittedByExternalReferer(), false);
    }

    public function testIfProtectedByRefererWithSameDomain()
    {
        $this->thumcnoServer->myHost = 'google.com';
        $_SERVER['HTTP_REFERER'] = 'http://google.com/';
        $this->config->appConfigs['block_external_leechers'] = true;
        $this->assertEquals($this->thumcnoServer->ifPermittedByExternalReferer(), false);
    }
}