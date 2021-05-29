<?php

namespace App\Models;

use App\Module\Base;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * App\Model\AbstractModel
 *
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel query()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel getKeyValue()
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
        $isUpdate = false;
        if ($updateArray) {
            $result = self::transaction(function () use ($updateArray, $where) {
                $list = static::where($where)->get();
                if ($list->isNotEmpty()) {
                    foreach ($list AS $row) {
                        $row->updateInstance($updateArray);
                        $row->save();
                    }
                }
            });
            $isUpdate = Base::isSuccess($result);
        }
        return $isUpdate;
    }

    /**
     * 数据库更新或插入
     * @param $where
     * @param array $update 存在时更新的内容
     * @param array $insert 不存在时插入的内容，如果没有则插入更新内容
     * @return bool
     */
    public static function updateInsert($where, $update = [], $insert = [])
    {
        $row = static::where($where)->first();
        if (empty($row)) {
            $row = new static;
            $row->updateInstance(array_merge($where, $insert ?: $update));
        } elseif ($update) {
            $row->updateInstance($update);
        }
        return $row->save();
    }

    /**
     * 定义变量为对象（IDE高亮）
     * @param $value
     * @return static
     */
    public static function IDE($value)
    {
        return $value;
    }

    /**
     * 用于Model的事务处理
     * @param \Closure $closure
     * @return array
     */
    public static function transaction(\Closure $closure)
    {
        //开启事务
        try {
            DB::beginTransaction();
            $result = $closure();
            if (is_bool($result)) {
                if ($result === false) {
                    throw new \Exception('处理失败！');  // 错误：① 返回faske
                }
            } elseif ($result) {
                if (is_string($result)) {
                    throw new \Exception($result);              // 错误：② 返回字符串（错误描述）
                } elseif (is_array($result)
                    && Base::isError($result)) {
                    throw new \Exception($result['msg']);       // 错误：③ 返回数组，且ret=0
                }
            }
            DB::commit();
            return $result ?: Base::retSuccess('success');
        } catch (\Throwable $e) {
            info($e);
            //接收异常处理并回滚
            try {
                DB::rollBack();
            } catch (\Throwable $eb) {
                info($eb);
            }
            return Base::retError($e->getMessage() ?: '处理错误！');
        }
    }
}
