<?php

namespace Riimu\BareMVC;

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

    public function save (\Site\Model\Model $model)
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

    public function findByPrimaryKey($value)
    {
        $primary = (new $this->modelName())->getPrimaryKey();

        if (count((array) $value) !== count($primary)) {
            throw new \InvalidArgumentException("$this->modelName must have " . count($primary) . " primary keys");
        }

        $entries = $this->find(array_combine($primary, (array) $value));
        return $entries ? $entries[0] : null;
    }

    protected function find(array $where)
    {
        $model = new $this->modelName();
        $params = [];
        $sql = sprintf('SELECT `%s` FROM `%s` WHERE %s',
            implode('`, `', $model->getDatabaseFields()),
            $this->getTableName(),
            $this->buildWhereStatement($where, $params));
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $stmt->setFetchMode(\PDO::FETCH_ASSOC);

        $entries = [];

        foreach ($stmt as $row) {
            $model = new $this->modelName();
            $model->setDatabaseValues($row);
            $entries[] = $model;
        }

        return $entries;
    }

    protected function count(array $where = null)
    {
        $sql = sprintf('SELECT COUNT(*) FROM `%s`', $this->getTableName());
        $params = [];

        if ($where !== null) {
            $sql .= ' WHERE ' . $this->buildWhereStatement($where, $params);
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
            $this->buildWhereStatement($where, $params));

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
    }

    private function buildWhereStatement(array $where, & $params)
    {
        $clauses = [];

        foreach ($where as $field => $value) {
            $clauses[] = "`$field` = ?";
            $params[] = $value;
        }

        return implode(' AND ', $clauses);
    }
}
