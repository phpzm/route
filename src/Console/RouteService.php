<?php

namespace Simples\Route\Console;

use Simples\Console\Service;
use Simples\Http\Kernel\Http;
use Simples\Route\Router;
use Simples\Kernel\App;

/**
 * Class RouteService
 * @package Simples\Console
 */
abstract class RouteService extends Service
{
    /**
     * @param array $parameters
     * @SuppressWarnings("unused")
     */
    public static function execute(array $parameters = [])
    {
        $router = new Router(App::options('labels'), App::options('type'));

        $routes = Http::routes($router)->getTrace();

        echo str_pad('METHOD', 10, ' '), ' | ', str_pad('URI', 50, ' '), ' | ', str_pad('GROUP', 6, ' '), PHP_EOL,
        str_pad('', 140, '-'), PHP_EOL;

        foreach ($routes as $route) {
            echo
            str_pad($route['method'], 10, ' '), ' | ',
            str_pad($route['uri'], 50, ' '), ' | ',
            str_pad(!!off($route['options'], 'group'), 6, ' '), ' | ', $route['callback'], PHP_EOL;
        }
    }
}
