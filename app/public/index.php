<?php

require_once ('../vendor/autoload.php');

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__.'/..');
$dotenv->load();

$router = new \Bramus\Router\Router();

$router->setNamespace('Http');
$router->get('/', 'FoodController@show');
$router->get('/add', 'FoodController@showAdd');
$router->post('/add', 'FoodController@add');
$router->post('/restaurant/(\d*)/vote', 'FoodController@vote');
$router->post('/reset','FoodController@reset');

$router->run();
