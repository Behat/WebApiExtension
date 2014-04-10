<?php
use Silex\Application;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

require_once __DIR__ . '/../vendor/autoload.php';

$app = new Silex\Application();


$app->match('echo', function (Application $app, Request $req) {
  $ret = array(
    'warning' => 'Do not expose this service in production : it is intrinsically unsafe',
  );
  $ret['method'] = $req->getMethod();
  foreach ($_REQUEST as $key => $value) {
    $ret[$key] = $value;
  }
  foreach (json_decode($req->getContent(false)) as $key => $value) {
    $ret[$key] = $value;
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
