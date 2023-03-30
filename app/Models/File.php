<?php

namespace App\Models;

use App\Exceptions\ApiException;
use App\Module\Base;
use App\Tasks\PushTask;
use Hhxsv5\LaravelS\Swoole\Task\Task;
use Illuminate\Database\Eloquent\SoftDeletes;
use Request;

/**
 * App\Models\File
 *
 * @property int $id
 * @property int|null $pid 上级ID
 * @property string|null $pids 上级ID递归
 * @property int|null $cid 复制ID
 * @property string|null $name 名称
 * @property string|null $type 类型
 * @property string|null $ext 后缀名
 * @property int|null $size 大小(B)
 * @property int|null $userid 拥有者ID
 * @property int|null $share 是否共享
 * @property int|null $pshare 所属分享ID
 * @property int|null $created_id 创建者
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @method static \Illuminate\Database\Eloquent\Builder|File newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|File newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|File onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|File query()
 * @method static \Illuminate\Database\Eloquent\Builder|File whereCid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|File whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|File whereCreatedId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|File whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|File whereExt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|File whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|File whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|File wherePid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|File wherePids($value)
 * @method static \Illuminate\Database\Eloquent\Builder|File wherePshare($value)
 * @method static \Illuminate\Database\Eloquent\Builder|File whereShare($value)
 * @method static \Illuminate\Database\Eloquent\Builder|File whereSize($value)
 * @method static \Illuminate\Database\Eloquent\Builder|File whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|File whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|File whereUserid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|File withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|File withoutTrashed()
 * @mixin \Eloquent
 */
class File extends AbstractModel
{
    use SoftDeletes;

    /**
     * 文件文件
     */
    const codeExt = [
        'txt',
        'htaccess', 'htgroups', 'htpasswd', 'conf', 'bat', 'cmd', 'cpp', 'c', 'cc', 'cxx', 'h', 'hh', 'hpp', 'ino', 'cs', 'css',
        'dockerfile', 'go', 'golang', 'html', 'htm', 'xhtml', 'vue', 'we', 'wpy', 'java', 'js', 'jsm', 'jsx', 'json', 'jsp', 'less', 'lua', 'makefile', 'gnumakefile',
        'ocamlmakefile', 'make', 'mysql', 'nginx', 'ini', 'cfg', 'prefs', 'm', 'mm', 'pl', 'pm', 'p6', 'pl6', 'pm6', 'pgsql', 'php',
        'inc', 'phtml', 'shtml', 'php3', 'php4', 'php5', 'phps', 'phpt', 'aw', 'ctp', 'module', 'ps1', 'py', 'r', 'rb', 'ru', 'gemspec', 'rake', 'guardfile', 'rakefile',
        'gemfile', 'rs', 'sass', 'scss', 'sh', 'bash', 'bashrc', 'sql', 'sqlserver', 'swift', 'ts', 'typescript', 'str', 'vbs', 'vb', 'v', 'vh', 'sv', 'svh', 'xml',
        'rdf', 'rss', 'wsdl', 'xslt', 'atom', 'mathml', 'mml', 'xul', 'xbl', 'xaml', 'yaml', 'yml',
        'asp', 'properties', 'gitignore', 'log', 'bas', 'prg', 'python', 'ftl', 'aspx', 'plist'
    ];

    /**
     * office文件
     */
    const officeExt = [
        'doc', 'docx',
        'xls', 'xlsx',
        'ppt', 'pptx',
    ];

    /**
     * 图片文件
     */
    const imageExt = [
        'jpg', 'jpeg', 'png', 'gif', 'bmp'
    ];

    /**
     * 本地媒体文件
     */
    const localExt = [
        'jpg', 'jpeg', 'png', 'gif', 'bmp', 'ico', 'raw',
        'tif', 'tiff',
        'mp3', 'wav', 'mp4', 'flv',
        'avi', 'mov', 'wmv', 'mkv', '3gp', 'rm',
    ];

    /**
     * 是否有访问权限
     * @param array $userids
     * @return int -1:没有权限，0:访问权限，1:读写权限，1000:所有者或创建者
     */
    public function getPermission(array $userids)
    {
        if (in_array($this->userid, $userids) || in_array($this->created_id, $userids)) {
            // ① 自己的文件夹 或 自己创建的文件夹
            return 1000;
        }
        $row = $this->getShareInfo();
        if ($row) {
            $fileUser = FileUser::whereFileId($row->id)->whereIn('userid', $userids)->orderByDesc('permission')->first();
            if ($fileUser) {
                // ② 在指定共享成员内
                return $fileUser->permission;
            }
        }
        return -1;
    }

