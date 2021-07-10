<?php

namespace App\Http\Controllers\Api;


use App\Models\File;
use App\Models\FileContent;
use App\Models\FileUser;
use App\Models\User;
use App\Module\Base;
use App\Module\Ihttp;
use Arr;
use Request;
use Response;

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
            $file = File::find($pid);
            if (empty($file)) {
                return Base::retError('Not exist');
            }
            $file->chackAllow($user->userid);
            //
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
                $array[] = $file->toArray();
                $pid = $file->pid;
            }
        } else {
            // 获取共享相关
            $list = File::where('userid', '!=', $user->userid)->where(function ($query) use ($user) {
                $query->where('share', 1)->orWhere(function ($q2) use ($user) {
                    $q2->where('share', 2)->whereIn('id', function ($q3) use ($user) {
                        $q3->select('file_id')->from('file_users')->where('userid', $user->userid);
                    });
                });
            })->get();
            if ($list->isNotEmpty()) {
                foreach ($list as $item) {
                    $item->pid = 0;
                    $array[] = $item->toArray();
                }
            }
        }
        return Base::retSuccess('success', $array);
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
        $builder = File::whereUserid($user->userid)->where('name', 'like', '%' . $key . '%');
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
                'userid' => $userid,
                'created_id' => $user->userid,
            ]);
            $file->save();
            //
            $data = File::find($file->id);
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
            'userid' => $userid,
            'created_id' => $user->userid,
        ]);
        $file->save();
        //
        $data = File::find($file->id);
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
     * @apiParam {Number} id            文件ID
     */
    public function content()
    {
        $id = intval(Request::input('id'));
        //
        $file = File::allowFind($id);
        //
        switch ($file->type) {
            case "word":
                return Response::download(resource_path('assets/statics/empty/empty.docx'));

            case "excel":
                return Response::download(resource_path('assets/statics/empty/empty.xlsx'));

            case "ppt":
                return Response::download(resource_path('assets/statics/empty/empty.pptx'));

            default:
                $content = FileContent::whereFid($file->id)->orderByDesc('id')->first();
                return Base::retSuccess('success', [
                    'content' => FileContent::formatContent($file->type, $content ? $content->content : [])
                ]);
        }
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
        if (in_array($file->type, ['word', 'excel', 'ppt'])) {
            return Base::retError($file->type . ' 不支持此方式保存');
        }
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
        $file->pushContentChange();
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
            $from = 'http://10.22.22.6' . $parse['path'] . '?' . $parse['query'];
            $path = 'uploads/office/' . $file->id . '/' . $user->userid . '-' . $key;
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
            }
        }
        return ['error' => 0];
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
        $userids = FileUser::whereFileId($file->id)->pluck('userid')->toArray();
        //
        return Base::retSuccess('success', [
            'id' => $file->id,
            'userids' => $userids
        ]);
    }

    /**
     * 获取共享信息
     *
     * @apiParam {Number} id            文件ID
     * @apiParam {String} action        动作
     * - share: 设置共享
     * - unshare: 取消共享
     * @apiParam {Number} [share]       共享对象
     * - 1: 共享给所有人
     * - 2: 共享给指定成员
     * @apiParam {Array} [userids]      共享成员，格式: [userid1, userid2, userid3]
     */
    public function share__update()
    {
        $user = User::auth();
        //
        $id = intval(Request::input('id'));
        $action = Request::input('action');
        $share = intval(Request::input('share'));
        $userids = Request::input('userids');
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
            return Base::retError('已经处于共享目录中');
        }
        //
        if ($action == 'unshare') {
            // 取消共享
            $file->setShare(0);
            return Base::retSuccess('取消成功', $file);
        } else {
            // 设置共享
            if (!in_array($share, [1, 2])) {
                return Base::retError('请选择共享对象');
            }
            $file->setShare($share);
            if ($share == 2) {
                $array = [];
                if (is_array($userids)) {
                    foreach ($userids as $userid) {
                        if (!intval($userid)) continue;
                        if (!User::whereUserid($userid)->exists()) continue;
                        FileUser::updateInsert([
                            'file_id' => $file->id,
                            'userid' => $userid,
                        ]);
                        $array[] = $userid;
                    }
                }
                if (empty($array)) {
                    FileUser::whereFileId($file->id)->delete();
                } else {
                    FileUser::whereFileId($file->id)->whereNotIn('userid', $array)->delete();
                }
            }
            return Base::retSuccess('设置成功', $file);
        }
    }
}
