<?php

namespace Tacnoman;

class UrlValidator
{
    public $urlParams = [];

    protected $_changedParams = [
       'src', 'w', 'h', 'q', 'a',
       'zc', 'f', 's', 'cc', 'ct',
    ];

    public function transformUrlParams($appConfigs)
    {
        // Check if the style is defined
        if (isset($this->urlParams['style']) && isset($appConfigs['styles'])) {
            $sizes = $this->getSizeFromStyle($this->urlParams['style'], $appConfigs['styles']);
            $this->urlParams = array_replace_recursive($this->urlParams, $sizes);
        }

        $this->filterUrlParams($this->urlParams);
    }

    public function isValid()
    {
        // Verify if the size defined exists
        if (!isset($this->urlParams['w']) ||
            !isset($this->urlParams['h']) ||
            !isset($this->urlParams['src'])) {
            return false;
        }

        return true;
    }

    public function filterUrlParams()
    {
        $params = [];
        foreach ($this->_changedParams as $parameter) {
            if (isset($this->urlParams[$parameter])) {
                $params[$parameter] = $this->urlParams[$parameter];
            }
        }

        $this->urlParams = $params;
    }

    public function getSizeFromStyle($style, $appStyles)
    {
        if (isset($appStyles[$style])) {
            $currentStyle = $appStyles[$style];
            $sizes = explode('x', $currentStyle);

            return ['w' => $sizes[0], 'h' => $sizes[1]];
        } else {
            throw new \Exception("The style {$style} does not exist.");
        }
    }
}
