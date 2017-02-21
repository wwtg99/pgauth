<?php
/**
 * Created by PhpStorm.
 * User: wwt
 * Date: 2016/10/6 0006
 * Time: 下午 4:02
 */

namespace Wwtg99\PgAuth\Auth;


use Wwtg99\DataPool\Common\IDataConnection;
use Wwtg99\PgAuth\Mapper\User;
use Wwtg99\PgAuth\Utils\Cache;

/**
 * Class NormalAuth
 * Normal auth
 * Verify by username, user_id, password or access_token.
 * @package Wwtg99\PgAuth\Auth
 */
class NormalAuth extends AbstractAuth
{

    const VERIFY_NAME_PASSWORD = 1;
    const VERIFY_NAME_TOKEN = 2;
    const VERIFY_TOKEN = 4;
    const VERIFY_USER_ID = 8;
    const VERIFY_EMAIL_PASSWORD = 16;
    const VERIFY_EMAIL_TOKEN = 32;
    const VERIFY_TEL_PASSWORD = 64;
    const VERIFY_TEL_TOKEN = 128;

    /**
     * @var \Desarrolla2\Cache\Cache
     */
    protected $cache = null;

    /**
     * @var \Wwtg99\DataPool\Common\IDataConnection
     */
    protected $conn = null;

    /**
     * Verify method
     * 1: name and password
     * 2: name and access_token
     * 4: access_token only
     * 8: user_id only
     * 16: email and password
     * 32: email and access_token
     * 64: tel and password
     * 128: tel and access_token
     * @var int
     */
    protected $authMethod = 1;

    /**
     * @var int
     */
    protected $tokenTtl = 3600;

    /**
     * @var bool
     */
    protected $uniqueEmail = true;

    /**
     * @var bool
     */
    protected $uniqueTel = true;

    /**
     * NormalAuth constructor.
     *
     * @param IDataConnection $conn
     * @param array $config
     */
    public function __construct($conn = null, $config = null)
    {
        if (isset($config['cache'])) {
            $ch = $config['cache'];
            if (isset($ch['type']) && isset($ch['options'])) {
                $this->cache = Cache::getCache($ch['type'], $ch['options']);
            }
        }
        if (isset($config['token_ttl'])) {
            $this->tokenTtl = $config['token_ttl'];
        }
        if (isset($config['auth_method'])) {
            $this->authMethod = $config['auth_method'];
        }
        if (isset($config['unique_email'])) {
            $this->uniqueEmail = boolval($config['unique_email']);
        }
        if (isset($config['unique_tel'])) {
            $this->uniqueTel = boolval($config['unique_tel']);
        }
        $this->conn = $conn;
    }

    /**
     * @param array $user
     * @return IUser|null
     */
    public function signUp(array $user)
    {
        $userModel = $this->conn->getMapper('User');
        //check exists
        $wh = [IUser::FIELD_USER_NAME=>$user[IUser::FIELD_USER_NAME]];
        if ($this->uniqueEmail && isset($user[IUser::FIELD_EMAIL]) && $user[IUser::FIELD_EMAIL]) {
            $wh[IUser::FIELD_EMAIL] = $user[IUser::FIELD_EMAIL];
        }
        if ($this->uniqueTel && isset($user[IUser::FIELD_TEL]) && $user[IUser::FIELD_TEL]) {
            $wh[IUser::FIELD_TEL] = $user[IUser::FIELD_TEL];
        }
        $re = $userModel->has(['OR'=>$wh]);
        if ($re) {
            $this->msg = 'User ' . $user[IUser::FIELD_USER_NAME] . ' exists!';
            return null;
        }
        //check roles
        if (isset($user[IUser::FIELD_ROLES])) {
            $roles = $user[IUser::FIELD_ROLES];
            if (!is_array($roles)) {
                $roles = explode(',', $roles);
            }
            $r = [];
            foreach ($roles as $role) {
                array_push($r, ['role_name'=>$role]);
            }
            unset($user[IUser::FIELD_ROLES]);
        }
        //check password
        $pwd = isset($user[IUser::FIELD_PASSWORD]) ? $user[IUser::FIELD_PASSWORD] : null;
        if ($pwd) {
            $pwd = password_hash($pwd, PASSWORD_BCRYPT);
        }
        $user[IUser::FIELD_PASSWORD] = $pwd;
        //insert user
        $uid = $userModel->insert($user);
        if ($uid) {
            if (isset($r)) {
                $re = $userModel->changeRoles($uid, $r);
                if (!$re) {
                    $this->msg = 'Change roles failed!';
                }
            }
            $token = $this->generateToken($uid);
            $this->user = new NormalUser($uid, $userModel, $token);
            $this->msg = 'Sign up successfully!';
            if ($this->tokenTtl > 0) {
                $this->saveCache();
            }
            return $this->user;
        }
        $this->msg = 'Sign up failed!';
        return null;
    }

