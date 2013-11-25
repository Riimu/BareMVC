<?php

namespace Riimu\BareMVC;

/**
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2013, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class Model
{
    protected $primaryKey;
    protected $fields;
    protected $values;

    private $new;

    public function __construct(array $values = null)
    {
        $this->new = true;

        foreach ($this->fields as $field) {
            $this->values[$field] = null;
        }

        if ($values !== null) {
            $this->set($values);
        }
    }

    public function getPrimaryKey()
    {
        return (array) $this->primaryKey;
    }

    public function getDatabaseFields()
    {
        return $this->fields;
    }

    public function __call($name, $arguments)
    {
        $type = substr($name, 0, 3);
        $field = $this->formatFieldName(substr($name, 3));

        if (($type !== 'set' && $type !== 'get') || !in_array($field, $this->fields)) {
            throw new \BadMethodCallException('Undefined method "' . $name . '" on ' . get_class($this));
        }

        if ($type === 'set') {
            $this->values[$field] = $arguments[0];
        }

        return $type === 'set' ? $this : $this->values[$field];
    }

    public function set($name, $value = null)
    {
        $values = func_num_args() > 1 ? [$name => $value] : $name;

        foreach ($values as $field => $set) {
            $method = 'set' . $this->formatMethodName($field);
            $this->$method($set);
        }

        return $this;
    }

    public function get($name)
    {
        foreach ((array) $name as $field) {
            $method = 'get' . $this->formatMethodName($field);
            $values[$field] = $this->$method();
        }

        return is_array($name) ? $values : reset($values);
    }

    private function formatMethodName($field)
    {
        return ucfirst(preg_replace_callback('/_([a-z]?)/', function ($match) {
            return strtoupper($match[1]);
        }, $field));
    }

    private function formatFieldName($method)
    {
        return preg_replace_callback('/[A-Z]/', function ($match) {
            return '_' . strtolower($match[0]);
        }, lcfirst($method));
    }

    public function isNew()
    {
        return $this->new;
    }

    public function setNewStatus($new)
    {
        $this->new = (bool) $new;
        return $this;
    }

    public function getDatabaseValues()
    {
        return $this->values;
    }

    public function setDatabaseValues($values)
    {
        if (array_keys($values) != $this->fields) {
            throw new \RuntimeException('Invalid Database Row for ' . get_class($this));
        }

        $this->values = $values;
        $this->new = false;
    }
}
