<?php

require_once 'Router.php';
require_once 'RateLimiter.php';

$router = new Router();

$router->put('/newsletter/subscribe', function() {
    return json_encode([
        'message' => 'You have been subscribed to the newsletter.'
    ], JSON_THROW_ON_ERROR);
}, ['RateLimiter']);

$router->group('/categories', function(Router $router) {
    $router->get('/', [CategoryController::class, 'index']);
    $router->get('/{category_name}/products', [CategoryController::class, 'getProductsByCategory']);
});

$router->run();
