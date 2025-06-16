<?php

namespace App\Module;

use Closure;
use Exception;
use Illuminate\Support\Facades\Redis;

class Lock
{
    /**
     * 使用Redis分布式锁执行闭包
     * @param string $key 锁的key
     * @param Closure $closure 要执行的闭包函数
     * @param int $ttl 锁的过期时间（毫秒），默认10000（10秒）
     * @param int $waitTimeout 等待锁的超时时间（毫秒），0表示不等待，默认10000（10秒）
     * @return mixed 闭包函数的返回值
     * @throws Exception 如果获取锁失败或闭包执行异常
     */
    public static function withLock(string $key, Closure $closure, int $ttl = 10000, int $waitTimeout = 10000)
    {
        $lockKey = "lock:{$key}";
        $lockValue = uniqid('', true); // 生成唯一值，用于安全释放锁

        // 尝试获取锁，如果waitTimeout为0则直接返回false，否则等待指定时间
        $acquired = false;
        if ($waitTimeout > 0) {
            $end = microtime(true) + ($waitTimeout / 1000);
            while (microtime(true) < $end) {
                if (Redis::set($lockKey, $lockValue, 'PX', $ttl, 'NX')) {
                    $acquired = true;
                    break;
                }
                usleep(100000); // 休眠100ms后重试
            }
        } else {
            $acquired = Redis::set($lockKey, $lockValue, 'PX', $ttl, 'NX');
        }

        if (!$acquired) {
            throw new Exception("Failed to acquire lock for key: {$key}");
        }

        try {
            // 执行闭包
            return $closure();
        } finally {
            // 安全释放锁（仅当锁值未变时删除）
            Redis::eval("if redis.call('get', KEYS[1]) == ARGV[1] then return redis.call('del', KEYS[1]) else return 0 end", 1, $lockKey, $lockValue);
        }
    }
}
