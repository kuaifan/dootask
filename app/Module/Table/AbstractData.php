<?php

namespace App\Module\Table;

use ReflectionClass;
use Swoole\Table;

abstract class AbstractData
{
    /** @var self */
    protected static $instance = null;

    /** @var Table */
    protected $table;

    protected function getTableName(): string
    {
        $className = (new ReflectionClass(static::class))->getShortName();
        return lcfirst($className) . 'Table';
    }

    private function __clone() {}
    private function __wakeup() {}

    protected function __construct()
    {
        $this->table = app('swoole')->{$this->getTableName()};
    }

    public function getTable()
    {
        return $this->table;
    }

    public static function instance()
    {
        if (static::$instance === null) {
            static::$instance = new static();
        }
        return static::$instance;
    }

    public static function set($key, $value)
    {
        return self::instance()->table->set($key, ['value' => $value]);
    }

    public static function get($key, $default = null)
    {
        $data = self::instance()->table->get($key);
        return $data ? $data['value'] : $default;
    }

    public static function del($key)
    {
        return self::instance()->table->del($key);
    }

    public static function exist($key)
    {
        return self::instance()->table->exist($key);
    }

    public static function setMultiple(array $items)
    {
        foreach ($items as $key => $value) {
            self::set($key, $value);
        }
    }

    public static function clear()
    {
        foreach (self::instance()->table as $key => $row) {
            self::del($key);
        }
    }

    public static function getAll()
    {
        $result = [];
        foreach (self::instance()->table as $key => $row) {
            $result[$key] = $row['value'];
        }
        return $result;
    }
}
