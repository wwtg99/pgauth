<?php
/**
 * Created by PhpStorm.
 * User: wwt
 * Date: 2016/10/6 0006
 * Time: 下午 3:32
 */

namespace Wwtg99\PgAuth\Auth;


interface IAuth
{

    /**
     * @param array $user
     * @return IUser|null
     */
    public function signUp(array $user);

    /**
     * @param array $user
     * @return IUser|null
     */
    public function signIn(array $user);

    /**
     * @param array $user
     * @return IUser|null
     */
    public function signOut(array $user);

    /**
     * @param array $user
     * @return IUser|null
     */
    public function verify(array $user);

    /**
     * @return string
     */
    public function getMessage();

}