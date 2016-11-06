<?php

namespace Tacnoman;

/**
 * Class to transform and validate url.
 *
 * @author Tacnoman <renatocassino@gmail.com>
 */
class UrlValidator
{
    /** @var array Parans in `$_GET` or in route */
    public $urlParams = [];

    /** @var array Possible params in `$_GET` or in route */
    protected $_changedParams = [
       'src', 'w', 'h', 'q', 'a',
       'zc', 'f', 's', 'cc', 'ct',
    ];

    /**
     * Transform url params for a valid format if is possible.
     */
    public function transformUrlParams($appConfigs)
    {
        // Check if the style is defined
        if (isset($this->urlParams['style']) && isset($appConfigs['styles'])) {
            $sizes = $this->getSizeFromStyle($this->urlParams['style'], $appConfigs['styles']);
            $this->urlParams = array_replace_recursive($this->urlParams, $sizes);
        }

        $this->filterUrlParams($this->urlParams);
    }

    /**
     * Check if the url is valid.
     *
     * @return bool
     */
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

    /**
     * Filter url, removing the useless params.
     */
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

    /**
     * Getting sizes from styles defined in array.
     *
     * @throw Exception if the style does not found
     */
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
