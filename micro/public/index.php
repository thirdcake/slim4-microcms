<?php

use Slim\Factory\AppFactory;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\Views\Twig;
use Microcms\Client;

require_once ($_SERVER["DOCUMENT_ROOT"]."/../vendor/autoload.php");

$app = AppFactory::create();

$app->addRoutingMiddleware();
$errorMiddleware = $app->addErrorMiddleware(true, true, true); // 本番では第1引数をfalseにする

function cms () {
  $client = new Client(
    "YOUR_DOMAIN",  // YOUR_DOMAIN is the XXXX part of XXXX.microcms.io
    "YOUR_API_KEY"  // API Key
  );
  return $client;
}

function view () {
  $twig = Twig::create(
    $_SERVER["DOCUMENT_ROOT"]."/../src/templates",
    //['cache' => $_SERVER["DOCUMENT_ROOT"].'/../src/cache']  // 本番ではキャッシュを有効にする
  );
  return $twig;
}

$app->setBasePath("/micro/public");  // 本番ではこの行を消す

$app->get("/a/{article_id}.html", function (ServerRequestInterface $request, ResponseInterface $response, array $args) : ResponseInterface {
  // 記事ページ
  $client = cms();
  $assign["object"] = $client->get("blogs", $args["article_id"]);
  $twig = view();
  $html = $twig->render($response, "single.html", $assign);
  return $response;
});
$app->get("/c/{category_id}.html", function (ServerRequestInterface $request, ResponseInterface $response, array $args) : ResponseInterface {
  // カテゴリーページ
  $page = filter_input(INPUT_GET, 'p', FILTER_VALIDATE_INT);
  $page = ($page && $page >= 0)? $page : 0;
  $client = cms();
  $assign["list"] = $client->list("blogs", [
    "limit" => 10,
    "offset" => $page * 10,
    "orders" => ["-revisedAt"],
    "fields" => ["id","revisedAt","title","eyecatch","category"],
    "filters" => "category[equals]".$args["category_id"],
    "depth" => 1
  ]);
  $assign["category_name"] = $assign["list"]->contents[0]->category->name;
  $twig = view();
  $html = $twig->render($response, "list.html", $assign);
  return $response;
});
$app->get("/[index.html]", function (ServerRequestInterface $request, ResponseInterface $response) : ResponseInterface {
  // トップページ
  $page = filter_input(INPUT_GET, 'p', FILTER_VALIDATE_INT);
  $page = ($page && $page >= 0)? $page : 0;
  $client = cms();
  $assign["list"] = $client->list("blogs", [
    "limit" => 10,
    "offset" => $page * 10,
    "orders" => ["-revisedAt"],
    "fields" => ["id","revisedAt","title","eyecatch","category"],
    "depth" => 1
  ]);
  $twig = view();
  $html = $twig->render($response, "list.html", $assign);
  return $response;
});

$app->run();