    /**
     * 获取共享数据（含自身）
     * @return $this|null
     */
    public function getShareInfo()
    {
        if ($this->share) {
            return $this;
        }
        $pid = $this->pid;
        while ($pid > 0) {
            $row = self::whereId($pid)->first();
            if (empty($row)) {
                break;
            }
            if ($row->share) {
                return $row;
            }
            $pid = $row->pid;
        }
        return null;
    }

    /**
     * 是否处于共享文件夹内（不含自身）
     * @return File|false
     */
    public function isNnShare()
    {
        $pid = $this->pid;
        while ($pid > 0) {
            $row = self::whereId($pid)->first();
            if (empty($row)) {
                break;
            }
            if ($row->share) {
                return $row;
            }
            $pid = $row->pid;
        }
        return false;
    }

    /**
     * 目录内是否存在共享文件或文件夹
     * @return bool
     */
    public function isSubShare()
    {
        return $this->type == 'folder' && File::where("pids", "like", "%,{$this->id},%")->whereShare(1)->exists();
    }

    /**
     * 设置/关闭 共享（同时遍历取消里面的共享）
     * @param $share
     * @return bool
     */
    public function updataShare($share = null)
    {
        if ($share === null) {
            $share = FileUser::whereFileId($this->id)->count() == 0 ? 0 : 1;
        }
        if ($this->share != $share) {
            AbstractModel::transaction(function () use ($share) {
                $this->share = $share;
                $this->save();
                File::where("pids", "like", "%,{$this->id},%")->update(['pshare' => $share ? $this->id : 0]);
                if ($share === 0) {
                    FileUser::deleteFileAll($this->id, $this->userid);
                }
                $list = self::wherePid($this->id)->get();
                if ($list->isNotEmpty()) {
                    foreach ($list as $item) {
                        $item->updataShare(0);
                    }
                }
            });
        }
        return true;
    }

    /**
     * 处理重名
     * @return void
     */
    public function handleDuplicateName()
    {
        $builder = self::wherePid($this->pid)->whereUserid($this->userid)->whereExt($this->ext);
        $exist = $builder->clone()->whereName($this->name)->exists();
        if (!$exist) {
            return;    // 未重名，不需要处理
        }
        // 发现重名，自动重命名
        $nextNum = 2;
        if (preg_match("/(.*?)(\s+\(\d+\))*$/", $this->name)) {
            $preName = preg_replace("/(.*?)(\s+\(\d+\))*$/", "$1", $this->name);
            $nextNum = $builder->clone()->where("name", "LIKE", "{$preName}%")->count() + 1;
        }
        $newName = "{$this->name} ({$nextNum})";
        if ($builder->clone()->whereName($newName)->exists()) {
            $nextNum = rand(100, 9999);
            $newName = "{$this->name} ({$nextNum})";
        }
        $this->name = $newName;
    }

    /**
     * 保存前更新pids/pshare
     * @return bool
     */
    public function saveBeforePP()
    {
        $pid = $this->pid;
        $pshare = $this->share ? $this->id : 0;
        $array = [];
        while ($pid > 0) {
            $array[] = $pid;
            $file = self::select(['id', 'pid', 'share'])->find($pid);
            if ($file) {
                $pid = $file->pid;
                if ($file->share) {
                    $pshare = $file->id;
                }
            } else {
                $pid = 0;
            }
        }
        $opids = $this->pids;
        if ($array) {
            $array = array_values(array_reverse($array));
            $this->pids = ',' . implode(',', $array) . ',';
        } else {
            $this->pids = '';
        }
        $this->pshare = $pshare;
        if (!$this->save()) {
            return false;
        }
        // 更新子文件（夹）
        if ($opids != $this->pids) {
            self::wherePid($this->id)->chunkById(100, function ($lists) {
                /** @var self $item */
                foreach ($lists as $item) {
                    $item->saveBeforePP();
                }
            });
        }
        return true;
    }

