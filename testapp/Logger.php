<?php
/**
 * @file
 * Logger.php
 *
 * @author: Frédéric G. MARAND <fgm@osinet.fr>
 *
 * @copyright (c) 2014 Ouest Systèmes Informatiques (OSInet).
 *
 * @license General Public License version 2 or later
 */

namespace Behat\WebApiExtension\TestApp;


use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;

/**
 * A tiny logger outputting information on the php -S output.
 *
 * @package Behat\WebApiExtension\TestApp
 */
class Logger extends AbstractLogger
{
    /**
     * @var bool
     *   Actually log messages or not.
     */
    private $debug;

    /**
     * @var int
     *   0 to 4 : the fourth parameter to error_log().
     */
    private $messageType;

    /**
     * @param bool $debug
     *   Actually output messages or not.
     * @param int $messageType
     *   Use 4 (default) for "php -S", 0 for normal web servers.
     */
    public function __construct($debug = false, $messageType = 4)
    {
        $this->debug = $debug;
        $this->messageType = $messageType;
    }

    /**
     * Log a debug message, optionally prepending a title to it.
     *
     * @param string $message
     * @param string|null $title
     */
    public function report($message, $title = null)
    {
        if ($this->debug) {
            $message = $title ? "$title: $message" : $message;
            $this->log(LogLevel::DEBUG, $message);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function log($level, $message, array $context = array())
    {
        error_log($message, $this->messageType);
    }
}