    /**
     * @param array $user
     * @return IUser|null
     */
    public function signIn(array $user)
    {
        if ($this->verify($user)) {
            $this->msg = 'Sign in successfully!';
            if ($this->tokenTtl > 0) {
                $this->saveCache();
            }
        }
        return $this->user;
    }

    /**
     * @param array $user
     * @return IUser|null
     */
    public function signOut(array $user)
    {
        $token = isset($user[self::KEY_TOKEN]) ? $user[self::KEY_TOKEN] : null;
        if ($token) {
            $this->cache->delete($token);
        }
        $this->msg = 'Sign out successfully!';
        return null;
    }

    /**
     * @param array $user
     * @return bool
     */
    public function verify(array $user)
    {
        $re = false;
        if (($this->authMethod & 4) == 4 && isset($user[self::KEY_TOKEN])) {
            //check access token only
            $re = $this->checkTokenOnly($user);
        }
        if (!$re && ($this->authMethod & 2) == 2 && isset($user[self::KEY_TOKEN]) && isset($user[self::KEY_USERNAME])) {
            //check access token and name
            $re = $this->checkTokenName($user);
        }
        if (!$re && ($this->authMethod & 32) == 32 && isset($user[self::KEY_TOKEN]) && isset($user[self::KEY_EMAIL])) {
            //check access token and email
            $re = $this->checkTokenEmail($user);
        }
        if (!$re && ($this->authMethod & 128) == 128 && isset($user[self::KEY_TOKEN]) && isset($user[self::KEY_TEL])) {
            //check access token and tel
            $re = $this->checkTokenTel($user);
        }
        if (!$re && ($this->authMethod & 1) == 1 && isset($user[self::KEY_USERNAME]) && isset($user[self::KEY_PASSWORD])) {
            //check name and password
            $re = $this->checkNamePassword($user);
        }
        if (!$re && ($this->authMethod & 16) == 16 && isset($user[self::KEY_EMAIL]) && isset($user[self::KEY_PASSWORD])) {
            //check email and password
            $re = $this->checkEmailPassword($user);
        }
        if (!$re && ($this->authMethod & 64) == 64 && isset($user[self::KEY_TEL]) && isset($user[self::KEY_PASSWORD])) {
            //check tel and password
            $re = $this->checkTelPassword($user);
        }
        if (!$re && ($this->authMethod & 8) == 8 && isset($user[self::KEY_USER_ID])) {
            //check user_id
            $re = $this->checkUserId($user);
        }
        return $re;
    }

    /**
     * @return \Desarrolla2\Cache\Cache
     */
    public function getCache()
    {
        return $this->cache;
    }

    /**
     * Save token in cache
     */
    public function saveCache()
    {
        if ($this->user) {
            $token = $this->user->getUserArray()[IUser::FIELD_TOKEN];
            //store token in cache
            $this->cache->set($token, json_encode($this->user->getUserArray(), JSON_UNESCAPED_UNICODE), $this->tokenTtl);
        }
    }

    /**
     * @return int
     */
    public function getTokenTtl()
    {
        return $this->tokenTtl;
    }

