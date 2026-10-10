<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Module\Base;
use App\Module\ContactsDepartment;

/**
 * @apiDefine contacts
 *
 * 通讯录
 */
class ContactsController extends AbstractController
{
    /**
     * @api {get} api/contacts/departments 通讯录部门树
     *
     * @apiDescription 需要token身份，返回深度优先展开的部门列表；部门成员通过 api/users/search（keys.departments）按需加载
     * @apiVersion 1.0.0
     * @apiGroup contacts
     * @apiName departments
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     * - total: 联系人总数
     * - no_department_count: 未加入任何部门的联系人数
     * - departments: 部门列表，字段 id、name、parent_id、owner_userid、level（层级，顶级为0）、member_count（直属成员数）、total_count（含下级部门的成员数）
     */
    public function departments()
    {
        User::auth();
        //
        return Base::retSuccess('success', ContactsDepartment::tree());
    }
}
