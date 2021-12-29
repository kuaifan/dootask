<?php

namespace App\Http\Controllers\Api;


use App\Models\AbstractModel;
use App\Models\File;
use App\Models\FileContent;
use App\Models\FileLink;
use App\Models\FileUser;
use App\Models\User;
use App\Module\Base;
use App\Module\Ihttp;
use Illuminate\Support\Facades\DB;
use Request;

/**
 * @apiDefine file
 *
 * 文件
 */
class FileController extends AbstractController
{
    /**
     * 获取文件列表
     *
     * @apiParam {Number} [pid]         父级ID
     */
    public function lists()
    {
        $user = User::auth();
        //
        $data = Request::all();
        $pid = intval($data['pid']);
        //
        if ($pid > 0) {
            File::allowFind($pid);
            $builder = File::wherePid($pid);
        } else {
            $builder = File::whereUserid($user->userid);
        }
        $array = $builder->take(500)->get()->toArray();
        //
        if ($pid > 0) {
            // 遍历获取父级
            while ($pid > 0) {
                $file = File::whereId($pid)->first();
                if (empty($file)) {
                    break;
                }
                $pid = $file->pid;
                $temp = $file->toArray();
                $temp['allow'] = $file->chackAllow($user->userid);
                $array[] = $temp;
            }
        } else {
            // 获取共享相关
            DB::statement("SET SQL_MODE=''");
            $pre = DB::connection()->getTablePrefix();
            $list = File::select(["files.*", DB::raw("MAX({$pre}file_users.permission) as permission")])
                ->join('file_users', 'files.id', '=', 'file_users.file_id')
                ->where('files.userid', '!=', $user->userid)
                ->where(function ($query) use ($user) {
                    $query->where('file_users.userid', 0);
                    $query->orWhere('file_users.userid', $user->userid);
                })
                ->groupBy('files.id')
                ->take(100)
                ->get();
            if ($list->isNotEmpty()) {
                foreach ($list as $file) {
                    $temp = $file->toArray();
                    $temp['pid'] = 0;
                    $array[] = $temp;
                }
            }
        }
        return Base::retSuccess('success', $array);
    }

    /**
     * 获取单条数据
     *
     * @apiParam {String} [code]         链接码（用于预览）
     * @apiParam {Number} [id]           文件ID（需要权限，用于管理）
     *
     * @return array
     */
    public function one()
    {
        if (Request::exists("code")) {
            $fileLink = FileLink::whereCode(Request::input('code'))->first();
            $file = $fileLink?->file;
            if (empty($file)) {
                return Base::retError('链接不存在');
            }
        } else {
            User::auth();
            $id = intval(Request::input('id'));
            $file = File::allowFind($id);
        }
        return Base::retSuccess('success', $file);
    }

    /**
     * 搜索文件列表
     *
     * @apiParam {String} [key]         关键词
     */
    public function search()
    {
        $user = User::auth();
        //
        $key = trim(Request::input('key'));
        if (empty($key)) {
            return Base::retError('请输入关键词');
        }
        //
        $builder = File::whereUserid($user->userid)->where("name", "like", "%{$key}%");
        $list = $builder->take(50)->get();
        //
        return Base::retSuccess('success', $list);
    }

    /**
     * 添加、修改文件(夹)
     *
     * @apiParam {String} name          项目名称
     * @apiParam {String} type          文件类型
     * @apiParam {Number} [id]          文件ID（赋值修改文件名称）
     * @apiParam {Number} [pid]         父级ID
     */
    public function add()
    {
        $user = User::auth();
        // 文件名称
        $name = trim(Request::input('name'));
        $type = trim(Request::input('type'));
        $id = intval(Request::input('id'));
        $pid = intval(Request::input('pid'));
        if (mb_strlen($name) < 2) {
            return Base::retError('文件名称不可以少于2个字');
        } elseif (mb_strlen($name) > 32) {
            return Base::retError('文件名称最多只能设置32个字');
        }
        //
        if ($id > 0) {
            // 修改
            $file = File::allowFind($id);
            //
            $file->name = $name;
            $file->save();
            $file->pushMsg('update', $file);
            return Base::retSuccess('修改成功', $file);
        } else {
            // 添加
            if (!in_array($type, [
                'folder',
                'document',
                'mind',
                'sheet',
                'flow',
                'word',
                'excel',
                'ppt',
            ])) {
                return Base::retError('类型错误');
            }
            $ext = '';
            if (in_array($type, [
                'word',
                'excel',
                'ppt',
            ])) {
                $ext = str_replace(['word', 'excel', 'ppt'], ['docx', 'xlsx', 'pptx'], $type);
            }
            //
            $userid = $user->userid;
            if ($pid > 0) {
                if (File::wherePid($pid)->count() >= 300) {
                    return Base::retError('每个文件夹里最多只能创建300个文件或文件夹');
                }
                $row = File::allowFind($pid, '主文件不存在');
                $userid = $row->userid;
            } else {
                if (File::whereUserid($user->userid)->wherePid(0)->count() >= 300) {
                    return Base::retError('每个文件夹里最多只能创建300个文件或文件夹');
                }
            }
            // 开始创建
            $file = File::createInstance([
                'pid' => $pid,
                'name' => $name,
                'type' => $type,
                'ext' => $ext,
                'userid' => $userid,
                'created_id' => $user->userid,
            ]);
            $file->save();
            //
            $data = File::find($file->id);
            $data->pushMsg('add', $data);
            return Base::retSuccess('添加成功', $data);
        }
    }

