<?php

use Silex\Application;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

require_once __DIR__ . '/../vendor/autoload.php';

$app = new Silex\Application();

$app->match(
    'echo',
    function (Request $req) {
        $ret = array(
            'warning' => 'Do not expose this service in production : it is intrinsically unsafe',
        );

        $ret['method'] = $req->getMethod();

        // Forms should be read from request, other data straight from input.
        $requestData = $req->request->all();
        if (!empty($requestData)) {
            foreach ($requestData as $key => $value) {
                $ret[$key] = $value;
            }
        }

        /** @var string $content */
        $content = $req->getContent(false);
        if (!empty($content)) {

            if (0 === strpos($req->headers->get('Content-Type'), 'application/json')) {

                $data = json_decode($content, true);

                if (!is_array($data)) {
                    $ret['content'] = $content;
                } else {
                    foreach ($data as $key => $value) {
                        $ret[$key] = $value;
                    }
                }

            } elseif (0 === strpos($req->headers->get('Content-Type'), 'application/xml')) {

                $xml = simplexml_load_string($content, "SimpleXMLElement", LIBXML_NOCDATA);
                $jsonString = json_encode($xml);
                $data = json_decode($jsonString, true);

                if (!is_array($data)) {
                    $ret['content'] = $content;
                } else {
                    foreach ($data as $key => $value) {
                        $ret[$key] = $value;
                    }
                }

            } else {
                $ret['content'] = $content;
            }

        }

        $ret['headers'] = array();
        foreach ($req->headers->all() as $k => $v) {
            $ret['headers'][$k] = $v;
        }
        foreach ($req->query->all() as $k => $v) {
            $ret['query'][$k] = $v;
        }

        $response = null;

        if (0 === strpos($req->headers->get('Accept'), 'application/json')) {
            $response = new JsonResponse($ret);
        } elseif (0 === strpos($req->headers->get('Accept'), 'application/xml')) {

            $xml = new SimpleXMLElement('<data/>');
            array_walk_recursive($ret, array ($xml, 'addChild'));
            $ret = $xml->asXML();

            $response = new Response($ret);

        } else {
            $response = new JsonResponse($ret);
        }

        return $response;
    }
);

$app->run();
