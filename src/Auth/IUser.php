<?php
/**
 * Created by PhpStorm.
 * User: wwt
 * Date: 2016/10/6 0006
 * Time: 下午 3:35
 */

namespace Wwtg99\PgAuth\Auth;


interface IUser
{

    const FIELD_USER_ID = 'user_id';
    const FIELD_USER_NAME = 'name';
    const FIELD_PASSWORD = 'password';
    const FIELD_ROLES = 'roles';
    const FIELD_LABEL = 'label';
    const FIELD_EMAIL = 'email';
    const FIELD_TEL = 'tel';
    const FIELD_SUPERUSER = 'superuser';
    const FIELD_DEPARTMENT_ID = 'department_id';
    const FIELD_DEPARTMENT = 'department';
    const FIELD_TOKEN = 'access_token';

    /**
     * @return array
     */
    public function getUserArray();

    /**
     * @return string
     */
    public function getId();
}