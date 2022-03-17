<?php

namespace App\Models;

use App\Exceptions\ApiException;
use App\Module\Base;
use DateTimeInterface;
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
 * @method static \Illuminate\Database\Query\Builder|static select($columns = [])
 * @method static \Illuminate\Database\Query\Builder|static whereNotIn($column, $values, $boolean = 'and')
 * @mixin \Eloquent
 */
class AbstractModel extends Model
{
    use HasFactory;

    const ID = 'id';

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $appendattrs = [];

    /**
     * 保存数据忽略错误
     * @return bool
     */
    protected function scopeSaveOrIgnore()
    {
        try {
            return $this->save();
        } catch (\Exception $e) {
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
     * @param $where
     * @param array $update 存在时更新的内容
     * @param array $insert 不存在时插入的内容，如果没有则插入更新内容
     * @param bool $isInsert 是否是插入数据
     * @return AbstractModel|\Illuminate\Database\Eloquent\Builder|Model|object|static|null
     */
    public static function updateInsert($where, $update = [], $insert = [], &$isInsert = true)
    {
        $row = static::where($where)->first();
        if (empty($row)) {
            $row = new static;
            $array = array_merge($where, $insert ?: $update);
            if (isset($array[$row->primaryKey])) {
                unset($array[$row->primaryKey]);
            }
            $row->updateInstance($array);
            $isInsert = true;
        } elseif ($update) {
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
                throw new ApiException($e->getMessage(), $e->getData(), $e->getCode());
            } else {
                info($e);
                throw new ApiException($e->getMessage() ?: '处理错误');
            }
        }
    }
}
