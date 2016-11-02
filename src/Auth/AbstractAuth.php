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

    public $keyUserName = 'username';
    public $keyPassword = 'password';
    public $keyAccessToken = 'access_token';

    /**
     * @var string
     */
    protected $msg = '';

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->msg;
    }


}