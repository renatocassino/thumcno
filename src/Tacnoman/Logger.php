<?php

namespace Tacnoman;

use Psr\Log\LogLevel;
use Katzgrau\KLogger\Logger as KLogger;

/**
 * Class Logger - Logging application in folder /path/to/thumcno/logs.
 *
 * @author Tacnoman - Renato Cassino <renatocassino@gmail.com>
 */
class Logger
{
    private static $instance = null;
    public $logger;

    public static $path = __DIR__.'/../../logs';

    /**
     * Get instance and setting the default logLevel.
     *
     * @return \Tacnoman\Logger
     */
    public static function getInstance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new static();
            $config = Config::getInstance();
            $configLogLevel = $config->appConfigs['debug_level'] ?? 3;
            $logLevel = self::$instance->getDebugLevel($configLogLevel);
            self::$instance->logger = new KLogger(self::$path, $logLevel);
        }

        return self::$instance;
    }

    /**
     * protected __construct().
     */
    protected function __construct()
    {
    }

    /**
     * Removing singleton.
     */
    public function __destruct()
    {
        self::$instance = null;
    }

    /**
     * Method to add to log.
     *
     * @param int|string $level   of debug
     * @param string     $message to save in log
     */
    public static function log($level, $message)
    {
        if (is_numeric($level)) {
            $level = self::getDebugLevel($level);
        }

        self::getInstance()->logger->$level($message);
    }

    /**
     * Alias to log method.
     *
     * @param string $message to save in log
     */
    public static function emergency($message)
    {
        self::log(LogLevel::EMERGENCY, $message);
    }

    /**
     * Alias to log method.
     *
     * @param string $message to save in log
     */
    public static function alert($message)
    {
        self::log(LogLevel::ALERT, $message);
    }

    /**
     * Alias to log method.
     *
     * @param string $message to save in log
     */
    public static function critical($message)
    {
        self::log(LogLevel::CRITICAL, $message);
    }

    /**
     * Alias to log method.
     *
     * @param string $message to save in log
     */
    public static function error($message)
    {
        self::log(LogLevel::ERROR, $message);
    }

    /**
     * Alias to log method.
     *
     * @param string $message to save in log
     */
    public static function warning($message)
    {
        self::log(LogLevel::WARNING, $message);
    }

    /**
     * Alias to log method.
     *
     * @param string $message to save in log
     */
    public static function notice($message)
    {
        self::log(LogLevel::NOTICE, $message);
    }

    /**
     * Alias to log method.
     *
     * @param string $message to save in log
     */
    public static function info($message)
    {
        self::log(LogLevel::INFO, $message);
    }

    /**
     * Alias to log method.
     *
     * @param string $message to save in log
     */
    public static function debug($message)
    {
        self::log(LogLevel::DEBUG, $message);
    }

    /**
     * Alias to log method.
     *
     * @param string $message to save in log
     */
    protected static function getDebugLevel($level)
    {
        switch ($level) {
            case 1: return LogLevel::EMERGENCY;
            case 2: return LogLevel::ALERT;
            case 3: return LogLevel::CRITICAL;
            case 4: return LogLevel::ERROR;
            case 5: return LogLevel::WARNING;
            case 6: return LogLevel::NOTICE;
            case 7: return LogLevel::INFO;
            case 8: return LogLevel::DEBUG;
        }

        return LogLevel::ERROR;
    }
}
