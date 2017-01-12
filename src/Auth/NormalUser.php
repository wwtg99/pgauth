<?php
/**
 * Created by PhpStorm.
 * User: wwt
 * Date: 2016/10/6 0006
 * Time: 下午 3:43
 */

namespace Wwtg99\PgAuth\Auth;


class NormalUser extends AbstractUser
{

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
            $re = $this->mapper->update($user, null, $this->userId);
            $this->refreshUser();
        } else {
            $this->user = $user;
            $re = true;
        }
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

}