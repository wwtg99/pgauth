<?php
/**
 * Created by PhpStorm.
 * User: wwt
 * Date: 2016/10/6 0006
 * Time: 下午 3:43
 */

namespace Wwtg99\PgAuth\Auth;


use Wwtg99\PgAuth\Mapper\User;

class NormalUser implements IUser
{

    /**
     * @var string
     */
    protected $userId = '';

    /**
     * @var string
     */
    protected $token = '';

    /**
     * @var array
     */
    protected $user = [];

    /**
     * @var User
     */
    protected $mapper = null;

    /**
     * AbstractUser constructor.
     * @param $user_id
     * @param $user
     * @param $token
     */
    public function __construct($user_id, $user, $token = '')
    {
        $this->userId = $user_id;
        $this->token = $token;
        if ($user instanceof User) {
            $this->mapper = $user;
            $this->refreshUser();
        } elseif (is_array($user)) {
            $this->user = $user;
        }
    }

    /**
     * @return array
     */
    public function getUserArray()
    {
        $u = $this->user;
        if ($this->token) {
            $u[self::FIELD_TOKEN] = $this->token;
        }
        return $u;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->userId;
    }

    /**
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @return array
     */
    public function getRoles()
    {
        $r = isset($this->user[self::FIELD_ROLES]) ? $this->user[self::FIELD_ROLES] : [];
        if (!is_array($r)) {
            $r = explode(',', $r);
        }
        return $r;
    }

    /**
     * @return User
     */
    public function getMapper()
    {
        return $this->mapper;
    }

    /**
     * @param User $mapper
     * @return NormalUser
     */
    public function setMapper($mapper)
    {
        $this->mapper = $mapper;
        return $this;
    }

    /**
     * @param array $user
     * @return bool
     */
    public function changeInfo(array $user)
    {
        if ($this->mapper) {
            if (isset($user[self::FIELD_ROLES])) {
                $roles = $user[self::FIELD_ROLES];
                if (!is_array($roles)) {
                    $roles = explode(',', $roles);
                }
                $r = [];
                foreach ($roles as $role) {
                    array_push($r, ['role_name'=>$role]);
                }
                $re = $this->mapper->changeRoles($this->userId, $r);
                if (!$re) {
                    return false;
                }
                unset($user[self::FIELD_ROLES]);
            }
            if ($user) {
                $re = $this->mapper->update($user, null, $this->userId);
            } else {
                $re = true;
            }
            $this->refreshUser();
        } else {
            $this->user = $user;
            $re = true;
        }
        return boolval($re);
    }

    /**
     * @param $roles
     * @return bool
     */
    public function changeRoles($roles)
    {
        if ($roles) {
            return $this->changeInfo([self::FIELD_ROLES=>$roles]);
        }
        return false;
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
        if ($this->mapper) {
            $re = $this->mapper->activeUser($this->userId, true);
            $this->refreshUser();
        } else {
            $re = false;
        }
        return $re;
    }

    /**
     * @return bool
     */
    public function inactive()
    {
        if ($this->mapper) {
            $re = $this->mapper->activeUser($this->userId, false);
            $this->refreshUser();
        } else {
            $re = false;
        }
        return $re;
    }

    /**
     * Refresh user
     * @return array|null
     */
    protected function refreshUser()
    {
        if ($this->mapper) {
            $u = $this->mapper->view('*', [IUser::FIELD_USER_ID=>$this->userId]);
            if ($u && isset($u[0])) {
                unset($u[0][self::FIELD_PASSWORD]);
                $this->user = $u[0];
            }
            return $u[0];
        }
        return null;
    }
}
