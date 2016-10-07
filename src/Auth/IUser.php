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

    /**
     * @param array $user
     * @return bool
     */
    public function changeInfo(array $user);

    /**
     * @param string $new
     * @return bool
     */
    public function changePassword($new);

    /**
     * @return array
     */
    public function getUser();

    /**
     * @return bool
     */
    public function active();

    /**
     * @return bool
     */
    public function inactive();
}