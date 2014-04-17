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

class Logger extends AbstractLogger
{
    private $debug;

    public function __construct($debug = 0)
    {
        $this->debug = $debug;
    }

    public function report($message, $title = null)
    {
        if ($this->debug) {
            $message = $title ? "$title: $message" : $message;
            $this->log(LogLevel::DEBUG, $message);
        }
    }

    public function log($level, $message, array $context = array())
    {
        error_log($message, 4);
    }
}