    /**
     * 复制文件(夹)
     *
     * @apiParam {Number} id            文件ID
     */
    public function copy()
    {
        $user = User::auth();
        //
        $id = intval(Request::input('id'));
        //
        $row = File::allowFind($id);
        //
        $userid = $user->userid;
        if ($row->pid > 0) {
            $userid = intval(File::whereId($row->pid)->value('userid'));
        }
        //
        if ($row->type == 'folder') {
            return Base::retError('不支持复制文件夹');
        }
        $num = File::whereCid($row->id)->count() + 1;
        $name = $row->name . " ({$num})";
        // 开始复制
        $file = File::createInstance([
            'cid' => $row->id,
            'pid' => $row->pid,
            'name' => $name,
            'type' => $row->type,
            'ext' => $row->ext,
            'userid' => $userid,
            'created_id' => $user->userid,
        ]);
        $file->save();
        //
        $data = File::find($file->id);
        $data->pushMsg('add', $data);
        return Base::retSuccess('复制成功', $data);
    }

    /**
     * 移动文件(夹)
     *
     * @apiParam {Number} id            文件ID
     * @apiParam {Number} pid           移动到的文件夹ID
     */
    public function move()
    {
        $user = User::auth();
        //
        $id = intval(Request::input('id'));
        $pid = intval(Request::input('pid'));
        //
        $file = File::whereId($id)->first();
        if (empty($file)) {
            return Base::retError('文件不存在或已被删除');
        }
        if ($file->userid != $user->userid) {
            return Base::retError('仅限所有者操作');
        }
        //
        if ($pid > 0) {
            if (!File::whereUserid($user->userid)->whereId($pid)->exists()) {
                return Base::retError('参数错误');
            }
            $arr = [];
            $tid = $pid;
            while ($tid > 0) {
                $arr[] = $tid;
                $tid = intval(File::whereId($tid)->value('pid'));
            }
            if (in_array($id, $arr)) {
                return Base::retError('位置错误');
            }
        }
        //
        $file->pid = $pid;
        $file->save();
        $file->pushMsg('update', $file);
        return Base::retSuccess('操作成功', $file);
    }

    /**
     * 删除文件(夹)
     *
     * @apiParam {Number} id            文件ID
     */
    public function remove()
    {
        $id = intval(Request::input('id'));
        //
        $file = File::allowFind($id);
        $file->deleteFile();
        return Base::retSuccess('删除成功', $file);
    }

    /**
     * 获取文件内容
     *
     * @apiParam {String} [code]         链接码（用于预览）
     * @apiParam {Number} [id]           文件ID（需要权限，用于管理）
     */
    public function content()
    {
        if (Request::exists("code")) {
            $fileLink = FileLink::whereCode(Request::input('code'))->first();
            $file = $fileLink?->file;
            if (empty($file)) {
                return Base::retError('链接不存在');
            }
        } else {
            $id = intval(Request::input('id'));
            $file = File::allowFind($id);
        }
        //
        $content = FileContent::whereFid($file->id)->orderByDesc('id')->first();
        return FileContent::formatContent($file->type, $content ? $content->content : []);
    }

    /**
     * 保存文件内容
     *
     * @apiParam {Number} id            文件ID
     * @apiParam {Object} [D]               Request Payload 提交
     * - content: 内容
     */
    public function content__save()
    {
        $user = User::auth();
        //
        $id = Base::getPostInt('id');
        $content = Base::getPostValue('content');
        //
        $file = File::allowFind($id);
        //
        $text = '';
        if ($file->type == 'document') {
            $data = Base::json2array($content);
            $isRep = false;
            preg_match_all("/<img\s*src=\"data:image\/(png|jpg|jpeg);base64,(.*?)\"/s", $data['content'], $matchs);
            foreach ($matchs[2] as $key => $text) {
                $p = "uploads/files/document/" . $id . "/";
                Base::makeDir(public_path($p));
                $p.= md5($text) . "." . $matchs[1][$key];
                $r = file_put_contents(public_path($p), base64_decode($text));
                if ($r) {
                    $data['content'] = str_replace($matchs[0][$key], '<img src="' . Base::fillUrl($p) . '"', $data['content']);
                    $isRep = true;
                }
            }
            $text = strip_tags($data['content']);
            if ($isRep == true) {
                $content = Base::array2json($data);
            }
        }
        //
        $content = FileContent::createInstance([
            'fid' => $file->id,
            'content' => $content,
            'text' => $text,
            'size' => strlen($content),
            'userid' => $user->userid,
        ]);
        $content->save();
        //
        $file->size = $content->size;
        $file->save();
        $file->pushMsg('content');
        //
        return Base::retSuccess('保存成功', $content);
    }

