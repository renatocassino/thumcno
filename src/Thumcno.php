<?php

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

    public function getUrlParams() {
        if(isset(self::$params['route'])) {
            preg_match('/' .self::$params['route'] . '/', $_SERVER['REQUEST_URI'], $params);
            if(empty($params))
                $this->error('404');

            $params = array_replace($params, $_GET);
        } else {
            $params = $_GET;
        }

        $this->validateUrlParams($params);
    }

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

    public function setVars()
    {
        self::$config = [
            'domain' => $_SERVER['SERVER_NAME'],
            'port' => $_SERVER['SERVER_PORT']
        ];

        self::$default = parse_ini_file(PATH . '/apps/default.ini', true);
    }

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
            $this->error(500);
        }
    }

    public function applyUrlParams() {
        self::$params = array_replace_recursive(
            self::$params,
            self::$url_params
        );
    }

    public function checkPort() {
        if(self::$config['port'] != self::$params['port']) {
            $this->error(500);
        }
    }

    protected function error($code) {
        die('error');
    }
}