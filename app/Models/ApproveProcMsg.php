<?php

namespace App\Models;

/**
 * App\Models\ApproveProcMsg
 *
 * @property int $id
 * @property int|null $proc_inst_id 流程实例ID
 * @property int|null $userid 会员ID
 * @property int|null $msg_id 消息ID
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel cancelAppend()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel cancelHidden()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel change($array)
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel getKeyValue()
 * @method static \Illuminate\Database\Eloquent\Builder|ApproveProcMsg newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ApproveProcMsg newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ApproveProcMsg query()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel remove()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel saveOrIgnore()
 * @method static \Illuminate\Database\Eloquent\Builder|ApproveProcMsg whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ApproveProcMsg whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ApproveProcMsg whereMsgId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ApproveProcMsg whereProcInstId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ApproveProcMsg whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ApproveProcMsg whereUserid($value)
 * @mixin \Eloquent
 */
class ApproveProcMsg extends AbstractModel
{

}