    /**
     * @param int $tokenTtl
     * @return NormalAuth
     */
    public function setTokenTtl($tokenTtl)
    {
        $this->tokenTtl = $tokenTtl;
        return $this;
    }

    /**
     * @return boolean
     */
    public function isUniqueEmail()
    {
        return $this->uniqueEmail;
    }

    /**
     * @param boolean $uniqueEmail
     * @return NormalAuth
     */
    public function setUniqueEmail($uniqueEmail)
    {
        $this->uniqueEmail = $uniqueEmail;
        return $this;
    }

    /**
     * @return boolean
     */
    public function isUniqueTel()
    {
        return $this->uniqueTel;
    }

    /**
     * @param boolean $uniqueTel
     * @return NormalAuth
     */
    public function setUniqueTel($uniqueTel)
    {
        $this->uniqueTel = $uniqueTel;
        return $this;
    }

    /**
     * @return int
     */
    public function getAuthMethod()
    {
        return $this->authMethod;
    }

    /**
     * @param int $authMethod
     * @return NormalAuth
     */
    public function setAuthMethod($authMethod)
    {
        $this->authMethod = $authMethod;
        return $this;
    }

    /**
     * @param $user
     * @return bool
     */
    protected function checkTokenOnly($user)
    {
        $token = $user[self::KEY_TOKEN];
        if ($this->cache->has($token)) {
            $u = $this->cache->get($token);
            if ($u) {
                $uobj = json_decode($u, true);
                if (isset($uobj[IUser::FIELD_USER_ID])) {
                    $uid = $uobj[IUser::FIELD_USER_ID];
                    $userModel = $this->conn->getMapper('User');
                    $this->user = new NormalUser($uid, $uobj, $token);
                    if ($userModel instanceof User) {
                        $this->user->setMapper($userModel);
                    }
                    $this->msg = 'User is valid!';
                    return true;
                }
            }
        }
        $this->msg = 'Verify user failed!';
        return false;
    }

    /**
     * @param $user
     * @return bool
     */
    protected function checkTokenName($user)
    {
        $token = $user[self::KEY_TOKEN];
        if ($this->cache->has($token)) {
            $u = $this->cache->get($token);
            if ($u) {
                $uobj = json_decode($u, true);
                $uname = $user[self::KEY_USERNAME];
                if (isset($uobj[IUser::FIELD_USER_ID]) && $uname == $uobj[IUser::FIELD_USER_NAME]) {
                    $uid = $uobj[IUser::FIELD_USER_ID];
                    $userModel = $this->conn->getMapper('User');
                    $this->user = new NormalUser($uid, $uobj, $token);
                    if ($userModel instanceof User) {
                        $this->user->setMapper($userModel);
                    }
                    $this->msg = 'User is valid!';
                    return true;
                }
            }
        }
        $this->msg = 'Verify user failed!';
        return false;
    }

    /**
     * @param $user
     * @return bool
     */
    protected function checkTokenEmail($user)
    {
        $token = $user[self::KEY_TOKEN];
        if ($this->cache->has($token)) {
            $u = $this->cache->get($token);
            if ($u) {
                $uobj = json_decode($u, true);
                $email = $user[self::KEY_EMAIL];
                if (isset($uobj[IUser::FIELD_USER_ID]) && $email == $uobj[IUser::FIELD_EMAIL]) {
                    $uid = $uobj[IUser::FIELD_USER_ID];
                    $userModel = $this->conn->getMapper('User');
                    $this->user = new NormalUser($uid, $uobj, $token);
                    if ($userModel instanceof User) {
                        $this->user->setMapper($userModel);
                    }
                    $this->msg = 'User is valid!';
                    return true;
                }
            }
        }
        $this->msg = 'Verify user failed!';
        return false;
    }