    /**
     * 保存文件内容（office）
     *
     * @apiParam {Number} id            文件ID
     */
    public function content__office()
    {
        $user = User::auth();
        //
        $id = intval(Request::input('id'));
        $status = intval(Request::input('status'));
        $key = Request::input('key');
        $url = Request::input('url');
        //
        $file = File::allowFind($id);
        //
        if ($status === 2) {
            $parse = parse_url($url);
            $from = 'http://' . env('APP_IPPR') . '.3' . $parse['path'] . '?' . $parse['query'];
            $path = 'uploads/office/' . date("Ym") . '/' . $file->id . '/' . $user->userid . '-' . $key;
            $save = public_path($path);
            Base::makeDir(dirname($save));
            $res = Ihttp::download($from, $save);
            if (Base::isSuccess($res)) {
                $content = FileContent::createInstance([
                    'fid' => $file->id,
                    'content' => [
                        'from' => $from,
                        'url' => $path
                    ],
                    'text' => '',
                    'size' => filesize($save),
                    'userid' => $user->userid,
                ]);
                $content->save();
                //
                $file->size = $content->size;
                $file->save();
                $file->pushMsg('update', $file);
            }
        }
        return ['error' => 0];
    }

    /**
     * 保存文件内容（上传文件）
     *
     * @apiParam {Number} [pid]         父级ID
     * @apiParam {String} [files]           文件名
     */
    public function content__upload()
    {
        $user = User::auth();
        //
        $pid = intval(Request::input('pid'));
        //
        $userid = $user->userid;
        if ($pid > 0) {
            if (File::wherePid($pid)->count() >= 300) {
                return Base::retError('每个文件夹里最多只能创建300个文件或文件夹');
            }
            $row = File::allowFind($pid, '主文件不存在');
            $userid = $row->userid;
        } else {
            if (File::whereUserid($user->userid)->wherePid(0)->count() >= 300) {
                return Base::retError('每个文件夹里最多只能创建300个文件或文件夹');
            }
        }
        //
        $path = 'uploads/office/' . date("Ym") . '/u' . $user->userid . '/';
        $data = Base::upload([
            "file" => Request::file('files'),
            "type" => 'more',
            "autoThumb" => false,
            "path" => $path,
        ]);
        if (Base::isError($data)) {
            return $data;
        }
        $data = $data['data'];
        //
        $type = match ($data['ext']) {
            'doc', 'docx' => "word",
            'xls', 'xlsx' => "excel",
            'ppt', 'pptx' => "ppt",
            'wps' => "wps",
            'jpg', 'jpeg', 'png', 'gif', 'bmp', 'ico', 'raw' => "picture",
            'rar', 'zip', 'jar', '7-zip', 'tar', 'gzip', '7z' => "archive",
            'tif', 'tiff' => "tif",
            'dwg', 'dxf' => "cad",
            'ofd' => "ofd",
            'pdf' => "pdf",
            'txt' => "txt",
            'html', 'htm', 'asp', 'jsp', 'xml', 'json', 'properties', 'md', 'gitignore', 'log', 'java', 'py', 'c', 'cpp', 'sql', 'sh', 'bat', 'm', 'bas', 'prg', 'cmd',
            'php', 'go', 'python', 'js', 'ftl', 'css', 'lua', 'rb', 'yaml', 'yml', 'h', 'cs', 'aspx' => "code",
            'mp3', 'wav', 'mp4', 'flv',
            'avi', 'mov', 'wmv', 'mkv', '3gp', 'rm' => "media",
            default => "",
        };
        $file = File::createInstance([
            'pid' => $pid,
            'name' => Base::rightDelete($data['name'], '.' . $data['ext']),
            'type' => $type,
            'ext' => $data['ext'],
            'userid' => $userid,
            'created_id' => $user->userid,
        ]);
        // 开始创建
        return AbstractModel::transaction(function () use ($type, $user, $data, $file) {
            $file->save();
            //
            $content = FileContent::createInstance([
                'fid' => $file->id,
                'content' => [
                    'from' => '',
                    'type' => $type,
                    'ext' => $data['ext'],
                    'url' => $data['path']
                ],
                'text' => '',
                'size' => $data['size'] * 1024,
                'userid' => $user->userid,
            ]);
            $content->save();
            //
            $file->size = $content->size;
            $file->save();
            //
            $data = File::find($file->id);
            $data->pushMsg('add', $data);
            return Base::retSuccess($data['name'] . ' 上传成功', $data);
        });
    }

