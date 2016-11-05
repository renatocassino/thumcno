<?php

namespace Tacnoman;

/**
 * Class to get configurations
 * Singleton pattern
 * @author Tacnoman <renatocassino@gmail.com>
 */
class Config {
    private static $_instance = null;

    /** @var string Should be a domain */
    public $domain;

    /** @var string Should be a path readable */
    public $thumcnoPath;

    /** @var integer The server port */
    public $port;

    /** @var array|null url params */
    public $params = [];

    /**
     * Configs defined in `.ini` file
     * @var array
     */
    public $appConfigs = [];

     /**
      * protected __construct()
      */
    protected function __construct() {}

     /**
      * Get instance
      * @return \Tacnoman\Config
      */
    public static function getInstance()
    {
        if(is_null(self::$_instance)) {
            self::$_instance = new static();
        }
        return self::$_instance;
    }

    /**
     * Set configuration
     * Called once a time
     * @return void
     */
    public function setConfiguration()
    {
        $this->setThumcnoPath();
        $this->setDomain();
        $this->setPort();
        $this->setAppConfig();
    }

    /**
     * Set thumcno path
     * @throws Exception if the enviroment variable `THUMCNO_PATH` not defined
     * @return void
     */
    public function setThumcnoPath()
    {
        if(!isset($_ENV['THUMCNO_PATH'])) {
            throw new \Exception('You must define the const `THUMCNO_PATH`.');
        }
        $this->thumcnoPath = $_ENV['THUMCNO_PATH'];
    }

    /**
     * Set domain
     * @return void
     */
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

    /**
     * Set port
     * @return void
     */
    public function setPort()
    {
        $this->port = $_SERVER['SERVER_PORT'];
    }

    /**
     * Get host
     * @return string host
     */
    public function getHost()
    {
        $host = "http://{$this->domain}";
        if(80 != $this->port) {
            $host .= ":{$this->port}";
        }
        return $host;        
    }

    /**
     * Set app config. Getting params setted in ini files.
     * Get `default.ini` and replace params with `<yourdomain>.ini` file.
     *
     * @see https://github.com/tacnoman/thumcno/blob/master/README.md Ini configuration
     */
    public function setAppConfig()
    {
        $defaultConfigs = $this->getParseIniFile('default');
        try {
            $appConfigs = $this->getParseIniFile($this->domain);
            $this->appConfigs = array_replace_recursive(
                $defaultConfigs,
                $appConfigs
            );
        } catch(\Exception $e) {
            if(isset($_ENV['PERMIT_ONLY_NAME']) && $_ENV['PERMIT_ONLY_NAME']) {
                $this->appConfigs = $defaultConfigs;
            } else {
                throw new Exception("You have an invalid domain.");
            }
        }
    }

    /**
     * Parse ini file
     * @param string $filename is the name of file without extension
     * @throws \Exception if file not exists
     * @return array|null
     */
    public function getParseIniFile($filename)
    {
        $pathFile = $_ENV['THUMCNO_PATH'] . '/apps/' . $filename . '.ini';
        if(file_exists($pathFile)) {
            return parse_ini_file($pathFile, true);
        }
        throw new \Exception("File `{$pathFile}` does not exists.");
    }
}
