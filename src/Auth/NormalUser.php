<?php
/**
 * Created by PhpStorm.
 * User: wwt
 * Date: 2016/10/6 0006
 * Time: 下午 3:43
 */

namespace Wwtg99\PgAuth\Auth;


use Wwtg99\DataPool\Common\IDataConnection;

class NormalUser extends AbstractUser
{

    /**
     * @var IDataConnection
     */
    protected $conn;

    /**
     * @var NormalAuth
     */
    protected $auth;

    /**
     * NormalUser constructor.
     *
     * @param array $user
     * @param IDataConnection $conn
     * @param NormalAuth $auth
     */
    public function __construct(array $user, $conn, $auth)
    {
        parent::__construct($user);
        $this->conn = $conn;
        $this->auth = $auth;
    }

    /**
     * @param array $user
     * @return bool
     */
    public function changeInfo(array $user)
    {
        $u = $this->getUserMapper();
        $uid = $this->user[self::FIELD_USER_ID];
        if (isset($user[self::FIELD_ROLES])) {
            $roles = $user[self::FIELD_ROLES];
            if (!is_array($roles)) {
                $roles = explode(',', $roles);
            }
            $r = [];
            foreach ($roles as $role) {
                array_push($r, ['role_name'=>$role]);
            }
            $re = $u->changeRoles($uid, $r);
            if (!$re) {
                return false;
            }
            unset($user[self::FIELD_ROLES]);
        }
        $re = $u->update($user, null, $uid);
        $this->refreshUser();
        return boolval($re);
    }

    /**
     * @param string $new
     * @return bool
     */
    public function changePassword($new)
    {
        if ($new) {
            return $this->changeInfo([self::FIELD_PASSWORD=>password_hash($new, PASSWORD_BCRYPT)]);
        }
        return false;
    }

    /**
     * @return bool
     */
    public function active()
    {
        $u = $this->getUserMapper();
        $uid = $this->user[self::FIELD_USER_ID];
        $re = $u->activeUser($uid, true);
        $this->refreshUser();
        return $re;
    }

    /**
     * @return bool
     */
    public function inactive()
    {
        $u = $this->getUserMapper();
        $uid = $this->user[self::FIELD_USER_ID];
        $re = $u->activeUser($uid, false);
        $this->refreshUser();
        return $re;
    }

    /**
     * @return \Wwtg99\DataPool\Common\IDataMapper
     */
    protected function getUserMapper()
    {
        return $this->conn->getMapper('User');
    }

    /**
     * Refresh user
     */
    protected function refreshUser()
    {
        $u = $this->getUserMapper()->get($this->user[IUser::FIELD_USER_ID]);
        if ($u) {
            unset($u[self::FIELD_PASSWORD]);
            $this->user = array_merge($this->user, $u);
            //update token in cache
            $token = $this->user[$this->auth->keyAccessToken];
            $this->auth->getCache()->set($token, json_encode($this->user, JSON_UNESCAPED_UNICODE), $this->auth->tokenTtl);
        }
    }

}