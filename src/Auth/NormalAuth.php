<?php
/**
 * Created by PhpStorm.
 * User: wwt
 * Date: 2016/10/6 0006
 * Time: 下午 4:02
 */

namespace Wwtg99\PgAuth\Auth;


use Wwtg99\Config\Common\IConfig;
use Wwtg99\DataPool\Common\DefaultDataPool;
use Wwtg99\PgAuth\Utils\Cache;

class NormalAuth implements IAuth
{

    /**
     * @var IConfig
     */
    protected $config;

    /**
     * @var \Desarrolla2\Cache\Cache
     */
    protected $cache = null;

    /**
     * @var \Wwtg99\DataPool\Common\IDataConnection
     */
    protected $conn = null;

    /**
     * @var string
     */
    protected $msg = '';

    /**
     * NormalAuth constructor.
     * @param IConfig $config
     */
    public function __construct($config = null)
    {
        $this->config = $config;
        if ($this->config->has('cache')) {
            $ch = $this->config->get('cache');
            if (isset($ch['type']) && isset($ch['options']))
            $this->cache = Cache::getCache($ch['type'], $ch['options']);
        }
        if ($this->config->has('connection')) {
            $cpool = new DefaultDataPool($this->config->get('connection'));
            $conn = $this->config->get('conn_name', 'main');
            $this->conn = $cpool->getConnection($conn);
        }
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
            $token = $u->getUser()[IAuth::KEY_USER_TOKEN];
            $ttl = $this->config->get('token_ttl');
            $this->cache->set($token, json_encode($u->getUser()), $ttl);
        }
        return $u;
    }

    /**
     * @param array $user
     * @return IUser|null
     */
    public function signOut(array $user)
    {
        $token = isset($user[IAuth::KEY_USER_TOKEN]) ? $user[IAuth::KEY_USER_TOKEN] : null;
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
        if (isset($user[self::KEY_USER_TOKEN])) {
            //check access token
            $token = $user[self::KEY_USER_TOKEN];
            echo "=====$token\n";
            if ($this->cache->has($token)) {
                $u = $this->cache->get($token);
                echo "=====$u\n";
                if ($u) {
                    $user = json_decode($u, true);
                    if (isset($user[IUser::FIELD_USER_ID])) {
                        $this->msg = 'User is valid!';
                        return new NormalUser($user, $this->conn);
                    }
                }
            }
        } elseif (isset($user[IAuth::KEY_USER_NAME]) && isset($user[IAuth::KEY_USER_PASSWORD])) {
            //check name and password
            $userModel = $this->conn->getMapper('User');
            $u = $userModel->view('*', [IUser::FIELD_USER_NAME => $user[IAuth::KEY_USER_NAME]]);
            if ($u && isset($u[0])) {
                $u = $u[0];
                $pwd = $u[IUser::FIELD_PASSWORD];
                if (is_null($pwd) || password_verify($user[IAuth::KEY_USER_PASSWORD], $pwd)) {
                    $token = substr(md5($u[IUser::FIELD_USER_ID] . mt_rand(0, 1000) . time()), 3, 10);
                    unset($u[IUser::FIELD_PASSWORD]);
                    $u[IAuth::KEY_USER_TOKEN] = $token;
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

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->msg;
    }


}