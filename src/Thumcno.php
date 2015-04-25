<?php

class Thumcno
{
    public static $config;

    public static $params;
    public static $default;

    public function __construct() {
        $this->setVars();
        $this->checkFile();
        $this->checkPort();
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

            self::$params = array_replace_recursive(
                self::$params,
                $_GET
            );
        }
        else {
            $this->error(500);
        }
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