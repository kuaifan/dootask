<?php

namespace App\Http\Controllers\Api;

use App\Models\AbstractModel;
use App\Models\Project;
use App\Models\ProjectColumn;
use App\Models\ProjectLog;
use App\Models\ProjectUser;
use App\Models\User;
use App\Module\Base;
use Request;

/**
 * @apiDefine project
 *
 * 项目
 */
class ProjectController extends AbstractController
{
    private $projectSelect = [
        '*',
        'projects.id AS id',
        'projects.userid AS userid',
        'projects.created_at AS created_at',
        'projects.updated_at AS updated_at',
    ];

    /**
     * 项目列表
     *
     * @apiParam {Number} [page]        当前页，默认:1
     * @apiParam {Number} [pagesize]    每页显示数量，默认:100，最大:200
     */
    public function lists()
    {
        $user = User::authE();
        if (Base::isError($user)) {
            return $user;
        } else {
            $user = User::IDE($user['data']);
        }
        //
        $list = Project::select($this->projectSelect)
            ->join('project_users', 'projects.id', '=', 'project_users.project_id')
            ->where('project_users.userid', $user->userid)
            ->orderByDesc('projects.id')
            ->paginate(Base::getPaginate(200, 100));
        //
        return Base::retSuccess('success', $list);
    }

    /**
     * 项目详情
     *
     * @apiParam {Number} project_id     项目ID
     */
    public function detail()
    {
        $user = User::authE();
        if (Base::isError($user)) {
            return $user;
        } else {
            $user = User::IDE($user['data']);
        }
        //
        $project_id = intval(Request::input('project_id'));
        //
        $project = Project::with(['projectColumn' => function($query) {
            $query->with(['projectTask' => function($taskQuery) {
                $taskQuery->where('parent_id', 0);
            }]);
        }, 'projectUser'])
            ->select($this->projectSelect)
            ->join('project_users', 'projects.id', '=', 'project_users.project_id')
            ->where('projects.id', $project_id)
            ->where('project_users.userid', $user->userid)
            ->first();
        if ($project) {
            $owner_user = $project->projectUser->where('owner', 1)->first();
            $project->owner_userid = $owner_user ? $owner_user->userid : 0;
        }
        //
        return Base::retSuccess('success', $project);
    }

    /**
     * 添加项目
     *
     * @apiParam {String} name          项目名称
     * @apiParam {String} [desc]        项目描述
     * @apiParam {Array} [columns]      流程，格式[流程1, 流程2]
     */
    public function add()
    {
        $user = User::authE();
        if (Base::isError($user)) {
            return $user;
        } else {
            $user = User::IDE($user['data']);
        }
        //项目名称
        $name = trim(Request::input('name', ''));
        $desc = trim(Request::input('desc', ''));
        if (mb_strlen($name) < 2) {
            return Base::retError('项目名称不可以少于2个字！');
        } elseif (mb_strlen($name) > 32) {
            return Base::retError('项目名称最多只能设置32个字！');
        }
        //流程
        $columns = Request::input('columns');
        if (!is_array($columns)) $columns = [];
        $insertColumns = [];
        $inorder = 0;
        foreach ($columns AS $column) {
            $column = trim($column);
            if ($column) {
                $insertColumns[] = [
                    'name' => $column,
                    'inorder' => $inorder++,
                ];
            }
        }
        if (empty($insertColumns)) {
            $insertColumns[] = [
                'name' => 'Default',
                'inorder' => 0,
            ];
        }
        if (count($insertColumns) > 30) {
            return Base::retError('项目流程最多不能超过30个！');
        }
        //开始创建
        $project = Project::createInstance([
            'name' => $name,
            'desc' => $desc,
            'userid' => $user->userid,
        ]);
        return AbstractModel::transaction(function() use ($user, $insertColumns, $project) {
            $project->save();
            ProjectUser::createInstance([
                'project_id' => $project->id,
                'userid' => $user->userid,
                'owner' => 1,
            ])->save();
            ProjectLog::createInstance([
                'project_id' => $project->id,
                'userid' => $user->userid,
                'detail' => '创建项目',
            ])->save();
            foreach ($insertColumns AS $column) {
                $column['project_id'] = $project->id;
                ProjectColumn::createInstance($column)->save();
            }
            return Base::retSuccess('添加成功！');
        });
    }
}
