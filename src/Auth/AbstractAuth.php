<?php
/**
 * Created by PhpStorm.
 * User: wwt
 * Date: 2016/10/6 0006
 * Time: 下午 9:37
 */

namespace Wwtg99\PgAuth\Auth;


abstract class AbstractAuth implements IAuth
{

    /**
     * @var string
     */
    protected $msg = '';

    /**
     * @var IUser
     */
    protected $user = null;

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->msg;
    }

}