    /**
     * @param $user
     * @return bool
     */
    protected function checkTokenTel($user)
    {
        $token = $user[self::KEY_TOKEN];
        if ($this->cache->has($token)) {
            $u = $this->cache->get($token);
            if ($u) {
                $uobj = json_decode($u, true);
                $tel = $user[self::KEY_TEL];
                if (isset($uobj[IUser::FIELD_USER_ID]) && $tel == $uobj[IUser::FIELD_TEL]) {
                    $uid = $uobj[IUser::FIELD_USER_ID];
                    $userModel = $this->conn->getMapper('User');
                    $this->user = new NormalUser($uid, $uobj, $token);
                    if ($userModel instanceof User) {
                        $this->user->setMapper($userModel);
                    }
                    $this->msg = 'User is valid!';
                    return true;
                }
            }
        }
        $this->msg = 'Verify user failed!';
        return false;
    }

    /**
     * @param $user
     * @return bool
     */
    protected function checkNamePassword($user)
    {
        $userModel = $this->conn->getMapper('User');
        $u = $userModel->get(null, '*', ['AND'=>[IUser::FIELD_USER_NAME => $user[self::KEY_USERNAME], 'deleted_at'=>null]]);
        if ($u) {
            $pwd = $u[IUser::FIELD_PASSWORD];
            if (is_null($pwd) || password_verify($user[self::KEY_PASSWORD], $pwd)) {
                $uid = $u[IUser::FIELD_USER_ID];
                $token = $this->generateToken($uid);
                $this->user = new NormalUser($uid, $u, $token);
                if ($userModel instanceof User) {
                    $this->user->setMapper($userModel);
                }
                $this->msg = 'User is valid!';
                return true;
            }
        }
        $this->msg = 'Verify user failed!';
        return false;
    }

    /**
     * @param $user
     * @return bool
     */
    protected function checkEmailPassword($user)
    {
        $userModel = $this->conn->getMapper('User');
        $u = $userModel->get(null, '*', ['AND'=>[IUser::FIELD_EMAIL => $user[self::KEY_EMAIL], 'deleted_at'=>null]]);
        if ($u) {
            $pwd = $u[IUser::FIELD_PASSWORD];
            if (is_null($pwd) || password_verify($user[self::KEY_PASSWORD], $pwd)) {
                $uid = $u[IUser::FIELD_USER_ID];
                $token = $this->generateToken($uid);
                $this->user = new NormalUser($uid, $u, $token);
                if ($userModel instanceof User) {
                    $this->user->setMapper($userModel);
                }
                $this->msg = 'User is valid!';
                return true;
            }
        }
        $this->msg = 'Verify user failed!';
        return false;
    }

    /**
     * @param $user
     * @return bool
     */
    protected function checkTelPassword($user)
    {
        $userModel = $this->conn->getMapper('User');
        $u = $userModel->get(null, '*', ['AND'=>[IUser::FIELD_TEL => $user[self::KEY_TEL], 'deleted_at'=>null]]);
        if ($u) {
            $pwd = $u[IUser::FIELD_PASSWORD];
            if (is_null($pwd) || password_verify($user[self::KEY_PASSWORD], $pwd)) {
                $uid = $u[IUser::FIELD_USER_ID];
                $token = $this->generateToken($uid);
                $this->user = new NormalUser($uid, $u, $token);
                if ($userModel instanceof User) {
                    $this->user->setMapper($userModel);
                }
                $this->msg = 'User is valid!';
                return true;
            }
        }
        $this->msg = 'Verify user failed!';
        return false;
    }

    /**
     * @param $user
     * @return bool
     */
    protected function checkUserId($user)
    {
        $userModel = $this->conn->getMapper('User');
        $uid = $user[self::KEY_USER_ID];
        $u = $userModel->get(null, '*', ['AND'=>[IUser::FIELD_USER_ID => $uid, 'deleted_at'=>null]]);
        if ($u) {
            $token = $this->generateToken($uid);
            $this->user = new NormalUser($uid, $u, $token);
            if ($userModel instanceof User) {
                $this->user->setMapper($userModel);
            }
            $this->msg = 'User is valid!';
            return true;

        }
        $this->msg = 'Verify user failed!';
        return false;
    }

    /**
     * @param $uid
     * @return string
     */
    protected function generateToken($uid)
    {
        return substr(md5($uid . mt_rand(0, 1000) . time()), 3, 10);
    }

}