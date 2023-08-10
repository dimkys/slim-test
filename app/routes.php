<?php

declare(strict_types=1);

use App\Application\Controllers\AuthController;
use Slim\App;
use Slim\Interfaces\RouteCollectorProxyInterface as Group;

return function (App $app) {
    $app->get('/', [AuthController::class, 'home']);
    $app->get('/logout', [AuthController::class, 'logout']);

    $app->group('/auth', function (Group $group) {
        $group->get('', [AuthController::class, 'auth']);
        $group->post('', [AuthController::class, 'login']);
        $group->get('/register', [AuthController::class, 'getRegister']);
        $group->post('/register', [AuthController::class, 'postRegister']);
    });
};
