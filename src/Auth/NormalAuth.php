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
    protected $tokenTtl = 3600;

    /**
     * @var string
     */
    protected $msg = '';

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
        if (isset($config['key_user_name'])) {
            $this->keyUserName = $config['key_user_name'];
        }
        if (isset($config['key_password'])) {
            $this->keyPassword = $config['key_password'];
        }
        if (isset($config['key_access_token'])) {
            $this->keyAccessToken = $config['key_access_token'];
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
            $u = $userModel->view('*', [IUser::FIELD_USER_ID => $uid]);
            $this->msg = 'Sign up successfully!';
            return new NormalUser($u, $this->conn);
        } else {
            $this->msg = 'Sign up failed!';
            return null;
        }
    }

    /**
     * @param array $user
     * @return IUser|null
     */
    public function signIn(array $user)
    {
        $u = $this->verify($user);
        if ($u) {
            $this->msg = 'Sign in successfully!';
            //store token in cache
            $token = $u->getUser()[$this->keyAccessToken];
            $this->cache->set($token, json_encode($u->getUser(), JSON_UNESCAPED_UNICODE), $this->tokenTtl);
        }
        return $u;
    }

    /**
     * @param array $user
     * @return IUser|null
     */
    public function signOut(array $user)
    {
        $token = isset($user[$this->keyAccessToken]) ? $user[$this->keyAccessToken] : null;
        if ($token) {
            $this->cache->delete($token);
        }
        $this->msg = 'Sign out successfully!';
        return null;
    }

    /**
     * @param array $user
     * @return IUser|null
     */
    public function verify(array $user)
    {
        if (isset($user[$this->keyAccessToken]) && isset($user[$this->keyUserName])) {
            //check access token
            $token = $user[$this->keyAccessToken];
            if ($this->cache->has($token)) {
                $u = $this->cache->get($token);
                if ($u) {
                    $uobj = json_decode($u, true);
                    $uname = $user[$this->keyUserName];
                    if (isset($uobj[IUser::FIELD_USER_ID]) && $uname == $uobj[IUser::FIELD_USER_NAME]) {
                        $this->msg = 'User is valid!';
                        return new NormalUser($uobj, $this->conn);
                    }
                }
            }
        } elseif (isset($user[$this->keyUserName]) && isset($user[$this->keyPassword])) {
            //check name and password
            $userModel = $this->conn->getMapper('User');
            $u = $userModel->view('*', ['AND'=>[IUser::FIELD_USER_NAME => $user[$this->keyUserName], 'deleted_at'=>null]]);
            if ($u && isset($u[0])) {
                $u = $u[0];
                $pwd = $u[IUser::FIELD_PASSWORD];
                if (is_null($pwd) || password_verify($user[$this->keyPassword], $pwd)) {
                    //generate access token
                    $token = substr(md5($u[IUser::FIELD_USER_ID] . mt_rand(0, 1000) . time()), 3, 10);
                    unset($u[IUser::FIELD_PASSWORD]);
                    $u[$this->keyAccessToken] = $token;
                    //check roles
                    if (isset($u[IUser::FIELD_ROLES])) {
                        $roles = $u[IUser::FIELD_ROLES];
                        if (!is_array($roles)) {
                            $roles = explode(',', $roles);
                        }
                        if (!in_array('common_user', $roles)) {
                            array_push($roles, 'common_user');
                        }
                        $u[IUser::FIELD_ROLES] = $roles;
                    } else {
                        $u[IUser::FIELD_ROLES] = ['common_user'];
                    }
                    $this->msg = 'User is valid!';
                    return new NormalUser($u, $this->conn);
                }
            }
        }
        $this->msg = 'Verify user failed!';
        return null;
    }

}