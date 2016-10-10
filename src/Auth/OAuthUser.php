<?php
/**
 * Created by PhpStorm.
 * User: wwt
 * Date: 2016/10/6 0006
 * Time: 下午 9:15
 */

namespace Wwtg99\PgAuth\Auth;


class OAuthUser extends AbstractUser
{

    /**
     * OAuthUser constructor.
     * @param array $user
     */
    public function __construct(array $user)
    {
        parent::__construct($user);
    }

    /**
     * @param array $user
     * @return bool
     */
    public function changeInfo(array $user)
    {
        return false;
    }

    /**
     * @param string $new
     * @return bool
     */
    public function changePassword($new)
    {
        return false;
    }

    /**
     * @return bool
     */
    public function active()
    {
        return false;
    }

    /**
     * @return bool
     */
    public function inactive()
    {
        return false;
    }


}