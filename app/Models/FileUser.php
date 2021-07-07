<?php

namespace App\Models;


/**
 * Class FileUser
 *
 * @package App\Models
 * @property int $id
 * @property int|null $file_id 项目ID
 * @property int|null $userid 成员ID
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|FileUser newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|FileUser newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|FileUser query()
 * @method static \Illuminate\Database\Eloquent\Builder|FileUser whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FileUser whereFileId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FileUser whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FileUser whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FileUser whereUserid($value)
 * @mixin \Eloquent
 */
class FileUser extends AbstractModel
{
}
