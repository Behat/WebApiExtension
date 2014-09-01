<?php

namespace Behat\WebApiExtension\Context;

use Sanpi\Behatch\HttpCall\ContextSupportedVoter;
use Sanpi\Behatch\HttpCall\FilterableHttpCallResult;
use Sanpi\Behatch\HttpCall\HttpCallResult;

class WebApiContextVoter implements ContextSupportedVoter, FilterableHttpCallResult
{
    public function vote(HttpCallResult $httpCallResult)
    {
        return $httpCallResult->getValue() instanceof \GuzzleHttp\Message\Response;
    }

    public function filter(HttpCallResult $httpCallResult)
    {
        return $httpCallResult->getValue()->getBody();
    }
}
