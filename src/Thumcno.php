<?php

namespace Tacnoman;

/**
 * Class Thumcno - Class to generate thumbnail.
 *
 * @author Tacnoman - Renato Cassino <renatocassino@gmail.com>
 */
class Thumcno
{
    public static $config;

    public static $params;
    public static $url_params;
    public static $default;

    public function __construct() {
        $this->setVars();
        $this->checkFile();
        $this->checkPort();
        $this->getUrlParams();
        $this->applyUrlParams();
    }

    /**
     * Get url params
     */
    public function getUrlParams() {
        if(isset(self::$params['route'])) {
            preg_match('/' .self::$params['route'] . '/', $_SERVER['REQUEST_URI'], $params);
            if(empty($params))
                $this->error('404', 'This route is invalid!');

            $params = array_replace($params, $_GET);
        } else {
            $params = $_GET;
        }

        $this->validateUrlParams($params);
    }

    /**
     * Get only url valid params and ignore the invalid
     *
     * @param $params
     */
    private function validateUrlParams($params) {
        $changedParams = [
            'src', 'w', 'h', 'q', 'a',
            'zc', 'f', 's', 'cc', 'ct'
        ];

        foreach($changedParams as $parameter) {
            if(isset($params[$parameter])) {
                self::$url_params[$parameter] = $params[$parameter];
            }
        }

        // Check if the style is defined
        if(isset($params['style'])) {
            $style = $params['style'];

            if(isset(self::$params['sizes']) && is_array(self::$params['sizes'])) {
                $sizes = self::$params['sizes'];

                if (isset($sizes[$style])) {

                    if (!preg_match('/^(\d+)x(\d+)$/', $sizes[$style]))
                        $this->error(500, 'You must set size as <width>x<height>. Ex: 400x400');

                    $sizes = explode('x', $sizes[$style]);

                    self::$url_params['w'] = $sizes[0];
                    self::$url_params['h'] = $sizes[1];


                } else {
                    $this->error(404, 'This style does not exists.');
                }
            }
        } else {
            // Verify if the size defined exists
            if( !isset(self::$url_params['w']) ||
                !isset(self::$url_params['h'])
            ) {
                $this->error(500, 'You must define the size or style size.');
            }

            $sizeStr = self::$url_params['w'] . 'x' . self::$url_params['h'];

            $forbidden = true;
            foreach(self::$params['sizes'] as $currentSize) {
                if($sizeStr == $currentSize) {
                    $forbidden = false;
                    break;
                }
            }

            if($forbidden) {
                $this->error(403, 'You cannot use this size for image.');
            }

        }
    }

    /**
     * Set the config and get the `default.ini` file
     */
    public function setVars()
    {
        self::$config = [
            'domain' => $_SERVER['SERVER_NAME'],
            'port' => $_SERVER['SERVER_PORT']
        ];

        self::$default = parse_ini_file(THUMCNO_PATH . '/apps/default.ini', true);
    }

    /**
     * Check if the file for domain exists. If not exist, throw exception and get 500 error page.
     *
     */
    public function checkFile()
    {
        $filename = THUMCNO_PATH . '/apps/' . self::$config['domain'] . '.ini';
        if( file_exists( $filename) ) {
            self::$params = array_replace_recursive(
                self::$default,
                parse_ini_file($filename, true)
            );
        }
        else {
            $this->error(500, 'You must create the file `' . __DIR__ . '/apps/' . self::$config['domain'] . '.ini');
        }
    }

    /**
     * Replace the <domain>.ini config in default.ini file
     *
     */
    public function applyUrlParams() {
        self::$params = array_replace_recursive(
            self::$params,
            self::$url_params
        );
    }

    /**
     * Validate if the port is valid
     *
     */
    public function checkPort() {
        if(self::$config['port'] != self::$params['port']) {
            $this->error(500, 'Your port is invalid! You must run in ' . self::$params['port']);
        }
    }

    /**
     * Exception
     *
     * @param $code
     * @param $message
     */
    protected function error($code, $message) {
        header('X-Error-Message: ' . $message, true, $code);
        die($message);
    }

    /**
     * Start service
     */
    public function start() {
        ThumcnoServer::start();
    }
}