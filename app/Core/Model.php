<?php

declare(strict_types=1);

namespace App\Core;

use PDO;

abstract class Model
{
    protected PDO $db;

    protected string $table = '';
    protected string $primaryKey = 'id';
    protected array $fillable = [];

    public function __construct(?PDO $pdo = null)
    {
        if ($pdo !== null) {
            $this->db = $pdo;
        } else {
            $this->db = \App\Config\Database::getPdo();
        }

        if (empty($this->table)) {
            $classParts = explode('\\', static::class);
            $className = end($classParts);
            $this->table = strtolower(str_replace('Model', '', $className)) . 's';
        }
    }

    public function find(int|string $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ? LIMIT 1");
        $stmt->execute([$id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public function create(array $data): int|string|false
    {
        $data = $this->filterFillable($data);
        if (empty($data)) {
            return false;
        }

        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        
        $sql = "INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders})";
        $stmt = $this->db->prepare($sql);
        
        if ($stmt->execute(array_values($data))) {
            return $this->db->lastInsertId();
        }
        
        return false;
    }

    public function update(int|string $id, array $data): bool
    {
        $data = $this->filterFillable($data);
        if (empty($data)) {
            return false;
        }

        $escapedKeys = array_map(fn($col) => '`' . str_replace('`', '', (string) $col) . '`', array_keys($data));
        $setClause = implode(' = ?, ', $escapedKeys) . ' = ?';
        
        $sql = "UPDATE `{$this->table}` SET {$setClause} WHERE `{$this->primaryKey}` = ?";
        $stmt = $this->db->prepare($sql);
        
        $values = array_values($data);
        $values[] = $id;
        
        return $stmt->execute($values);
    }

    public function delete(int|string $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM `{$this->table}` WHERE `{$this->primaryKey}` = ?");
        return $stmt->execute([$id]);
    }

    public function all(): array
    {
        $stmt = $this->db->query("SELECT * FROM `{$this->table}`");
        return $stmt->fetchAll();
    }

    public function where(string $column, mixed $value): array
    {
        $cleanCol = '`' . str_replace('`', '', $column) . '`';
        $stmt = $this->db->prepare("SELECT * FROM `{$this->table}` WHERE {$cleanCol} = ?");
        $stmt->execute([$value]);
        return $stmt->fetchAll();
    }

    public function firstWhere(string $column, mixed $value): ?array
    {
        $cleanCol = '`' . str_replace('`', '', $column) . '`';
        $stmt = $this->db->prepare("SELECT * FROM `{$this->table}` WHERE {$cleanCol} = ? LIMIT 1");
        $stmt->execute([$value]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    protected function filterFillable(array $data): array
    {
        if (empty($this->fillable)) {
            return $data;
        }
        return array_intersect_key($data, array_flip($this->fillable));
    }
}