    /**
     * 获取共享信息
     *
     * @apiParam {Number} id            文件ID
     */
    public function share()
    {
        $user = User::auth();
        //
        $id = intval(Request::input('id'));
        //
        $file = File::whereId($id)->first();
        if (empty($file)) {
            return Base::retError('文件不存在或已被删除');
        }
        if ($file->userid != $user->userid) {
            return Base::retError('仅限所有者操作');
        }
        //
        $list = FileUser::whereFileId($file->id)->get();
        //
        return Base::retSuccess('success', [
            'id' => $file->id,
            'list' => $list
        ]);
    }

    /**
     * 设置共享
     *
     * @apiParam {Number} id            文件ID
     * @apiParam {Array} [userids]      共享成员，格式: [userid1, userid2, userid3]
     * @apiParam {Number} [permission]  共享方式
     * - 0：只读
     * - 1：读写
     * - -1: 删除
     */
    public function share__update()
    {
        $user = User::auth();
        //
        $id = intval(Request::input('id'));
        $userids = Request::input('userids');
        $permission = intval(Request::input('permission'));
        //
        if (!in_array($permission, [-1, 0, 1])) {
            return Base::retError('参数错误');
        }
        //
        $file = File::whereId($id)->first();
        if (empty($file)) {
            return Base::retError('文件不存在或已被删除');
        }
        if ($file->userid != $user->userid) {
            return Base::retError('仅限所有者操作');
        }
        //
        if ($file->isNnShare()) {
            return Base::retError('已经处于共享文件夹中');
        }
        //
        if (!is_array($userids) || empty($userids)) {
            return Base::retError('请选择共享对象');
        }
        //
        $array = [];
        if ($permission === -1) {
            // 取消共享
            $action = "delete";
            foreach ($userids as $userid) {
                if (FileUser::where([
                    'file_id' => $file->id,
                    'userid' => $userid,
                ])->delete()) {
                    $array[] = $userid;
                }
            }
        } else {
            // 设置共享
            $action = "update";
            if (FileUser::whereFileId($file->id)->count() + count($userids) > 100) {
                return Base::retError('共享人数上限100个成员');
            }
            foreach ($userids as $userid) {
                if (FileUser::updateInsert([
                    'file_id' => $file->id,
                    'userid' => $userid,
                ], [
                    'permission' => $permission,
                ])) {
                    $array[] = $userid;
                }
            }
        }
        //
        $file->setShare();
        $file->pushMsg($action, $action == "delete" ? null : $file, $array);
        return Base::retSuccess($action == "delete" ? "删除成功" : "设置成功", $file);
    }

    /**
     * 退出共享
     *
     * @apiParam {Number} id            文件ID
     */
    public function share__out()
    {
        $user = User::auth();
        //
        $id = intval(Request::input('id'));
        //
        $file = File::allowFind($id);
        //
        if ($file->userid == $user->userid) {
            return Base::retError('不能退出自己共享的文件');
        }
        if (FileUser::where([
            'file_id' => $file->id,
            'userid' => 0,
        ])->exists()) {
            return Base::retError('无法退出共享所有人的文件或文件夹');
        }
        FileUser::where([
            'file_id' => $file->id,
            'userid' => $user->userid,
        ])->delete();
        //
        $file->setShare();
        return Base::retSuccess("退出成功");
    }

    /**
     * 获取链接
     *
     * @apiParam {Number} id                文件ID
     * @apiParam {String} refresh           刷新链接
     * - no: 只获取（默认）
     * - yes: 刷新链接，之前的将失效
     */
    public function link()
    {
        $user = User::auth();
        //
        $id = intval(Request::input('id'));
        $refresh = Request::input('refresh', 'no');
        //
        $file = File::allowFind($id);
        //
        if ($file->userid != $user->userid) {
            return Base::retError('仅限所有者操作');
        }
        if ($file->type == 'folder') {
            return Base::retError('文件夹暂不支持此功能');
        }
        //
        $fileLink = FileLink::whereFileId($file->id)->first();
        if (empty($fileLink)) {
            $fileLink = FileLink::createInstance([
                'file_id' => $file->id,
                'code' => Base::generatePassword(64),
            ]);
            $fileLink->save();
        } else {
            if ($refresh == 'yes') {
                $fileLink->code = Base::generatePassword(64);
                $fileLink->save();
            }
        }
        return Base::retSuccess('success', [
            'id' => $file->id,
            'url' => Base::fillUrl('single/file/' . $fileLink->code),
            'num' => $fileLink->num
        ]);
    }
}
