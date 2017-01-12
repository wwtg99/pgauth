<?php
/**
 * Created by PhpStorm.
 * User: wwt
 * Date: 2016/10/6 0006
 * Time: 下午 4:02
 */

namespace Wwtg99\PgAuth\Auth;


use Wwtg99\DataPool\Common\IDataConnection;
use Wwtg99\PgAuth\Utils\Cache;

/**
 * Class NormalAuth
 * Normal auth
 * Verify by username and password or access_token and username.
 * @package Wwtg99\PgAuth\Auth
 */
class NormalAuth extends AbstractAuth
{

    /**
     * @var \Desarrolla2\Cache\Cache
     */
    protected $cache = null;

    /**
     * @var \Wwtg99\DataPool\Common\IDataConnection
     */
    protected $conn = null;

    /**
     * @var int
     */
    public $tokenTtl = 3600;

    /**
     * Check access_token without name.
     * @var bool
     */
    public $tokenOnly = false;

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
            if (isset($ch['type']) && isset($ch['options']))
            $this->cache = Cache::getCache($ch['type'], $ch['options']);
        }
        if (isset($config['token_ttl'])) {
            $this->tokenTtl = $config['token_ttl'];
        }
        if (isset($config['token_only'])) {
            $this->tokenOnly = boolval($config['token_only']);
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
        $re = $userModel->has([IUser::FIELD_USER_NAME=>$user[IUser::FIELD_USER_NAME]]);
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
            $this->saveCache();
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
            $this->saveCache();
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
        if ($this->tokenOnly && isset($user[self::KEY_TOKEN])) {
            //check access token only
            $re = $this->checkTokenOnly($user);
        } else if (isset($user[self::KEY_TOKEN]) && isset($user[self::KEY_USERNAME])) {
            //check access token and name
            $re = $this->checkTokenName($user);
        } elseif (isset($user[self::KEY_USERNAME]) && isset($user[self::KEY_PASSWORD])) {
            //check name and password
            $re = $this->checkNamePassword($user);
        } else {
            $this->msg = 'Verify user failed!';
            $re = false;
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
            $token = $this->user->getUser()[IUser::FIELD_TOKEN];
            //store token in cache
            $this->cache->set($token, json_encode($this->user->getUser(), JSON_UNESCAPED_UNICODE), $this->tokenTtl);
        }
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
                    $token = $this->generateToken($uid);
                    $this->user = new NormalUser($uid, $userModel, $token);
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
                    $token = $this->generateToken($uid);
                    $this->user = new NormalUser($uid, $userModel, $token);
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
                $this->user = new NormalUser($uid, $userModel, $token);
                $this->msg = 'User is valid!';
                return true;
            }
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