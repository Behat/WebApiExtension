<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Factory\AppFactory;
use Slim\Exception\HttpNotFoundException;
use Slim\Psr7\Response as SlimResponse;

require_once __DIR__ . '/../vendor/autoload.php';

$app = AppFactory::create();

$app->any(
    '/echo',
    function (Request $req, Response $response) {
        $ret = array(
            'warning' => 'Do not expose this service in production : it is intrinsically unsafe',
        );

        $ret['method'] = $req->getMethod();

        // Forms should be read from request, other data straight from input.
        $requestData = $req->getParsedBody();
        if (!empty($requestData)) {
            foreach ($requestData as $key => $value) {
                $ret[$key] = $value;
            }
        }

        /** @var string $content */
        $content = $req->getBody()->getContents();
        if (!empty($content)) {
            $data = json_decode($content, true);
            if (!is_array($data)) {
                $ret['content'] = $content;
            } else {
                foreach ($data as $key => $value) {
                    $ret[$key] = $value;
                }
            }
        }

        $ret['headers'] = array();
        foreach ($req->getHeaders() as $k => $v) {
            $ret['headers'][$k] = $v;
        }
        foreach ($req->getQueryParams() as $k => $v) {
            $ret['query'][$k] = $v;
        }

        $response->getBody()->write(json_encode($ret));

        return $response->withHeader('Content-type', 'application/json');
    }
);

$app->add(function(Request $request, RequestHandlerInterface $requestHandler) {
    try {
        return $requestHandler->handle($request);
    } catch (HttpNotFoundException $httpException) {
        $response = (new SlimResponse())->withStatus(404);
        $response->getBody()->write('404 Not found');

        return $response;
    }
});
$app->run();
