<?php

namespace Tacnoman;

/**
 * Class to get configurations
 * Singleton pattern
 */
class Config {
    private static $_instance = null;
    public $domain;
    public $thumcnoPath;
    public $host;
    public $port;
    public $params = [];
    public $defines;

    protected function __construct() {
    }

    public static function getInstance()
    {
        if(is_null(self::$_instance)) {
            self::$_instance = new static();
        }
        return self::$_instance;
    }

    public function setConfiguration()
    {
        $this->setThumcnoPath();
        $this->setDomain();
        $this->setPort();
        $this->setHost();
    }

    public function setThumcnoPath()
    {
        if(!isset($_ENV['THUMCNO_PATH'])) {
            throw new \Exception('500', 'You must define the const `THUMCNO_PATH`.');
        }
        $this->thumcnoPath = $_ENV['THUMCNO_PATH'];
    }

    public function setDomain()
    {
        if (isset($_SERVER['HTTP_HOST'])) {
            $domain = $_SERVER['HTTP_HOST'];
            if (strstr($domain, ':')) {
                $domain = explode(':', $domain)[0];
            }
        } else {
            $domain = $_SERVER['SERVER_NAME'];
        }

        $this->domain = $domain;
    }

    public function setPort()
    {
        $this->port = $_SERVER['SERVER_PORT'];
    }

    public function setHost()
    {
        $this->host = "http://{$this->domain}";
        if(80 != $this->port) {
            $this->host .= ":{$this->port}";
        }
    }
}
