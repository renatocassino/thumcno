<?php

namespace Tacnoman;

/**
 * Class to get configurations
 * Singleton pattern
 */
class Config {
    private static $_instance = null;
    public $params = [];
    public $defines;

    protected function __construct() {
    }

    public static function getInstance() {
        if(is_null(self::$_instance)) {
            self::$_instance = new static();
        }
        return self::$_instance;
    }

    public static function setConfiguration() {
        
    }
}
