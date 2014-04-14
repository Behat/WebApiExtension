<?php
use Silex\Application;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

require_once __DIR__ . '/../vendor/autoload.php';

$app = new Silex\Application();

global $debug;

$debug = 1;

function logger($message, $title = null) {
  if ($GLOBALS['debug']) {
    $out = $title ? "$title: $message" : $message;
    error_log($out, 4);
  }
}

$app->match('echo', function (Application $app, Request $req) {
  $ret = array(
    'warning' => 'Do not expose this service in production : it is intrinsically unsafe',
  );

  $ret['method'] = $req->getMethod();

  // Data should be read from $_REQUEST for forms, straight from input otherwise.
  if (!empty($_REQUEST)) {
    logger(print_r($_REQUEST, true), 'Request');
    foreach ($_REQUEST as $key => $value) {
      $ret[$key] = $value;
    }
  }
  else {
    $content = $req->getContent(false);
    if (empty($content)) {
      logger('No content');
    }
    else {
      logger(print_r($content, true), 'Content');
      $data = json_decode($content, true);
      if (!is_array($data)) {
        $ret['content'] = $content;
      }
      else {
        logger(print_r($data, true), 'Data');
        foreach ($data as $key => $value) {
          $ret[$key] = $value;
        }
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
});

$app->run();
