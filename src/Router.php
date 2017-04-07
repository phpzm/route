<?php

namespace Simples\Route;

use Simples\Kernel\App;

/**
 * Class Router
 * @package Simples\Route
 *
 * @method Router get($route, $callable, $options = [])
 * @method Router post($route, $callable, $options = [])
 * @method Router put($route, $callable, $options = [])
 * @method Router patch($route, $callable, $options = [])
 * @method Router delete($route, $callable, $options = [])
 */
class Router extends Engine
{
    /**
     *
     * Router constructor.
     * @param $labels
     * @param $contentType
     * @param $headers
     */
    public function __construct($labels = false, $contentType = null, $headers = null)
    {
        parent::__construct($labels, $contentType, $headers);
    }

    /**
     * TODO: exceptions to error input parameters
     * @param $method
     * @param $start
     * @param $context
     * @param array $options
     * @return $this
     */
    public function group($method, $start, $context, $options = [])
    {
        $type = '';

        switch (gettype($context)) {
            case TYPE_ARRAY:
                $context = $this->fixGroupArray($context);
                $type = 'files';
                break;
            case TYPE_STRING:
                $path = path(true, $context);
                $isFile = file_exists($path);
                if ($isFile) {
                    $type = 'file';
                }
                if ($isFile && is_dir($path)) {
                    $type = 'dir';
                }
                break;
        }
        if (is_callable($context)) {
            $type = 'callable';
        }

        $start = (substr($start, 0, 1) === '/' ? $start : '/' . $start);
        $start = (substr($start, -1) === '/' ? substr($start, 0, -1) : $start);

        $options['group'] = ['start' => $this->pattern($start)['pattern'] . '/', 'type' => $type];

        $uri = $start . '*';

        $this->on($method, $uri, $context, $options);

        return $this;
    }

    /**
     * @param $method
     * @param $callback
     * @param array $options
     * @return $this
     */
    public function otherWise($method, $callback, $options = [])
    {
        if ($method === '*') {
            $method = self::ALL;
        }
        if (!is_array($method)) {
            $method = [$method];
        }
        foreach ($method as $item) {
            $this->otherWise[strtolower($item)] = ['callback' => $callback, 'options' => $options];
        }

        return $this;
    }

    /**
     * @param $uri
     * @param $class
     * @param array $options
     * @return $this
     */
    public function resource($uri, $class, $options = [])
    {
        $resource = [
            ['method' => 'GET', 'uri' => 'index', 'callable' => 'index'],

            ['method' => 'GET', 'uri' => '', 'callable' => 'index'],
            ['method' => 'GET', 'uri' => 'create', 'callable' => 'create'],
            ['method' => 'GET', 'uri' => ':id', 'callable' => 'show'],
            ['method' => 'GET', 'uri' => ':id/edit', 'callable' => 'edit'],

            ['method' => 'POST', 'uri' => '', 'callable' => 'store'],
            ['method' => 'PUT,PATCH', 'uri' => ':id', 'callable' => 'update'],
            ['method' => 'DELETE', 'uri' => ':id', 'callable' => 'destroy'],
        ];

        $separator = App::options('separator');

        foreach ($resource as $item) {
            $item = (object)$item;
            $this->on($item->method, "{$uri}/{$item->uri}", "{$class}{$separator}{$item->callable}", $options);
        }

        return $this;
    }

    /**
     * @param $uri
     * @param $class
     * @param array $options
     * @return $this
     */
    public function api($uri, $class, $options = [])
    {
        $resource = [
            ['method' => 'GET', 'uri' => '', 'callable' => 'search'],
            ['method' => 'GET', 'uri' => ':id', 'callable' => 'get'],
            ['method' => 'PATCH', 'uri' => ':id/recycle', 'callable' => 'recycle'],
            ['method' => 'POST', 'uri' => '', 'callable' => 'post'],
            ['method' => 'PUT', 'uri' => ':id', 'callable' => 'put'],
            ['method' => 'DELETE', 'uri' => ':id', 'callable' => 'delete']
        ];

        $separator = App::options('separator');

        foreach ($resource as $item) {
            $item = (object)$item;
            $this->on($item->method, "{$uri}/{$item->uri}", "{$class}{$separator}{$item->callable}", $options);
        }

        return $this;
    }

    /**
     * @param array $context
     * @return array
     */
    private function fixGroupArray(array $context)
    {
        foreach ($context as $index => $file) {
            if (!file_exists(path(true, $file))) {
                unset($context[$index]);
            }
        }
        return $context;
    }
}
