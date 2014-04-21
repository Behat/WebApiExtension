<?php

use Behat\WebApiExtension\TestApp\Logger;
use Silex\Application;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

require_once __DIR__ . '/../vendor/autoload.php';

$app = new Silex\Application();
$app['logger'] = new Logger(1);

$app->match(
    'echo',
      function (Application $app, Request $req) {
          /** @var \Behat\WebApiExtension\TestApp\Logger $logger */
          $logger = $app['logger'];
          $ret = array(
              'warning' => 'Do not expose this service in production : it is intrinsically unsafe',
          );

          $ret['method'] = $req->getMethod();

          // Forms should be read from request, other data straight from input.
          $requestData = $req->request->all();
          if (!empty($requestData)) {
              $logger->report(print_r($requestData, true), 'Request');
              foreach ($requestData as $key => $value) {
                  $ret[$key] = $value;
              }
          }

          $content = $req->getContent(false);
          if (empty($content)) {
              $logger->report('No content');
          } else {
              $logger->report(print_r($content, true), 'Content');
              $data = json_decode($content, true);
              if (!is_array($data)) {
                  $ret['content'] = $content;
              } else {
                  $logger->report(print_r($data, true), 'Data');
                  foreach ($data as $key => $value) {
                      $ret[$key] = $value;
                  }
              }
          }

          $ret['headers'] = array();
          foreach ($req->headers->all() as $k => $v) {
              $ret['headers'][$k] = $v;
          }
          foreach ($req->query->all() as $k => $v) {
              $ret['query'][$k] = $v;
          }
          $response = new JsonResponse($ret);

          return $response;
      }
);

$app->run();
