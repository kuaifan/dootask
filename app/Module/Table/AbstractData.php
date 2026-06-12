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
        // 非 Swoole 运行时（artisan/测试）无 swoole 绑定，table 为 null，各方法返回默认值
        $this->table = app()->bound('swoole') ? app('swoole')->{$this->getTableName()} : null;
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
        if (!self::instance()->table) {
            return false;
        }
        return self::instance()->table->set($key, ['value' => $value]);
    }

    public static function get($key, $default = null)
    {
        if (!self::instance()->table) {
            return $default;
        }
        $data = self::instance()->table->get($key);
        return $data ? $data['value'] : $default;
    }

    public static function del($key)
    {
        if (!self::instance()->table) {
            return false;
        }
        return self::instance()->table->del($key);
    }

    public static function exist($key)
    {
        if (!self::instance()->table) {
            return false;
        }
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
        if (!self::instance()->table) {
            return;
        }
        foreach (self::instance()->table as $key => $row) {
            self::del($key);
        }
    }

    public static function getAll()
    {
        if (!self::instance()->table) {
            return [];
        }
        $result = [];
        foreach (self::instance()->table as $key => $row) {
            $result[$key] = $row['value'];
        }
        return $result;
    }
}
