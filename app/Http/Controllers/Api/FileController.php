<?php

namespace App\Http\Controllers\Api;

use App\Models\WebSocketDialogMsg;
use App\Models\WebSocketDialog;
use App\Exceptions\ApiException;
use App\Models\AbstractModel;
use App\Models\File;
use App\Models\FileContent;
use App\Models\FileLink;
use App\Models\FileUser;
use App\Models\User;
use App\Module\Base;
use App\Module\Ihttp;
use Response;
use Session;
use Swoole\Coroutine;
use Carbon\Carbon;
use Redirect;
use Request;
use ZipArchive;

/**
 * @apiDefine file
 *
 * 文件
 */
class FileController extends AbstractController
{
    /**
     * @api {get} api/file/lists          01. 获取文件列表
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup file
     * @apiName lists
     *
     * @apiParam {Number} [pid]         父级ID
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function lists()
    {
        $user = User::auth();
        //
        $data = Request::all();
        $pid = intval($data['pid']);
        //
        return Base::retSuccess('success', (new File)->getFileList($user, $pid));
    }

    /**
     * @api {get} api/file/one          02. 获取单条数据
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup file
     * @apiName one
     *
     * @apiParam {Number|String} id
     * - Number 文件ID（需要登录）
     * - String 链接码（不需要登录，用于预览）
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function one()
    {
        $id = Request::input('id');
        //
        $permission = 0;
        if (Base::isNumber($id)) {
            $user = User::auth();
            $file = File::permissionFind(intval($id), $user, 0, $permission);
        } elseif ($id) {
            $fileLink = FileLink::whereCode($id)->first();
            $file = $fileLink?->file;
            if (empty($file)) {
                $msg = '文件链接不存在';
                $data = File::code2IdName($id);
                if ($data) {
                    $msg = "【{$data->name}】 {$msg}";
                }
                return Base::retError($msg, $data);
            }
        } else {
            return Base::retError('参数错误');
        }
        //
        $array = $file->toArray();
        $array['permission'] = $permission;
        return Base::retSuccess('success', $array);
    }

    /**
     * @api {get} api/file/search          03. 搜索文件列表
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup file
     * @apiName search
     *
     * @apiParam {String} [link]        通过分享地址搜索（如：https://t.hitosea.com/single/file/ODcwOCwzOSxpa0JBS2lmVQ==）
     * @apiParam {String} [key]         关键词
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function search()
    {
        $user = User::auth();
        //
        $link = trim(Request::input('link'));
        $key = trim(Request::input('key'));
        $id = 0;
        $take = 50;
        if (preg_match("/\/single\/file\/(.*?)$/i", $link, $match)) {
            $id = intval(FileLink::whereCode($match[1])->value('file_id'));
            $take = 1;
            if (empty($id)) {
                return Base::retSuccess('success', []);
            }
        }
        // 搜索自己的
        $builder = File::whereUserid($user->userid);
        if ($id) {
            $builder->where("id", $id);
        }
        if ($key) {
            $builder->where("name", "like", "%{$key}%");
        }
        $array = $builder->take($take)->get()->toArray();
        // 搜索共享的
        $take = $take - count($array);
        if ($take > 0 && ($id || $key)) {
            $builder = File::whereIn('pshare', function ($queryA) use ($user) {
                $queryA->select('files.id')
                    ->from('files')
                    ->join('file_users', 'files.id', '=', 'file_users.file_id')
                    ->where('files.userid', '!=', $user->userid)
                    ->where(function ($queryB) use ($user) {
                        $queryB->whereIn('file_users.userid', [0, $user->userid]);
                    });
            });
            if ($id) {
                $builder->where("id", $id);
            }
            if ($key) {
                $builder->where("name", "like", "%{$key}%");
            }
            $list = $builder->take($take)->get();
            if ($list->isNotEmpty()) {
                foreach ($list as $file) {
                    $temp = $file->toArray();
                    if ($file->pshare === $file->id) {
                        $temp['pid'] = 0;
                    }
                    $array[] = $temp;
                }
            }
        }
        //
        return Base::retSuccess('success', $array);
    }

    /**
     * @api {get} api/file/add          04. 添加、修改文件(夹)
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup file
     * @apiName add
     *
     * @apiParam {String} name          项目名称
     * @apiParam {String} type          文件类型
     * @apiParam {Number} [id]          文件ID（赋值修改文件名称）
     * @apiParam {Number} [pid]         父级ID
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
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
        $tmpName = preg_replace("/[\\\\\/:*?\"<>|]/", '', $name);
        if ($tmpName != $name) {
            return Base::retError("文件名称不能包含这些字符：\/:*?\"<>|");
        }
        //
        if ($id > 0) {
            // 修改
            $file = File::permissionFind($id, $user, 1);
            //
            $file->name = $name;
            $file->handleDuplicateName();
            $file->save();
            $data = [
                'id' => $file->id,
                'name' => $file->name,
            ];
            $file->pushMsg('update', $data);
            return Base::retSuccess('修改成功', $data);
        } else {
            // 添加
            if (!in_array($type, [
                'folder',
                'document',
                'mind',
                'drawio',
                'word',
                'excel',
                'ppt',
            ])) {
                return Base::retError('类型错误');
            }
            $ext = str_replace([
                'folder',
                'document',
                'mind',
                'drawio',
                'word',
                'excel',
                'ppt',
            ], [
                '',
                'md',
                'mind',
                'drawio',
                'docx',
                'xlsx',
                'pptx',
            ], $type);
            //
            $userid = $user->userid;
            if ($pid > 0) {
                if (File::wherePid($pid)->count() >= 300) {
                    return Base::retError('每个文件夹里最多只能创建300个文件或文件夹');
                }
                $row = File::permissionFind($pid, $user, 1);
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
            $file->handleDuplicateName();
            $file->saveBeforePP();
            //
            $data = File::find($file->id);
            $data->pushMsg('add', $data);
            return Base::retSuccess('添加成功', $data);
        }
    }

    /**
     * @api {get} api/file/copy          05. 复制文件(夹)
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup file
     * @apiName copy
     *
     * @apiParam {Number} id            文件ID
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function copy()
    {
        $user = User::auth();
        //
        $id = intval(Request::input('id'));
        //
        $row = File::permissionFind($id, $user);
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
        $file->handleDuplicateName();
        $data = AbstractModel::transaction(function() use ($file) {
            $content = FileContent::select(['content', 'text', 'size'])->whereFid($file->cid)->orderByDesc('id')->first();
            $file->size = $content?->size ?: 0;
            $file->saveBeforePP();
            if ($content) {
                $content = $content->toArray();
                $content['fid'] = $file->id;
                $content['userid'] = $file->userid;
                FileContent::createInstance($content)->save();
            }
            return File::find($file->id);
        });
        //
        $data->pushMsg('add', $data);
        return Base::retSuccess('复制成功', $data);
    }

    /**
     * @api {get} api/file/move          06. 移动文件(夹)
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup file
     * @apiName move
     *
     * @apiParam {Numbers} ids          文件ID（格式：[id1, id2]）
     * @apiParam {Number} pid           移动到的文件夹ID
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function move()
    {
        $user = User::auth();
        //
        $ids = Request::input('ids');
        $pid = intval(Request::input('pid'));
        //
        if (!is_array($ids) || empty($ids)) {
            return Base::retError('请选择移动的文件或文件夹');
        }
        if (count($ids) > 100) {
            return Base::retError('一次最多只能移动100个文件或文件夹');
        }
        $toShareFile = false;
        if ($pid > 0) {
            $tmpFile = File::permissionFind($pid, $user, 1);
            $toShareFile = $tmpFile->getShareInfo();
        }
        //
        $files = [];
        AbstractModel::transaction(function() use ($user, $pid, $ids, $toShareFile, &$files) {
            foreach ($ids as $id) {
                $file = File::permissionFind($id, $user, 1000);
                //
                if ($pid > 0) {
                    if ($toShareFile) {
                        if ($file->share) {
                            throw new ApiException("{$file->name} 当前正在共享，无法移动到另一个共享文件夹内");
                        }
                        if ($file->isSubShare()) {
                            throw new ApiException("{$file->name} 内含有共享文件，无法移动到另一个共享文件夹内");
                        }
                        $file->userid = $toShareFile->userid;
                        File::where('pids', 'LIKE', "%,{$file->id},%")->update(['userid' => $toShareFile->userid]);
                    }
                    //
                    $tmpId = $pid;
                    while ($tmpId > 0) {
                        if ($id == $tmpId) {
                            throw new ApiException('移动位置错误');
                        }
                        $tmpId = intval(File::whereId($tmpId)->value('pid'));
                    }
                } else {
                    $file->userid = $user->userid;
                    File::where('pids', 'LIKE', "%,{$file->id},%")->update(['userid' => $user->userid]);
                }
                //
                $file->pid = $pid;
                $file->handleDuplicateName();
                $file->saveBeforePP();
                $files[] = $file;
            }
        });
        foreach ($files as $file) {
            $file->pushMsg('update', $file);
        }
        return Base::retSuccess('操作成功', $files);
    }

    /**
     * @api {get} api/file/remove          07. 删除文件(夹)
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup file
     * @apiName remove
     *
     * @apiParam {Numbers} ids          文件ID（格式：[id1, id2]）
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function remove()
    {
        $user = User::auth();
        //
        $ids = Request::input('ids');
        //
        if (!is_array($ids) || empty($ids)) {
            return Base::retError('请选择删除的文件或文件夹');
        }
        if (count($ids) > 100) {
            return Base::retError('一次最多只能删除100个文件或文件夹');
        }
        //
        $files = [];
        AbstractModel::transaction(function() use ($user, $ids, &$files) {
            foreach ($ids as $id) {
                $file = File::permissionFind($id, $user, 1000);
                $file->deleteFile();
                $files[] = $file;
            }
        });
        //
        return Base::retSuccess('删除成功', $files);
    }

    /**
     * @api {get} api/file/content          08. 获取文件内容
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup file
     * @apiName content
     *
     * @apiParam {Number|String} id
     * - Number: 文件ID（需要登录）
     * - String: 链接码（不需要登录，用于预览）
     * @apiParam {String} only_update_at        仅获取update_at字段
     * - no (默认)
     * - yes
     * @apiParam {String} down                  直接下载
     * - no: 浏览（默认）
     * - yes: 下载（office文件直接下载，除非是preview）
     * - preview: 转预览地址
     * @apiParam {Number} [history_id]          读取历史记录ID
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function content()
    {
        $id = Request::input('id');
        $down = Request::input('down', 'no');
        $only_update_at = Request::input('only_update_at', 'no');
        $history_id = intval(Request::input('history_id'));
        //
        if (Base::isNumber($id)) {
            $user = User::auth();
            $file = File::permissionFind(intval($id), $user, $down == 'yes' ? 1 : 0);
        } elseif ($id) {
            $fileLink = FileLink::whereCode($id)->first();
            $file = $fileLink?->file;
            if (empty($file)) {
                $msg = '文件链接不存在';
                $data = File::code2IdName($id);
                if ($data) {
                    $msg = "【{$data->name}】 {$msg}";
                }
                return Base::retError($msg, $data);
            }
        } else {
            return Base::retError('参数错误');
        }
        //
        if ($only_update_at == 'yes') {
            return Base::retSuccess('success', [
                'id' => $file->id,
                'update_at' => Carbon::parse($file->updated_at)->toDateTimeString()
            ]);
        }
        //
        $builder = FileContent::whereFid($file->id);
        if ($history_id > 0) {
            $builder->whereId($history_id);
        }
        $content = $builder->orderByDesc('id')->first();
        if ($down === 'preview') {
            return Redirect::to(FileContent::formatPreview($file, $content?->content));
        }
        return FileContent::formatContent($file, $content?->content, $down == 'yes');
    }

    /**
     * @api {get} api/file/content/save          09. 保存文件内容
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup file
     * @apiName content__save
     *
     * @apiParam {Number} id                文件ID
     * @apiParam {Object} [D]               Request Payload 提交
     * - content: 内容
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function content__save()
    {
        $user = User::auth();
        //
        $id = intval(Request::input('id'));
        $content = Request::input('content');
        //
        $file = File::permissionFind($id, $user, 1);
        //
        $text = '';
        if ($file->type == 'document') {
            $data = Base::json2array($content);
            $isRep = false;
            preg_match_all("/<img\s+src=\"data:image\/(png|jpg|jpeg|webp);base64,(.*?)\"/s", $data['content'], $matchs);
            foreach ($matchs[2] as $key => $text) {
                $tmpPath = "uploads/file/document/" . date("Ym") . "/" . $id . "/attached/";
                Base::makeDir(public_path($tmpPath));
                $tmpPath .= md5($text) . "." . $matchs[1][$key];
                if (Base::saveContentImage(public_path($tmpPath), base64_decode($text))) {
                    $paramet = getimagesize(public_path($tmpPath));
                    $data['content'] = str_replace($matchs[0][$key], '<img src="' . Base::fillUrl($tmpPath) . '" original-width="' . $paramet[0] . '" original-height="' . $paramet[1] . '"', $data['content']);
                    $isRep = true;
                }
            }
            $text = strip_tags($data['content']);
            if ($isRep == true) {
                $content = Base::array2json($data);
            }
        }
        //
        switch ($file->type) {
            case 'document':
                $contentArray = Base::json2array($content);
                $contentString = $contentArray['content'];
                $file->ext = $contentArray['type'] == 'md' ? 'md' : 'text';
                break;
            case 'drawio':
                $contentArray = Base::json2array($content);
                $contentString = $contentArray['xml'];
                $file->ext = 'drawio';
                break;
            case 'mind':
                $contentString = $content;
                $file->ext = 'mind';
                break;
            case 'txt':
            case 'code':
                $contentArray = Base::json2array($content);
                $contentString = $contentArray['content'];
                break;
            default:
                return Base::retError('参数错误');
        }
        $path = "uploads/file/" . $file->type . "/" . date("Ym") . "/" . $id . "/" . md5($contentString);
        $save = public_path($path);
        Base::makeDir(dirname($save));
        file_put_contents($save, $contentString);
        //
        $content = FileContent::createInstance([
            'fid' => $file->id,
            'content' => [
                'type' => $file->ext,
                'url' => $path
            ],
            'text' => $text,
            'size' => filesize($save),
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
     * @api {get} api/file/office/token          10. 获取token
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup file
     * @apiName office__token
     *
     * @apiParam {array} config
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function office__token()
    {
        User::auth();
        //
        $config = Request::input('config');
        $token = \Firebase\JWT\JWT::encode($config, env('APP_KEY') ,'HS256');
        return Base::retSuccess('成功', [
            'token' => $token
        ]);
    }

    /**
     * @api {get} api/file/content/office          11. 保存文件内容（office）
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup file
     * @apiName content__office
     *
     * @apiParam {Number} id            文件ID
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
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
        $file = File::permissionFind($id, $user, 1);
        //
        if ($status === 2) {
            $parse = parse_url($url);
            $from = 'http://' . env('APP_IPPR') . '.3' . $parse['path'] . '?' . $parse['query'];
            $path = 'uploads/file/' . $file->type . '/' . date("Ym") . '/' . $file->id . '/' . $key;
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
                $file->updated_at = Carbon::now();
                $file->save();
                $file->pushMsg('update', $file);
            }
        }
        return ['error' => 0];
    }

    /**
     * @api {get} api/file/content/upload          12. 保存文件内容（上传文件）
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup file
     * @apiName content__upload
     *
     * @apiParam {Number} [pid]             父级ID
     * @apiParam {Number} [cover]           覆盖已存在的文件
     * - 0：不覆盖，保留两者（默认）
     * - 1：覆盖
     * @apiParam {String} [files]           文件名
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function content__upload()
    {
        $user = User::auth();
        $pid = intval(Request::input('pid'));
        $overwrite = intval(Request::input('cover'));
        $webkitRelativePath = Request::input('webkitRelativePath');
        $data = (new File)->contentUpload($user, $pid, $webkitRelativePath, $overwrite);
        return Base::retSuccess($data['data']['name'] . ' 上传成功', $data['addItem']);
    }

    /**
     * @api {get} api/file/content/history          13. 获取内容历史
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup file
     * @apiName content__history
     *
     * @apiParam {Number} id                文件ID
     *
     * @apiParam {Number} [page]            当前页，默认:1
     * @apiParam {Number} [pagesize]        每页显示数量，默认:20，最大:100
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function content__history()
    {
        $user = User::auth();
        //
        $id = Request::input('id');
        //
        $file = File::permissionFind(intval($id), $user);
        //
        $data = FileContent::select(['id', 'size', 'userid', 'created_at'])
            ->whereFid($file->id)
            ->orderByDesc('id')
            ->paginate(Base::getPaginate(100, 20));
        return Base::retSuccess('success', $data);
    }

    /**
     * @api {get} api/file/content/restore          14. 恢复文件历史
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup file
     * @apiName content__restore
     *
     * @apiParam {Number} id                文件ID
     * @apiParam {Number} history_id        历史数据ID
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function content__restore()
    {
        $user = User::auth();
        //
        $id = intval(Request::input('id'));
        $history_id = intval(Request::input('history_id'));
        //
        $file = File::permissionFind($id, $user);
        //
        $history = FileContent::whereFid($file->id)->whereId($history_id)->first();
        if (empty($history)) {
            return Base::retError('历史数据不存在或已被删除');
        }
        //
        $content = $history->replicate();
        $content->userid = $user->userid;
        $content->save();
        //
        $file->size = $content->size;
        $file->save();
        $file->pushMsg('content');
        //
        return Base::retSuccess('还原成功');
    }

    /**
     * @api {get} api/file/share          15. 获取共享信息
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup file
     * @apiName share
     *
     * @apiParam {Number} id            文件ID
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
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
     * @api {get} api/file/share/update          16. 设置共享
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup file
     * @apiName share__update
     *
     * @apiParam {Number} id            文件ID
     * @apiParam {Array} [userids]      共享成员，格式: [userid1, userid2, userid3]
     * @apiParam {Number} [permission]  共享方式
     * - 0：只读
     * - 1：读写
     * - -1: 删除
     * @apiParam {Number} [force]       设置共享时是否忽略提醒
     * - 0：如果子文件夹已存在共享则ret返回-3001（默认）
     * - 1：忽略提醒
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function share__update()
    {
        $user = User::auth();
        //
        $id = intval(Request::input('id'));
        $userids = Request::input('userids');
        $permission = intval(Request::input('permission'));
        $force = intval(Request::input('force'));
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
        $share = $file->isNnShare();
        if ($share) {
            $typeCn = $file->type === 'folder' ? '文件夹' : '文件';
            return Base::retError("此{$typeCn}已经处于【{$share->name}】共享文件夹中，无法重复共享");
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
                if (FileUser::deleteFileUser($file->id, $userid)) {
                    $array[] = $userid;
                }
            }
        } else {
            // 设置共享
            $action = "update";
            if ($force === 0) {
                if ($file->isSubShare()) {
                    return Base::retError('此文件夹内已有共享文件夹', [], -3001);
                }
            }
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
        $file->updataShare();
        $file->pushMsg($action, $action == "delete" ? null : $file, $array);
        return Base::retSuccess($action == "delete" ? "删除成功" : "设置成功", $file);
    }

    /**
     * @api {get} api/file/share/out          17. 退出共享
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup file
     * @apiName share__out
     *
     * @apiParam {Number} id            文件ID
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function share__out()
    {
        $user = User::auth();
        //
        $id = intval(Request::input('id'));
        //
        $file = File::permissionFind($id, $user);
        //
        if ($file->userid == $user->userid) {
            return Base::retError('不能退出自己共享的文件');
        }
        if (FileUser::whereFileId($file->id)->whereUserid(0)->exists()) {
            return Base::retError('无法退出共享所有人的文件或文件夹');
        }
        FileUser::deleteFileUser($file->id, $user->userid);
        //
        $file->updataShare();
        return Base::retSuccess("退出成功");
    }

    /**
     * @api {get} api/file/link          18. 获取链接
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup file
     * @apiName link
     *
     * @apiParam {Number} id                文件ID
     * @apiParam {String} refresh           刷新链接
     * - no: 只获取（默认）
     * - yes: 刷新链接，之前的将失效
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function link()
    {
        $user = User::auth();
        //
        $id = intval(Request::input('id'));
        $refresh = Request::input('refresh', 'no');
        //
        $file = File::permissionFind($id, $user);
        $fileLink = $file->getShareLink($user->userid, $refresh == 'yes');
        //
        return Base::retSuccess('success', $fileLink);
    }

    /**
     * @api {get} api/file/download/pack          19. 打包文件
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup file
     * @apiName download__pack
     *
     * @apiParam {Array} [ids]          文件ID，格式: [id, id2, id3]
     * @apiParam {String} [name]        下载文件名
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function download__pack()
    {
        $key = Request::input('key');
        if ($key) {
            $userid = Session::get('file::pack:userid');
            if (empty($userid)) {
                return Base::ajaxError("请求已过期，请重新导出！", [], 0, 502);
            }
            //
            $array = Base::string2array(base64_decode(urldecode($key)));
            $file = $array['file'];
            if (empty($file) || !file_exists(storage_path($file))) {
                return Base::ajaxError("文件不存在！", [], 0, 502);
            }
            return Response::download(storage_path($file));
        }

        $user = User::auth();
        $ids = Request::input('ids');
        $fileName = Request::input('name');
        $fileName = preg_replace("/[\/\\\:\*\?\"\<\>\|]/", "", $fileName);
        if (empty($fileName)) {
            $fileName = 'Package_' . $user->userid;
        }
        $fileName .= '_' . Base::time() . '.zip';

        $filePath = "temp/file/pack/" . date("Ym", Base::time());
        $zipFile = "app/" . $filePath . "/" . $fileName;
        $zipPath = storage_path($zipFile);

        if (!is_array($ids) || empty($ids)) {
            return Base::retError('请选择下载的文件或文件夹');
        }
        if (count($ids) > 100) {
            return Base::retError('一次最多可以下载100个文件或文件夹');
        }

        $botUser = User::botGetOrCreate('system-msg');
        if (empty($botUser)) {
            return Base::retError('系统机器人不存在');
        }
        $dialog = WebSocketDialog::checkUserDialog($botUser, $user->userid);

        $files = [];
        $totalSize = 0;

        foreach ($ids as $k => $id) {
            $files[] = File::getFilesTree(intval($id), $user, 1);
            $totalSize += $files[$k]->totalSize;
        }

        if ($totalSize > File::zipMaxSize) {
            return Base::retError('文件总大小已超过1GB，请分批下载');
        }

        $base64 = base64_encode(Base::array2string([
            'file' => $zipFile,
        ]));
        $fileUrl = Base::fillUrl('api/file/download/pack?key=' . urlencode($base64));
        Session::put('file::pack:userid', $user->userid);

        $zip = new \ZipArchive();
        Base::makeDir(dirname($zipPath));

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return Base::retError('创建压缩文件失败');
        }

        go(function () use ($zipPath, $fileUrl, $zip, $files, $fileName, $botUser, $dialog) {
            Coroutine::sleep(0.1);
            // 压缩进度
            $progress = 0;
            $zip->registerProgressCallback(0.05, function ($ratio) use ($fileUrl, $fileName, &$progress) {
                $progress = round($ratio * 100);
                File::filePushMsg('compress', [
                    'name' => $fileName,
                    'url' => $fileUrl,
                    'progress' => $progress
                ]);
            });
            //
            foreach ($files as $file) {
                File::addFileTreeToZip($zip, $file);
            }
            $zip->close();
            //
            if ($progress < 100) {
                File::filePushMsg('compress', [
                    'name' => $fileName,
                    'url' => $fileUrl,
                    'progress' => 100
                ]);
            }
            //
            $text = "<b>文件下载打包已完成。</b>";
            $text .= "\n\n";
            $text .= "文件名：{$fileName}";
            $text .= "\n";
            $text .= "文件大小：".Base::twoFloat(filesize($zipPath) / 1024, true)."KB";
            $text .= "\n";
            $text .= '<a href="' . $fileUrl . '" target="_blank"><button type="button" class="ivu-btn ivu-btn-warning" style="margin-top: 10px;"><span>立即下载</span></button></a>';
            WebSocketDialogMsg::sendMsg(null, $dialog->id, 'text', ['text' => $text], $botUser->userid, false, false, true);
        });
        return Base::retSuccess('success', [
            'name' => $fileName,
            'url' => $fileUrl,
        ]);
    }
}
