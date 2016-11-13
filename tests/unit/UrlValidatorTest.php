<?php


class UrlValidatorTest extends \Codeception\Test\Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    protected function _before()
    {
        $this->config = \Tacnoman\Config::getInstance();
        $this->urlValidator = new \Tacnoman\UrlValidator();
    }

    public function testFilterUrlParams()
    {
        $params = ['w' => 100, 'nonExistParam' => true];
        $this->urlValidator->urlParams = $params;
        $this->urlValidator->filterUrlParams();
        $this->assertEquals($this->urlValidator->urlParams, ['w' => 100]);
    }

    public function testGetStyleSize()
    {
        $styles = ['medium' => '200x100', 'big' => '800x600'];
        $size = $this->urlValidator->getSizeFromStyle('medium', $styles);
        $this->assertEquals($size, ['w' => 200, 'h' => 100]);
    }

    public function testIfUrlParamsIsValid()
    {
        $this->urlValidator->urlParams = ['w' => 100, 'h' => 200, 'src' => 'dubai.jpg'];
        $this->assertTrue($this->urlValidator->isValid());
    }

    public function testIfUrlParamsIsInvalid()
    {
        $this->urlValidator->urlParams = ['h' => 200, 'src' => 'dubai.jpg'];
        $this->assertFalse($this->urlValidator->isValid());

        $this->urlValidator->urlParams = ['w' => 100, 'src' => 'dubai.jpg'];
        $this->assertFalse($this->urlValidator->isValid());

        $this->urlValidator->urlParams = ['w' => 100, 'h' => 200];
        $this->assertFalse($this->urlValidator->isValid());
    }

    public function testIsValidUrlParamsWithStyle()
    {
        $appConfigs = ['styles' => ['medium' => '200x100']];
        $this->urlValidator->urlParams = ['style' => 'medium', 'src' => 'dubai.jpg'];
        $this->urlValidator->transformUrlParams($appConfigs);
        $this->assertEquals($this->urlValidator->urlParams, ['w' => 200, 'h' => 100, 'src' => 'dubai.jpg']);
        $this->assertTrue($this->urlValidator->isValid());
    }
}
