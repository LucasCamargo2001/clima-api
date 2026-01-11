<?php

use Cake\Routing\Route\DashedRoute;
use Cake\Routing\RouteBuilder;

return function (RouteBuilder $routes): void {

    $routes->setRouteClass(DashedRoute::class);

    $routes->scope('/', function (RouteBuilder $builder): void {

        $builder->connect('/', ['controller' => 'Pages', 'action' => 'display', 'home']);

        $builder->connect('/pages/*', 'Pages::display');

        $builder->connect(
            '/api/weather/{city}',
            ['controller' => 'Weather', 'action' => 'view']
        )->setPass(['city']);

        $builder->fallbacks();
    });
};
