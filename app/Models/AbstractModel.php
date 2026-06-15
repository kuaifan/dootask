<?php

namespace App\Models;

use App\Exceptions\ApiException;
use App\Module\Base;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * App\Models\AbstractModel
 *
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel query()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel saveOrIgnore()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel getKeyValue()
 * @method static \Illuminate\Database\Eloquent\Model|object|static|null cancelAppend()
 * @method static \Illuminate\Database\Eloquent\Model|object|static|null cancelHidden()
 * @method static \Illuminate\Database\Eloquent\Builder|static with($relations)
 * @method static \Illuminate\Pagination\LengthAwarePaginator paginate(callable $callback)
 * @method int change(array $array)
 * @method int remove()
 * @mixin \Eloquent
 */
class AbstractModel extends Model
{
    use HasFactory;

    const ID = 'id';

    /**
     * 全局日期字段（Laravel 10 移除 $dates 属性后改经 getCasts 合并，子模型 $casts 同名键优先）
     */
    protected $defaultDatetimeCasts = [
        'top_at',
        'last_at',

        'start_at',
        'end_at',

        'archived_at',
        'complete_at',
        'loop_at',

        'receive_at',

        'line_at',
        'disable_at',

        'clear_at',

        'read_at',
        'done_at',
        'remind_at',
        'reminded_at',

        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function getCasts(): array
    {
        $casts = parent::getCasts();
        foreach ($this->defaultDatetimeCasts as $field) {
            $casts[$field] ??= 'datetime';
        }
        return $casts;
    }

    protected $appendattrs = [];

    /**
     * 通过模型修改数据
     * @param AbstractModel $builder
     * @param $array
     * @return int
     */
    protected function scopeChange($builder, $array)
    {
        $line = 0;
        $rows = $builder->get();
        foreach ($rows as $row) {
            $row->updateInstance($array);
            if ($row->save()) {
                $line++;
            }
        }
        return $line;
    }

    /**
     * 通过模型删除数据
     * @param AbstractModel $builder
     * @return int
     */
    protected function scopeRemove($builder)
    {
        $line = 0;
        $rows = $builder->get();
        foreach ($rows as $row) {
            if ($row->delete()) {
                $line++;
            }
        }
        return $line;
    }

    /**
     * 保存数据忽略错误
     * @return bool
     */
    protected function scopeSaveOrIgnore()
    {
        try {
            return $this->save();
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * 获取模型主键的值（如果没有则先保存）
     * @return mixed
     */
    protected function scopeGetKeyValue()
    {
        $key = $this->getKeyName();
        if (!isset($this->$key)) {
            $this->save();
        }
        return $this->$key;
    }

    /**
     * 取消附加值
     * @return static
     */
    protected function scopeCancelAppend()
    {
        return $this->setAppends([]);
    }

    /**
     * 取消隐藏值
     * @return static
     */
    protected function scopeCancelHidden()
    {
        return $this->setHidden([]);
    }

    /**
     * 为数组 / JSON 序列化准备日期。
     * @param DateTimeInterface $date
     * @return string
     */
    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format($this->dateFormat ?: 'Y-m-d H:i:s');
    }

    /**
     * 通过模型创建实例
     * @param array $param
     * @param bool $force
     * @return static
     */
    public static function fillInstance(array $param = [], bool $force = true)
    {
        $instance = new static;
        if ($param) {
            if ($force) {
                $instance->forceFill($param);
            } else {
                $instance->fill($param);
            }
        }
        return $instance;
    }

    /**
     * 创建/更新数据
     * @param array $param
     * @param null $id
     * @return AbstractModel|AbstractModel[]|\Illuminate\Database\Eloquent\Collection|Model|static
     */
    public static function createInstance($param = [], $id = null)
    {
        if ($id) {
            $instance = static::findOrFail($id);
        } else {
            $instance = new static;
        }
        if ($param) {
            $instance->updateInstance($param);
        }
        return $instance;
    }

    /**
     * 覆写框架 saveOrIgnore 的底层插入逻辑。
     *
     * 框架默认走 insertOrIgnoreReturning（INSERT ... ON CONFLICT ... RETURNING），
     * MySQL/MariaDB 的 grammar 不支持该变体，会抛
     * "This database engine does not support insert or ignore with returning."。
     * 这里改用 MySQL 支持的 INSERT IGNORE，并在成功插入时手动回填自增ID，
     * 保持与框架一致的返回语义（冲突被忽略时返回 false）。
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  array|string|null  $uniqueBy
     * @return bool
     */
    protected function performInsertOrIgnore(Builder $query, array|string|null $uniqueBy)
    {
        // MySQL INSERT IGNORE 无法按指定列限制冲突范围，所有 unique 冲突一并吞掉。
        // 若调用方传了 $uniqueBy 期望精确 scope，这里直接抛错，避免与框架语义偷偷不一致。
        if ($uniqueBy !== null) {
            throw new \InvalidArgumentException('saveOrIgnore $uniqueBy is not supported on MySQL driver; pass null.');
        }

        if ($this->usesUniqueIds()) {
            $this->setUniqueIds();
        }

        if ($this->fireModelEvent('creating') === false) {
            return false;
        }

        if ($this->usesTimestamps()) {
            $this->updateTimestamps();
        }

        $attributes = $this->getAttributesForInsert();

        if (empty($attributes)) {
            return true;
        }

        if ($query->toBase()->insertOrIgnore($attributes) === 0) {
            return false;
        }

        if ($this->getIncrementing()) {
            $lastId = $query->getConnection()->getPdo()->lastInsertId();
            // 无 auto_increment 列的表上 INSERT IGNORE 即使插入成功 lastInsertId 也返回 "0"，
            // 别用它去覆盖业务设置的主键。
            if ($lastId > 0) {
                $this->setAttribute($this->getKeyName(), $lastId);
            }
        }

        $this->exists = true;
        $this->wasRecentlyCreated = true;

        $this->fireModelEvent('created', false);

        return true;
    }

    /**
     * 更新数据校验
     * @param array $param
     */
    public function updateInstance(array $param)
    {
        foreach ($param AS $k => $v) {
            if (is_array($v)) {
                $v = Base::array2json($v);
            }
            $this->$k = $v;
        }
    }

    /**
     * 根据条件更新数据
     * @param $where
     * @param $updateArray
     * @return bool
     */
    public static function updateData($where, $updateArray)
    {
        if ($updateArray) {
            self::transaction(function () use ($updateArray, $where) {
                $list = static::where($where)->get();
                if ($list->isNotEmpty()) {
                    foreach ($list AS $row) {
                        $row->updateInstance($updateArray);
                        $row->save();
                    }
                }
            });
            return true;
        }
        return false;
    }

    /**
     * 数据库更新或插入
     * @param array $where              查询条件
     * @param array|\Closure $update    存在时更新的内容
     * @param array|\Closure $insert    不存在时插入的内容，如果没有则插入更新内容
     * @param bool $isInsert            是否是插入数据
     * @param bool|null $lockForUpdate  是否加锁（true:加锁，false:不加锁，null:在事务中会自动加锁）
     * @return AbstractModel|\Illuminate\Database\Eloquent\Builder|Model|object|static|null
     */
    public static function updateInsert($where, $update = [], $insert = [], &$isInsert = true, $lockForUpdate = null)
    {
        $query = static::where($where);
        if ($lockForUpdate === null) {
            $lockForUpdate = \DB::transactionLevel() > 0;
        }
        if ($lockForUpdate) {
            $query->lockForUpdate();
        }
        $row = $query->first();
        if (empty($row)) {
            $row = new static;
            if ($insert instanceof \Closure) {
                $insert = $insert();
            }
            if (empty($insert)) {
                if ($update instanceof \Closure) {
                    $update = $update();
                }
                $insert = $update;
            }
            $array = array_merge($where, $insert);
            if (isset($array[$row->primaryKey])) {
                unset($array[$row->primaryKey]);
            }
            $row->updateInstance($array);
            $isInsert = true;
        } elseif ($update) {
            if ($update instanceof \Closure) {
                $update = $update();
            }
            $row->updateInstance($update);
            $isInsert = false;
        }
        if (!$row->save()) {
            return null;
        }
        return $row;
    }

    /**
     * 用于Model的事务处理
     * @param \Closure $closure
     * @return mixed
     */
    public static function transaction(\Closure $closure)
    {
        try {
            DB::beginTransaction();
            $result = $closure();
            DB::commit();
            return $result;
        } catch (\Throwable $e) {
            //接收异常处理并回滚
            try {
                DB::rollBack();
            } catch (\Throwable $eb) {
                info($eb);
            }
            if ($e instanceof ApiException) {
                throw new ApiException( $e->getMessage() , $e->getData(), $e->getCode());
            } else {
                throw new ApiException( $e->getMessage() ?: '处理错误');
            }
        }
    }
}
