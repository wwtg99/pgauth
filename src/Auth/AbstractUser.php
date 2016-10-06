<?php
/**
 * Created by PhpStorm.
 * User: wwt
 * Date: 2016/10/6 0006
 * Time: 下午 3:40
 */

namespace Wwtg99\PgAuth\Auth;


abstract class AbstractUser implements IUser
{

    /**
     * @var array
     */
    protected $user = [];

    /**
     * AbstractUser constructor.
     * @param array $user
     */
    public function __construct(array $user)
    {
        $this->user = $user;
    }

    /**
     * @return array
     */
    public function getUser()
    {
        return $this->user;
    }


}