<?php

/**
 * Class Thumcno
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

        self::$default = parse_ini_file(PATH . '/apps/default.ini', true);
    }

    /**
     * Check if the file for domain exists. If not exist, throw exception and get 500 error page.
     *
     */
    public function checkFile()
    {
        $filename = PATH . '/apps/' . self::$config['domain'] . '.ini';
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
}