    /**
     * 遍历删除文件(夹)
     * @return bool
     */
    public function deleteFile()
    {
        AbstractModel::transaction(function () {
            $this->delete();
            $this->pushMsg('delete');
            FileUser::deleteFileAll($this->id);
            FileContent::whereFid($this->id)->delete();
            $list = self::wherePid($this->id)->get();
            if ($list->isNotEmpty()) {
                foreach ($list as $file) {
                    $file->deleteFile();
                }
            }
        });
        return true;
    }

    /**
     * 强制删除文件
     * @return true
     */
    public function forceDeleteFile()
    {
        AbstractModel::transaction(function () {
            $this->forceDelete();
            FileContent::withTrashed()
                ->whereFid($this->id)
                ->orderBy('id')
                ->chunk(500, function ($contents) {
                    /** @var FileContent $content */
                    foreach ($contents as $content) {
                        $content->forceDeleteContent();
                    }
                });
        });
        return true;
    }

    /**
     * 获取文件分享链接
     * @param $userid
     * @param $refresh
     * @return array
     */
    public function getShareLink($userid, $refresh = false)
    {
        if ($this->type == 'folder') {
            throw new ApiException('文件夹不支持分享');
        }
        return FileLink::generateLink($this->id, $userid, $refresh);
    }

    /**
     * 获取文件名称加后缀
     * @return string|null
     */
    public function getNameAndExt()
    {
        return $this->ext ? "{$this->name}.{$this->ext}" : $this->name;
    }

    /**
     * 推送消息
     * @param $action
     * @param File|null $data   发送内容，默认为[id]
     * @param array $userid     指定会员，默认为可查看文件的人
     */
    public function pushMsg($action, $data = null, $userid = null)
    {
        if ($data === null) {
            $data = [
                'id' => $this->id
            ];
        }
        //
        $userid = $this->pushUserid($action, $userid);
        if (empty($userid)) {
            return;
        }
        //
        $msg = [
            'type' => 'file',
            'action' => $action,
            'data' => $data,
        ];
        if ($action == 'content') {
            $msg['nickname'] = User::nickname();
            $msg['time'] = time();
        }
        $params = [
            'ignoreFd' => Request::header('fd'),
            'userid' => $userid,
            'msg' => $msg
        ];
        $task = new PushTask($params, false);
        Task::deliver($task);
    }

    /**
     * 获取推送会员
     * @param $action
     * @param $userid
     * @return array|int[]|mixed|null[]
     */
    public function pushUserid($action, $userid = null) {
        $wherePath = "/manage/file";
        if ($userid === null) {
            $array = [$this->userid];
            if ($action == 'add' && $this->pid == 0) {
                return $array;
            }
            if ($action == 'content') {
                $wherePath = "/single/file/{$this->id}";
            } elseif ($this->pid > 0) {
                $wherePath = "/manage/file/{$this->pid}";
            } else {
                $tmpArray = FileUser::whereFileId($this->id)->pluck('userid')->toArray();
                if (empty($tmpArray)) {
                    return $array;
                }
                if (!in_array(0, $tmpArray)) {
                    return $tmpArray;
                }
            }
            $tmpArray = WebSocket::wherePath($wherePath)->pluck('userid')->toArray();
            if (empty($tmpArray)) {
                return $array;
            }
            $array = array_values(array_filter(array_unique(array_merge($array, $tmpArray))));
        } else {
            $array = is_array($userid) ? $userid : [$userid];
            if (in_array(0, $array)) {
                return WebSocket::wherePath($wherePath)->pluck('userid')->toArray();
            }
        }
        return $array;
    }

    /**
     * code获取文件ID、名称
     * @param $code
     * @return File
     */
    public static function code2IdName($code) {
        $arr = explode(",", base64_decode($code));
        if (empty($arr)) {
            return null;
        }
        $fileId = intval($arr[0]);
        if (empty($fileId)) {
            return null;
        }
        return File::select(['id', 'name'])->find($fileId);
    }


    /**
     * 处理返回图片地址
     * @param array $item
     * @return array
     */
    public static function handleImageUrl($item)
    {
        if (in_array($item['ext'], self::imageExt) ) {
            $content = Base::json2array(FileContent::whereFid($item['id'])->orderByDesc('id')->value('content'));
            if ($content) {
                $item['image_url'] = Base::fillUrl($content['url']);
                $item['image_width'] = intval($content['width']);
                $item['image_height'] = intval($content['height']);
            }
        }
        return $item;
    }

