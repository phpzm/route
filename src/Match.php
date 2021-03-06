<?php

namespace Simples\Route;

use Simples\Unit\Origin;
use Throwable;

/**
 * Class Route
 * @package Simples\Route
 */
class Match extends Origin
{
    /**
     * @var string
     */
    private $method;
    /**
     * @var string
     */
    private $uri;
    /**
     * @var string
     */
    private $path;
    /**
     * @var callable
     */
    private $callback;
    /**
     * @var array
     */
    private $parameters;
    /**
     * @var array
     */
    private $options;

    /**
     * Route constructor.
     * @param string $method
     * @param string $uri
     * @param string $path
     * @param string|callable|Throwable $callback
     * @param array $parameters
     * @param array $options
     */
    public function __construct($method, $uri, $path, $callback, $parameters, $options)
    {
        $this->method = $method;
        $this->uri = $uri;
        $this->path = $path;
        $this->callback = $callback;
        $this->parameters = $parameters;
        $this->options = $options;
    }

    /**
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @param string $method
     * @return Match
     */
    public function setMethod(string $method)
    {
        $this->method = $method;
        return $this;
    }

    /**
     * @return string
     */
    public function getUri()
    {
        return $this->uri;
    }

    /**
     * @param string $uri
     * @return Match
     */
    public function setUri(string $uri)
    {
        $this->uri = $uri;
        return $this;
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @param string $path
     * @return Match
     */
    public function setPath(string $path)
    {
        $this->path = $path;
        return $this;
    }

    /**
     * @return callable
     */
    public function getCallback()
    {
        return $this->callback;
    }

    /**
     * @param string|callable|Throwable $callback
     * @return Match
     */
    public function setCallback($callback)
    {
        $this->callback = $callback;
        return $this;
    }

    /**
     * @return array
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * @param array $parameters
     * @return Match
     */
    public function setParameters(array $parameters)
    {
        $this->parameters = $parameters;
        return $this;
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @param array $options
     * @return Match
     */
    public function setOptions(array $options)
    {
        $this->options = $options;
        return $this;
    }

    /**
     * @param $option
     * @return mixed|null
     */
    public function getOption($option)
    {
        return $this->options[$option] ?? null;
    }

    /**
     * @return mixed
     */
    protected function expose()
    {
        return [
            'method' => $this->method,
            'uri' => $this->uri,
            'path' => $this->path,
            'callback' => $this->callback,
            'parameters' => $this->parameters,
            'options' => $this->options,
        ];
    }
}
