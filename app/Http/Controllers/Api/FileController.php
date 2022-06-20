<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\ApiException;
use App\Models\AbstractModel;
use App\Models\File;
use App\Models\FileContent;
use App\Models\FileLink;
use App\Models\FileUser;
use App\Models\User;
use App\Module\Base;
use App\Module\Ihttp;
use Carbon\Carbon;
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
        $permission = 1000;
        if ($pid > 0) {
            File::permissionFind($pid, 0, $permission);
            $builder = File::wherePid($pid);
        } else {
            $builder = File::whereUserid($user->userid);
        }
        //
        $array = $builder->take(500)->get()->toArray();
        foreach ($array as &$item) {
            $item['permission'] = $permission;
        }
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
                $temp['permission'] = $file->getPermission($user->userid);
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
        // 图片直接返回预览地址
        foreach ($array as &$item) {
            File::handleImageUrl($item);
        }
        return Base::retSuccess('success', $array);
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
            User::auth();
            $file = File::permissionFind(intval($id), 0, $permission);
        } elseif ($id) {
            $fileLink = FileLink::whereCode($id)->first();
            $file = $fileLink?->file;
            if (empty($file)) {
                return Base::retError('链接不存在');
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
        //
        if ($id > 0) {
            // 修改
            $file = File::permissionFind($id, 1);
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
                $row = File::permissionFind($pid, 1);
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
            $file->saveBeforePids();
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
        $row = File::permissionFind($id);
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
        $data = AbstractModel::transaction(function() use ($file) {
            $content = FileContent::select(['content', 'text', 'size'])->whereFid($file->cid)->orderByDesc('id')->first();
            $file->size = $content?->size ?: 0;
            $file->saveBeforePids();
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
        User::auth();
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
        if ($pid > 0) {
            File::permissionFind($pid, 1);
        }
        //
        $files = [];
        AbstractModel::transaction(function() use ($pid, $ids, &$files) {
            foreach ($ids as $id) {
                $file = File::permissionFind($id, 1000);
                //
                if ($pid > 0) {
                    $arr = [];
                    $tid = $pid;
                    while ($tid > 0) {
                        $arr[] = $tid;
                        $tid = intval(File::whereId($tid)->value('pid'));
                    }
                    if (in_array($id, $arr)) {
                        throw new ApiException('移动位置错误');
                    }
                }
                //
                $file->pid = $pid;
                $file->saveBeforePids();
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
        User::auth();
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
        AbstractModel::transaction(function() use ($ids, &$files) {
            foreach ($ids as $id) {
                $file = File::permissionFind($id, 1000);
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
     * - yes: 下载（office文件直接下载）
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
        //
        if (Base::isNumber($id)) {
            User::auth();
            $file = File::permissionFind(intval($id));
        } elseif ($id) {
            $fileLink = FileLink::whereCode($id)->first();
            $file = $fileLink?->file;
            if (empty($file)) {
                return Base::retError('链接不存在');
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
        $content = FileContent::whereFid($file->id)->orderByDesc('id')->first();
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
        Base::checkClientVersion('0.9.13');
        $user = User::auth();
        //
        $id = Base::getPostInt('id');
        $content = Base::getPostValue('content');
        //
        $file = File::permissionFind($id, 1);
        //
        $text = '';
        if ($file->type == 'document') {
            $data = Base::json2array($content);
            $isRep = false;
            preg_match_all("/<img\s*src=\"data:image\/(png|jpg|jpeg);base64,(.*?)\"/s", $data['content'], $matchs);
            foreach ($matchs[2] as $key => $text) {
                $tmpPath = "uploads/file/document/" . date("Ym") . "/" . $id . "/attached/";
                Base::makeDir(public_path($tmpPath));
                $tmpPath .= md5($text) . "." . $matchs[1][$key];
                if (file_put_contents(public_path($tmpPath), base64_decode($text))) {
                    $data['content'] = str_replace($matchs[0][$key], '<img src="' . Base::fillUrl($tmpPath) . '"', $data['content']);
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
            case 'code':
            case 'txt':
                $contentString = $content;
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
     * @api {get} api/file/content/office          10. 保存文件内容（office）
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
        $file = File::permissionFind($id, 1);
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
     * @api {get} api/file/content/upload          11. 保存文件内容（上传文件）
     *
     * @apiDescription 需要token身份
     * @apiVersion 1.0.0
     * @apiGroup file
     * @apiName content__upload
     *
     * @apiParam {Number} [pid]         父级ID
     * @apiParam {String} [files]           文件名
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function content__upload()
    {
        $user = User::auth();
        //
        $pid = intval(Request::input('pid'));
        $webkitRelativePath = Request::input('webkitRelativePath');
        //
        $userid = $user->userid;
        if ($pid > 0) {
            if (File::wherePid($pid)->count() >= 300) {
                return Base::retError('每个文件夹里最多只能创建300个文件或文件夹');
            }
            $row = File::permissionFind($pid, 1);
            $userid = $row->userid;
        } else {
            if (File::whereUserid($user->userid)->wherePid(0)->count() >= 300) {
                return Base::retError('每个文件夹里最多只能创建300个文件或文件夹');
            }
        }
        //
        $dirs = explode("/", $webkitRelativePath);
        while (count($dirs) > 1) {
            $dirName = array_shift($dirs);
            if ($dirName) {
                $pushMsg = [];
                AbstractModel::transaction(function () use ($dirName, $user, $userid, &$pid, &$pushMsg) {
                    $dirRow = File::wherePid($pid)->whereType('folder')->whereName($dirName)->lockForUpdate()->first();
                    if (empty($dirRow)) {
                        $dirRow = File::createInstance([
                            'pid' => $pid,
                            'type' => 'folder',
                            'name' => $dirName,
                            'userid' => $userid,
                            'created_id' => $user->userid,
                        ]);
                        if ($dirRow->saveBeforePids()) {
                            $pushMsg[] = File::find($dirRow->id);
                        }
                    }
                    if (empty($dirRow)) {
                        throw new ApiException('创建文件夹失败');
                    }
                    $pid = $dirRow->id;
                });
                foreach ($pushMsg as $tmpRow) {
                    $tmpRow->pushMsg('add', $tmpRow);
                }
            }
        }
        //
        $path = 'uploads/tmp/' . date("Ym") . '/';
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
            'text', 'md', 'markdown' => 'document',
            'drawio' => 'drawio',
            'mind' => 'mind',
            'doc', 'docx' => "word",
            'xls', 'xlsx' => "excel",
            'ppt', 'pptx' => "ppt",
            'wps' => "wps",
            'jpg', 'jpeg', 'png', 'gif', 'bmp', 'ico', 'raw', 'svg' => "picture",
            'rar', 'zip', 'jar', '7-zip', 'tar', 'gzip', '7z', 'gz', 'apk', 'dmg' => "archive",
            'tif', 'tiff' => "tif",
            'dwg', 'dxf' => "cad",
            'ofd' => "ofd",
            'pdf' => "pdf",
            'txt' => "txt",
            'htaccess', 'htgroups', 'htpasswd', 'conf', 'bat', 'cmd', 'cpp', 'c', 'cc', 'cxx', 'h', 'hh', 'hpp', 'ino', 'cs', 'css',
            'dockerfile', 'go', 'golang', 'html', 'htm', 'xhtml', 'vue', 'we', 'wpy', 'java', 'js', 'jsm', 'jsx', 'json', 'jsp', 'less', 'lua', 'makefile', 'gnumakefile',
            'ocamlmakefile', 'make', 'mysql', 'nginx', 'ini', 'cfg', 'prefs', 'm', 'mm', 'pl', 'pm', 'p6', 'pl6', 'pm6', 'pgsql', 'php',
            'inc', 'phtml', 'shtml', 'php3', 'php4', 'php5', 'phps', 'phpt', 'aw', 'ctp', 'module', 'ps1', 'py', 'r', 'rb', 'ru', 'gemspec', 'rake', 'guardfile', 'rakefile',
            'gemfile', 'rs', 'sass', 'scss', 'sh', 'bash', 'bashrc', 'sql', 'sqlserver', 'swift', 'ts', 'typescript', 'str', 'vbs', 'vb', 'v', 'vh', 'sv', 'svh', 'xml',
            'rdf', 'rss', 'wsdl', 'xslt', 'atom', 'mathml', 'mml', 'xul', 'xbl', 'xaml', 'yaml', 'yml',
            'asp', 'properties', 'gitignore', 'log', 'bas', 'prg', 'python', 'ftl', 'aspx' => "code",
            'mp3', 'wav', 'mp4', 'flv',
            'avi', 'mov', 'wmv', 'mkv', '3gp', 'rm' => "media",
            'xmind' => "xmind",
            'rp' => "axure",
            default => "",
        };
        if ($data['ext'] == 'markdown') {
            $data['ext'] = 'md';
        }
        $file = File::createInstance([
            'pid' => $pid,
            'name' => Base::rightDelete($data['name'], '.' . $data['ext']),
            'type' => $type,
            'ext' => $data['ext'],
            'userid' => $userid,
            'created_id' => $user->userid,
        ]);
        // 开始创建
        return AbstractModel::transaction(function () use ($webkitRelativePath, $type, $user, $data, $file) {
            $file->size = $data['size'] * 1024;
            $file->saveBeforePids();
            //
            $data = Base::uploadMove($data, "uploads/file/" . $file->type . "/" . date("Ym") . "/" . $file->id . "/");
            $content = FileContent::createInstance([
                'fid' => $file->id,
                'content' => [
                    'from' => '',
                    'type' => $type,
                    'ext' => $data['ext'],
                    'url' => $data['path']
                ],
                'text' => '',
                'size' => $file->size,
                'userid' => $user->userid,
            ]);
            $content->save();
            //
            $tmpRow = File::find($file->id);
            $tmpRow->pushMsg('add', $tmpRow);
            //
            $data = $tmpRow->toArray();
            $data['full_name'] = $webkitRelativePath ?: $data['name'];
            File::handleImageUrl($data);
            return Base::retSuccess($data['name'] . ' 上传成功', $data);
        });
    }

    /**
     * @api {get} api/file/share          12. 获取共享信息
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
     * @api {get} api/file/share/update          13. 设置共享
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
                if (FileUser::deleteFileUser($file->id, $userid)) {
                    $array[] = $userid;
                }
            }
        } else {
            // 设置共享
            $action = "update";
            if ($force === 0) {
                if (File::where("pids", "like", "%,{$file->id},%")->whereShare(1)->exists()) {
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
     * @api {get} api/file/share/out          14. 退出共享
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
        $file = File::permissionFind($id);
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
     * @api {get} api/file/link          15. 获取链接
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
        $file = File::permissionFind($id);
        if ($file->type == 'folder') {
            return Base::retError('文件夹暂不支持此功能');
        }
        //
        $fileLink = FileLink::whereFileId($file->id)->whereUserid($user->userid)->first();
        if (empty($fileLink)) {
            $fileLink = FileLink::createInstance([
                'file_id' => $file->id,
                'userid' => $user->userid,
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
