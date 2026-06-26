<?php

declare(strict_types=1);

/**
 * Front controller — jediný vstupní bod aplikace.
 * Všechny HTTP požadavky sem směruje .htaccess (mod_rewrite).
 */

require dirname(__DIR__) . '/autoload.php';

use App\Controller\TaskController;
use App\Core\Router;

$controller = new TaskController();
$router = new Router();

$router->add('GET', '/', static fn () => $controller->index());
$router->add('POST', '/tasks', static fn () => $controller->store());
$router->add('POST', '/tasks/{id}/toggle', static fn (int $id) => $controller->toggle($id));
$router->add('POST', '/tasks/{id}/delete', static fn (int $id) => $controller->destroy($id));

$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