    /**
     * 获取文件并检测权限
     * @param int $id
     * @param User|array|int $user      要求权限的用户，如：[0, 1]
     * @param int $limit                要求权限: 0-访问权限、1-读写权限、1000-所有者或创建者
     * @param int $permission
     * @return File
     */
    public static function permissionFind(int $id, $user, int $limit = 0, int &$permission = -1)
    {
        $file = File::find($id);
        if (empty($file)) {
            throw new ApiException('文件不存在或已被删除');
        }
        //
        if ($user instanceof User) {
            $userids = $user->isTemp() ? [$user->userid] : [0, $user->userid];
        } else {
            $userids = is_array($user) ? $user : [$user];
        }
        $permission = $file->getPermission($userids);
        if ($permission < $limit) {
            $msg = match ($limit) {
                1000 => '仅限所有者或创建者操作',
                1 => '没有修改写入权限',
                default => '没有查看访问权限',
            };
            throw new ApiException($msg);
        }
        return $file;
    }

    /**
     * 格式化内容数据
     * @param array $data [path, size, ext, name]
     * @return array
     */
    public static function formatFileData(array $data)
    {
        $fileName = $data['name'];
        $filePath = $data['path'];
        $fileSize = $data['size'];
        $fileExt = $data['ext'];
        $publicPath = public_path($filePath);
        //
        switch ($fileExt) {
            case 'md':
            case 'text':
                // 文本
                $data['content'] = [
                    'type' => $fileExt,
                    'content' => file_get_contents($publicPath) ?: 'Content deleted',
                ];
                $data['file_mode'] = $fileExt;
                break;

            case 'drawio':
                // 图表
                $data['content'] = [
                    'xml' => file_get_contents($publicPath)
                ];
                $data['file_mode'] = $fileExt;
                break;

            case 'mind':
                // 思维导图
                $data['content'] = Base::json2array(file_get_contents($publicPath));
                $data['file_mode'] = $fileExt;
                break;

            default:
                if (in_array($fileExt, self::codeExt) && $fileSize < 2 * 1024 * 1024)
                {
                    // 文本预览，限制2M内的文件
                    $data['content'] = [
                        'content' => file_get_contents($publicPath) ?: 'Content deleted',
                    ];
                    $data['file_mode'] = 'code';
                }
                elseif (in_array($fileExt, File::officeExt))
                {
                    // office预览
                    $data['content'] = json_decode('{}');
                    $data['file_mode'] = 'office';
                }
                else
                {
                    // 其他预览
                    $name = Base::rightDelete($fileName, ".{$fileExt}") . ".{$fileExt}";
                    $data['content'] = [
                        'preview' => true,
                        'name' => $name,
                        'key' => urlencode(Base::urlAddparameter($filePath, [
                            'name' => $name,
                            'ext' => $fileExt
                        ])),
                    ];
                    $data['file_mode'] = 'preview';
                }
                break;
        }
        return $data;
    }

    /**
     * 移交文件
     * @param $originalUserid
     * @param $newUserid
     * @return void
     */
    public static function transfer($originalUserid, $newUserid)
    {
        if (!self::whereUserid($originalUserid)->exists()) {
            return;
        }

        // 创建一个文件夹存放移交的文件
        $name = User::userid2nickname($originalUserid) ?: ('ID:' . $originalUserid);
        $file = File::createInstance([
            'pid' => 0,
            'name' => "【{$name}】移交的文件",
            'type' => "folder",
            'ext' => "",
            'userid' => $newUserid,
            'created_id' => 0,
        ]);
        $file->handleDuplicateName();
        $file->saveBeforePP();

        // 移交文件
        self::whereUserid($originalUserid)->chunkById(100, function($list) use ($file, $newUserid) {
            /** @var self $item */
            foreach ($list as $item) {
                if ($item->pid === 0) {
                    $item->pid = $file->id;
                }
                $item->userid = $newUserid;
                $item->saveBeforePP();
            }
        });

        // 移交文件权限
        FileUser::whereUserid($originalUserid)->chunkById(100, function ($list) use ($newUserid) {
            /** @var FileUser $item */
            foreach ($list as $item) {
                $row = FileUser::whereFileId($item->file_id)->whereUserid($newUserid)->first();
                if ($row) {
                    // 已存在则删除原数据，判断改变已存在的数据
                    $row->permission = max($row->permission, $item->permission);
                    $row->save();
                    $item->delete();
                } else {
                    // 不存在则改变原数据
                    $item->userid = $newUserid;
                    $item->save();
                }
            }
        });
    }
}
