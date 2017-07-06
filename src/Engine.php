<?php

namespace Simples\Route;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Simples\Error\SimplesRunTimeError;
use Simples\Kernel\App;
use Simples\Kernel\Container;

/**
 * Class Engine
 * @package Simples\Route
 */
class Engine extends Base
{
    /**
     * @param string $type
     * @param mixed $callback
     */
    protected function deep(string $type, $callback)
    {
        switch ($type) {
            case 'file':
                $this->load(path(true, $callback));
                break;
            case 'files':
                foreach ($callback as $file) {
                    $this->load(path(true, $file));
                }
                break;
            case 'dir':
                $files = $this->files($callback);
                foreach ($files as $file) {
                    $this->load(path(true, $file));
                }
                break;
            case 'callable':
                call_user_func_array(
                    $callback,
                    Container::instance()->resolveFunctionParameters($callback, ['router' => $this])
                );
                break;
        }
    }

    /**
     * @param string $filename
     * @throws SimplesRunTimeError
     */
    public function load(string $filename)
    {
        if (!file_exists($filename)) {
            throw new SimplesRunTimeError("The file `{$filename}` was not found");
        }

        /** @noinspection PhpIncludeInspection */
        $callable = require_once $filename;
        if (is_callable($callable)) {
            call_user_func_array($callable, [$this]);
        }
    }

    /**
     * @param string $dir
     * @return array
     */
    public function files(string $dir)
    {
        $files = [];

        $dir = path(true, $dir);

        if (!is_dir($dir)) {
            return $files;
        }

        $directory = new RecursiveDirectoryIterator($dir);
        $resources = new RecursiveIteratorIterator($directory, RecursiveIteratorIterator::SELF_FIRST);
        foreach ($resources as $resource) {
            if (is_dir($resource->getFilename())) {
                continue;
            }
            $pattern = '/' . preg_quote(App::options('root'), '/') . '/';
            $file = preg_replace($pattern, '', $resource->getPathname(), 1);
            if ($file) {
                $files[] = $file;
            }
        }

        return $files;
    }

    /**
     * @param string $method
     * @param string $uri
     * @param array $options
     * @return Match
     */
    final public function match(string $method, string $uri, array $options = [])
    {
        $method = strtolower($method);

        $path = null;
        $callback = null;
        $data = [];

        foreach ($this->routes as $index => $routes) {
            if ($method !== $index) {
                continue;
            }
            $found = $this->search($routes, $uri, $options);
            if ($found) {
                App::log($found);
                $path = $found['path'];
                $callback = $found['callback'];
                $data = $found['data'];
                $options = $found['options'];
                break;
            }
        }

        $parameters = array_merge($data, ['data' => $this->data]);

        if (!$callback && isset($this->otherWise[$method])) {
            $context = $this->otherWise[$method];
            $path = '';
            $callback = $context['callback'];
            $options = array_merge($context['options'], $options);
        }

        return $this->resolve($method, $uri, $path, $callback, $parameters, $options);
    }

    /**
     * @param $routes
     * @param $uri
     * @param $options
     * @return array|null
     */
    private function search($routes, $uri, $options)
    {
        foreach ($routes as $path => $context) {
            if (!preg_match($path, $uri, $matches)) {
                continue;
            }
            $options = array_merge_recursive($context['options'], $options);
            array_shift($matches);
            $match = [
                'path' => $path,
                'callback' => $context['callback'],
                'data' => $this->parseData($matches, $context['labels']),
                'options' => $options
            ];
            return $match;
        }
        return null;
    }

    /**
     * @param array $matches
     * @param array $labels
     * @return array
     */
    private function parseData(array $matches, array $labels)
    {
        $data = $matches;
        if ($this->labels || (isset($options['labels']) ? $options['labels'] : false)) {
            foreach ($labels as $key => $label) {
                $data[$label] = $matches[$key];
            }
        }
        return $data;
    }

    /**
     * @param string $method
     * @param string $uri
     * @param string $path
     * @param callable $callback
     * @param array $parameters
     * @param array $options
     * @return Match
     */
    protected function resolve(string $method, string $uri, string $path, $callback, $parameters, $options)
    {
        $group = off($options, 'group');

        if ($group) {
            unset($options['group']);

            $this->clear();

            $this->deep($group['type'], $callback);

            $end = str_replace_first($group['start'], '', $uri);
            $uri = (substr($end, 0, 1) === '/') ? $end : '/' . $end;

            return $this->match($method, $uri, $options);
        }

        return $this->matchGroup($method, $uri, $path, $callback, $parameters, $options);
    }

    /**
     * @param string $method
     * @param string $uri
     * @param string $path
     * @param callable $callback
     * @param array $parameters
     * @param array $options
     * @return Match
     */
    private function matchGroup(string $method, string $uri, string $path, $callback, $parameters, $options)
    {
        if (isset($options['type']) || $this->type) {
            App::options('type', isset($options['type']) ? $options['type'] : $this->type);
        }
        if (isset($options['headers']) || $this->headers) {
            $headers = $this->headers;
            if (!$headers) {
                $headers = [];
            }
            if (isset($options['headers'])) {
                $headers = array_merge($headers, $options['headers']);
            }
            App::options('headers', $headers);
        }

        return new Match($method, $uri, $path, $callback, $parameters, $options);
    }
}
