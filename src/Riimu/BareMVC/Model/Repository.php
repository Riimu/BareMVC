<?php

namespace Riimu\BareMVC\Model;

/**
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2013, Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class Repository
{
    /**
     * @var \PDO
     */
    protected $db;
    protected $modelName;
    protected $tableName;
    protected $tablePrefix = '';

    public function __construct(\PDO $db)
    {
        $this->db = $db;
    }

    public function save (\Riimu\BareMVC\Model\Model $model)
    {
        if (!($model instanceof $this->modelName)) {
            throw new \RuntimeException('Cannot save model of type "' . get_class($model) . '"');
        }

        if ($model->isNew()) {
            $this->insert($model->getDatabaseValues());
            $primary = $model->getPrimaryKey();

            if (count($primary) === 1 && $model->get($primary[0]) === null) {
                $model->set($primary[0], $this->db->lastInsertId());
            }

            $model->setNewStatus(false);
        } else {
            $this->update($model->getDatabaseValues(), $model->get($model->getPrimaryKey()));
        }
    }

    protected function getTableName()
    {
        return $this->tablePrefix . $this->tableName;
    }

    protected function getFields($prefix = null, $table = true)
    {
        return $this->fields((new $this->modelName)->getDatabaseFields(),
            $prefix, $table === true ? $this->getTableName() : $table);
    }

    protected function getModel($values, $prefix = null)
    {
        $model = new $this->modelName();
        $model->setDatabaseValues($prefix === null
            ? $values : $this->getPrefixFields($values, $prefix));
        return $model;
    }

    public function findByPrimaryKey($value)
    {
        $keys = (new $this->modelName())->getPrimaryKey();
        $values = (array) $value;

        if (count($keys) !== count($values)) {
            throw new \InvalidArgumentException(
                "$this->modelName has " . count($keys) . " primary keys");
        } elseif (array_filter($values, 'is_scalar') != $values) {
            throw new \InvalidArgumentException(
                "All primary key values must be scalar");
        }

        $entries = $this->find(array_combine($keys, $values));
        return $entries ? $entries[0] : null;
    }

    protected function find(array $where = null, array $order = null, $limit = null, array $with = null)
    {
        if ($with) {
            $select = $this->getFields('this_', 'this');
            $from = "`{$this->getTableName()}` AS `this`";

            foreach ($with as $join) {
                if (!empty($join[4])) {
                    $select .= ', ' . $join[0]->getFields("$join[1]_", $join[1]);
                }

                $from .= " JOIN `{$join[0]->getTableName()}` AS `$join[1]` ON " .
                    "`$join[1]`.`$join[3]` = `this`.`$join[2]`";
            }

        } else {
            $select = $this->getFields(null, null);
            $from = "`" . $this->getTableName() . "`";
        }

        $params = [];
        $sql = "SELECT $select FROM $from" .
            ($where ? ' WHERE ' . $this->where($where, $params) : '') .
            ($order ? ' ORDER BY ' . $this->orderBy($order) : '') .
            ($limit ? ' LIMIT ' . $this->limit($limit) : '');

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $stmt->setFetchMode(\PDO::FETCH_ASSOC);

        $result = [];

        if ($with) {
            foreach ($stmt as $row) {
                $entry = $this->getModel($row, 'this_');

                foreach ($with as $join) {
                    if (!empty($join[4])) {
                        $join[4]($entry, $join[0]->getModel($row, "$join[1]_"));
                    }
                }

                $result[] = $entry;
            }
        } else {
            foreach ($stmt as $row) {
                $result[] = $this->getModel($row);
            }
        }

        return $result;
    }

    protected function count(array $where = null)
    {
        $sql = sprintf('SELECT COUNT(*) FROM `%s`', $this->getTableName());
        $params = [];

        if ($where !== null) {
            $sql .= ' WHERE ' . $this->where($where, $params);
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    protected function insert($values)
    {
        $sql = sprintf('INSERT INTO `%s` (`%s`) VALUES (%s)',
            $this->getTableName(),
            implode('`, `', array_keys($values)),
            implode(', ', array_fill(0, count($values), '?')));
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array_values($values));
    }

    protected function update($values, array $where)
    {
        $params = array_values($values);
        $sql = sprintf('UPDATE `%s` SET %s WHERE %s',
            $this->getTableName(),
            implode(', ', array_map(function ($field) {
                return "`$field` = ?";
            }, array_keys($values))),
            $this->where($where, $params));

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
    }

    protected function fields($fields, $prefix = null, $table = null)
    {
        $names = [];

        foreach ($fields as $field) {
            $names[] = ($table === null ? '' : "`$table`.") . "`$field`" .
                ($prefix === null ? '' : " AS `$prefix$field`");
        }

        return implode(', ', $names);
    }

    protected function where(array $where, & $params)
    {
        $ops = ['<', '>'];
        $clauses = [];

        foreach ($where as $field => $value) {
            $name = "`" . implode('`.`', explode('.', $field, 2)) . "`";

            if (is_null($value)) {
                $clauses[] = "$name IS NULL";
            } elseif (!is_array($value)) {
                $clauses[] = "$name = ?";
                $params[] = $value;
            } elseif (count($value) === 2 && in_array($value[0], $ops)) {
                $clauses[] = "$name $value[0] ?";
                $params[] = $value[1];
            } else {
                $clauses[] = "$name IN (" . implode(', ', array_fill(0, count($value), '?')) .")";
                $params = array_merge($params, $value);
            }
        }

        return implode(' AND ', $clauses);
    }

    protected function orderBy(array $order)
    {
        $clauses = [];

        foreach ($order as $field => $asc) {
            $name = "`" . implode('`.`', explode('.', $field, 2)) . "`";
            $clauses[] = "$name " . ($asc ? 'ASC' : 'DESC');
        }

        return implode(', ', $clauses);
    }

    protected function limit($limit)
    {
        if (is_array($limit)) {
            return ((int) $limit[0]) . ', ' . ((int) $limit[1]);
        }

        return (string)(int) $limit;
    }

    protected function getPrefixFields($row, $prefix)
    {
        $result = [];

        foreach ($row as $key => $value) {
            if (strpos($key, $prefix) === 0) {
                $result[substr($key, strlen($prefix))] = $value;
            }
        }

        return $result;
    }
}
