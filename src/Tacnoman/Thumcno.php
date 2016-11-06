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

    public function __construct()
    {
        $config = Config::GetInstance();
        $config->setConfiguration();
    }

    /**
     * Start service.
     */
    public function start()
    {
        ThumcnoServer::start();
    }
}
