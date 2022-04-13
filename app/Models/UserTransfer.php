<?php

namespace App\Models;

use App\Exceptions\ApiException;
use App\Module\Base;
use Carbon\Carbon;
use Guanguans\Notify\Factory;
use Guanguans\Notify\Messages\EmailMessage;

/**
 * App\Models\UserTransfer
 *
 * @property int $id
 * @property int|null $original_userid 原作者
 * @property int|null $new_userid 交接人
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|UserTransfer newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|UserTransfer newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|UserTransfer query()
 * @method static \Illuminate\Database\Eloquent\Builder|UserTransfer whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserTransfer whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserTransfer whereNewUserid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserTransfer whereOriginalUserid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserTransfer whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class UserTransfer extends AbstractModel
{

    /**
     * 开始移交
     * @return void
     */
    public function start()
    {
        // 移交项目身份
        ProjectUser::transfer($this->original_userid, $this->new_userid);
        // 移交任务身份
        ProjectTaskUser::transfer($this->original_userid, $this->new_userid);
        // 移交文件
        File::transfer($this->original_userid, $this->new_userid);
    }
}
