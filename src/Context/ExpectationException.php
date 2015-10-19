<?php

/*
 * This file is part of the Behat WebApiExtension.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Behat\WebApiExtension\Context;

class ExpectationException extends \RuntimeException
{
    /**
     * Initializes exception.
     *
     * @param string     $message   Message.
     * @param \Exception $exception Expectation exception.
     */
    public function __construct($message = '', \Exception $exception = null)
    {
        parent::__construct($message, 0, $exception);
    }
}
