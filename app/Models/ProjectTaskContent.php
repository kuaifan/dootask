<?php

namespace App\Models;

use App\Module\Base;
use App\Exceptions\ApiException;

/**
 * App\Models\ProjectTaskContent
 *
 * @property int $id
 * @property int|null $project_id 项目ID
 * @property int|null $task_id 任务ID
 * @property int|null $userid 用户ID
 * @property string|null $desc 内容描述
 * @property string|null $content 内容
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel cancelAppend()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel cancelHidden()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel change($array)
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel getKeyValue()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTaskContent newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTaskContent newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTaskContent query()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel remove()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel saveOrIgnore()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTaskContent whereContent($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTaskContent whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTaskContent whereDesc($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTaskContent whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTaskContent whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTaskContent whereTaskId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTaskContent whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTaskContent whereUserid($value)
 * @mixin \Eloquent
 */
class ProjectTaskContent extends AbstractModel
{
    protected $hidden = [
        'updated_at',
    ];

    /**
     * 获取内容详情
     * @return array
     */
    public function getContentInfo()
    {
        $content = Base::json2array($this->content);
        if (isset($content['url'])) {
            $filePath = public_path($content['url']);
            $array = $this->toArray();
            $array['content'] = file_get_contents($filePath) ?: '';
            if ($array['content']) {
                $replace = Base::fillUrl('uploads/task');
                $array['content'] = str_replace('{{RemoteURL}}uploads/task', $replace, $array['content']);
            }
            return $array;
        }
        return $this->toArray();
    }

    /**
     * 保存任务详情至文件并返回文件路径
     * @param $task_id
     * @param $content
     * @return string
     */
    public static function saveContent($task_id, $content)
    {
        @ini_set("pcre.backtrack_limit", 999999999);
        //
        $oldContent = $content;
        $path = 'uploads/task/content/' . date("Ym") . '/' . $task_id . '/';
        //
        preg_match_all("/<img\s+src=\"data:image\/(png|jpg|jpeg|webp);base64,(.*?)\"/s", $content, $matchs);
        foreach ($matchs[2] as $key => $text) {
            $tmpPath = $path . 'attached/';
            Base::makeDir(public_path($tmpPath));
            $tmpPath .= md5($text) . "." . $matchs[1][$key];
            if (Base::saveContentImage(public_path($tmpPath), base64_decode($text))) {
                $paramet = getimagesize(public_path($tmpPath));
                $content = str_replace($matchs[0][$key], '<img src="{{RemoteURL}}' . $tmpPath . '" original-width="' . $paramet[0] . '" original-height="' . $paramet[1] . '"', $content);
            }
        }
        $pattern = '/<img(.*?)src=("|\')https*:\/\/(.*?)\/(uploads\/task\/content\/(.*?))\2/is';
        $content = preg_replace($pattern, '<img$1src=$2{{RemoteURL}}$4$2', $content);
        //
        $filePath = $path . md5($content);
        $publicPath = public_path($filePath);
        Base::makeDir(dirname($publicPath));
        $result = file_put_contents($publicPath, $content);
        if(!$result && $oldContent){
            throw new ApiException("保存任务详情至文件失败,请重试");
        }
        //
        return $filePath;
    }
}
