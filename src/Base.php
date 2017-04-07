<?php

namespace Simples\Route;

use Simples\Http\Response;

/**
 * Class Base
 * @package Simples\Route
 */
class Base
{
    /**
     * @trait Share
     */
    use Sharable;

    /**
     * @var array honorable mention ['options']
     */
    const ALL = ['get', 'post', 'put', 'patch', 'delete'];

    /**
     * @var array
     */
    protected $routes = [];

    /**
     * @var array
     */
    protected $debug = [];

    /**
     * @var array
     */
    protected $otherWise = [];

    /**
     * @var string
     */
    protected $preFlight = 'options';

    /**
     * @var bool
     */
    protected $labels;

    /**
     * @var string
     */
    protected $type;

    /**
     * @var array
     */
    protected $headers;

    /**
     *
     * Engine constructor.
     * @param bool $labels
     * @param string|null $contentType
     * @param array|null $headers
     */
    public function __construct(bool $labels = false, string $contentType = null, array $headers = null)
    {
        $this->labels = $labels;
        $this->type = coalesce($contentType, Response::CONTENT_TYPE_PLAIN);
        $this->headers = $headers;
    }

    /**
     * @param $method
     * @param $arguments
     * @return $this
     */
    final public function __call($method, $arguments)
    {
        if (!isset($arguments[1])) {
            return $this;
        }
        $uris = $arguments[0];
        if (!is_array($uris)) {
            $uris = [$uris];
        }
        $callback = $arguments[1];
        $options = isset($arguments[2]) ? $arguments[2] : [];

        foreach ($uris as $uri) {
            $this->on($method, $uri, $callback, $options);
        }
        return $this;
    }

    /**
     * @return $this
     */
    final public function clear()
    {
        $this->routes = [];

        return $this;
    }

    /**
     * @param $methods
     * @param $uri
     * @param $callback
     * @param array $options
     * @return $this
     */
    final public function on($methods, $uri, $callback, $options = [])
    {
        if ($methods === '*') {
            $methods = self::ALL;
        }
        if (gettype($methods) === 'string') {
            $methods = explode(',', $methods);
        }

        foreach ($methods as $method) {
            $method = strtolower($method);
            if (!isset($this->routes[$method])) {
                $this->routes[$method] = [];
            }
            $pattern = $this->pattern($uri);

            $route = $pattern['pattern'] . '$/';

            $this->routes[$method][$route] = [
                'uri' => $uri, 'callback' => $callback, 'options' => $options, 'labels' => $pattern['labels']
            ];
        }

        return $this;
    }

    /**
     * @param string $uri
     * @return array
     */
    public function pattern(string $uri)
    {
        $labels = [];

        $uri = (substr($uri, 0, 1) !== '/') ? '/' . $uri : $uri;
        $peaces = explode('/', $uri);

        foreach ($peaces as $key => $value) {
            $peaces[$key] = str_replace('*', '(.*)', $peaces[$key]);
            if (strpos($value, ':') === 0) {
                $peaces[$key] = '(\w+)';
                $labels[] = substr($value, 1);
                continue;
            }
            if (strpos($value, '{') === 0) {
                $peaces[$key] = '(\w+)';
                $labels[] = substr($value, 1, -1);
            }
        }
        if ($peaces[(count($peaces) - 1)]) {
            $peaces[] = '';
        }
        $pattern = str_replace('/', '\/', implode('/', $peaces));
        return [
            'pattern' => '/^' . $pattern,
            'labels' => $labels
        ];
    }

    /**
     * @param array $trace
     * @return array
     */
    public function getTrace($trace = [])
    {
        $groups = [];
        foreach ($this->routes as $method => $paths) {
            foreach ($paths as $route) {
                $trace[] = [
                    'method' => $method,
                    'uri' => $route['uri'],
                    'options' => $route['options'],
                    'callback' => stripslashes(json_encode($route['callback']))
                ];
                $group = off($route['options'], 'group');
                if (!$group) {
                    continue;
                }
                $groups[] = [
                    'type' => $group['type'], 'callback' => $route['callback']
                ];
            }
        }

        foreach ($this->otherWise as $method => $othersWise) {
            $trace[] = [
                'method' => $method,
                'uri' => '/other-wise',
                'options' => $othersWise['options'],
                'callback' => stripslashes(json_encode($othersWise['callback']))
            ];
        }
        $this->otherWise = [];

        if (count($groups)) {
            foreach ($groups as $group) {
                $this->clear();
                $this->deep($group['type'], $group['callback']);

                $trace = $this->getTrace($trace);
            }
        }

        return $trace;
    }

    /**
     * @param $name
     * @param $value
     */
    public function addHeader($name, $value)
    {
        if (!is_array($this->headers)) {
            $this->headers = [];
        }
        $this->headers[$name] = $value;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType(string $type)
    {
        $this->type = $type;
    }
}
