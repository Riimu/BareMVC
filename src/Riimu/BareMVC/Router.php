<?php

namespace Riimu\BareMVC;

/**
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2013, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class Router
{
    private $defaultController = 'index';
    private $controllerFormat = '%sController';
    private $actionFormat = '%sAction';

    private $pathCache;
    private $basePath;
    private $self;
    private $url;

    public function __construct()
    {
        $this->basePath = substr($_SERVER['SCRIPT_NAME'], 0, strrpos($_SERVER['SCRIPT_NAME'], '/'));
        $this->pathCache = [];
        $this->self = null;

        $scheme = empty($_SERVER['REQUEST_SCHEME']) ? 'http' : $_SERVER['REQUEST_SCHEME'];
        $this->url = empty($_SERVER['HTTP_HOST'])
            ? null : $scheme . '://' . $_SERVER['HTTP_HOST'];
    }

    public function getRequestRoute($canonize = true)
    {
        $route = isset($_GET['path']) ? $_GET['path'] : null;

        try {
            $path = $this->getRoute($route);
        } catch (InvalidPathException $ex) {
            throw new PageNotFoundException("Path $route does not exist");
        }

        if ($canonize && $route !== $path['path'] && $this->url !== null) {
            header('location: ' . $this->url . $this->toPath($path['path']));
            exit;
        }

        parse_str(parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY), $_GET);

        return $path;
    }

    public function getRoute($route)
    {
        $params = empty($route) ? [] : explode('/', rtrim($route, '/'));
        $controller = array_shift($params);
        $action = array_shift($params);

        return $this->getPath($controller, $action, $params);
    }

    public function getPath($name = null, $action = null, array $params = null)
    {
        $path = $this->getOptimalPath($name, $action);
        $path['params'] = $params ?: [];

        if ($params) {
            $path['path'] = implode('/',
                array_merge([$path['controller'], $path['action']], $params)) . '/';
        }

        return $path;
    }

    private function getOptimalPath($name, $action)
    {
        $name = strtolower($name);
        $action = strtolower($action);

        if (isset($this->pathCache[$name][$action])) {
            return $this->pathCache[$name][$action];
        }

        $defaultName = strtolower($this->defaultController);
        $class = sprintf($this->controllerFormat, ucfirst($name ?: $defaultName));

        if (!class_exists($class) || !is_a($class, 'Riimu\BareMVC\Controller', true)) {
            throw new InvalidPathException("Controller '$class' not found");
        }

        $defaultAction = strtolower((new $class)->getDefaultAction());
        $method = sprintf($this->actionFormat, $action ?: $defaultAction);

        if (!method_exists($class, $method)) {
            throw new InvalidPathException("Action '$method' not found on '$class'");
        }

        if (!$action || $action === $defaultAction) {
            $path = !$name || $name === $defaultName ? '' : $name . '/';
        } else {
            $path = $name . '/' . $action . '/';
        }

        return $this->pathCache[$name][$action] = [
            'controller' => $name ?: $defaultName,
            'action' => $action ?: $defaultAction,
            'class' => $class,
            'method' => $method,
            'path' => $path,
        ];
    }

    public function setSelf($path)
    {
        $this->self = $path;
    }

    public function to($controller, $action = null, array $params = null, array $get = null)
    {
        $url = $this->toPath($this->getPath($controller, $action, $params)['path']);

        if ($get) {
            $url .= '?' . http_build_query($get, '', '&amp;');
        }

        return $url;
    }

    public function toSelf()
    {
        return $this->toPath($this->self);
    }

    public function toPath($path)
    {
        return $this->basePath . '/' . ltrim($path, '/');
    }
}

class PageNotFoundException extends \Exception { }
class InvalidPathException extends \Exception { }