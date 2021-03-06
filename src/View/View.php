<?php

namespace Riimu\BareMVC\View;

/**
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2013, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class View
{
    private $viewFormat;
    private $view;
    private $variables;
    private $parent;

    public function __construct()
    {
        $this->viewFormat = '%s';
        $this->view = null;
        $this->variables = [];
        $this->parent = null;
    }

    public function setViewFormat($format)
    {
        $this->viewFormat = $format;
    }

    public function setView($view)
    {
        $this->view = $view;
    }

    public function getSub($view)
    {
        $sub = new View();
        $sub->viewFormat = $this->viewFormat;
        $sub->view = $view;
        $sub->parent = $this;

        return $sub;
    }

    public function setSubView($name, $view)
    {
        return $this->variables[$name] = $this->getSub($view);
    }

    public function getVariables()
    {
        return $this->parent === null
            ? $this->variables
            : $this->variables + $this->parent->getVariables();
    }

    public function output(array $variables = null)
    {
        extract($this->getVariables());

        if ($variables !== null) {
            extract($variables);
        }

        require sprintf($this->viewFormat, $this->view);
    }

    public function __set($name, $value)
    {
        $this->variables[$name] = $value;
    }

    public function __get($name)
    {
        if (!isset($this->variables[$name])) {
            if ($this->parent === null) {
                throw new \OutOfBoundsException("'$name' variable does not exist in the view");
            }

            return $this->parent->__get($name);
        }

        return $this->variables[$name];
    }

    public function __isset($name)
    {
        return isset($this->variables[$name]);
    }

    public function __unset($name)
    {
        unset($this->variables[$name]);
    }
}
