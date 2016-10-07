<?php
/**
 * Created by PhpStorm.
 * User: wwt
 * Date: 2016/10/6 0006
 * Time: 下午 9:21
 */

namespace Wwtg99\PgAuth\Auth;


use Wwtg99\DataPool\Common\IDataConnection;

class OAuthServer extends NormalAuth
{

    const FIELD_APP_ID = 'app_id';
    const FIELD_APP_REDIRECT_URI = 'redirect_uri';
    const FIELD_APP_SECRET = 'app_secret';

    /**
     * @var int
     */
    protected $codeTtl = 300;

    /**
     * OAuthServer constructor.
     *
     * @param IDataConnection $conn
     * @param array $config
     */
    public function __construct($conn = null, $config = null)
    {
        parent::__construct($conn, $config);
        if (isset($config['code_ttl'])) {
            $this->codeTtl = $config['code_ttl'];
        }
    }

    /**
     * @param array $user
     * @return IUser|null
     */
    public function signIn(array $user)
    {
        $code = isset($user['code']) ? $user['code'] : '';
        $sec = isset($user['secret']) ? $user['secret'] : '';
        if ($code && $sec) {
            $u = $this->checkCode($code, $sec);
            if ($u) {
                $this->msg = 'Sign in successfully!';
                //store token in cache
                $token = $u[IAuth::KEY_USER_TOKEN];
                $this->cache->set($token, json_encode($u, JSON_UNESCAPED_UNICODE), $this->tokenTtl);
                return new OAuthUser($u);
            } else {
                $this->msg = 'Invalid code!';
            }
        } else {
            $this->msg = 'No code or app secret!';
        }
        return null;
    }

    /**
     * @param array $user
     * @return null|string
     */
    public function getCode(array $user)
    {
        if (isset($user[self::KEY_USER_NAME]) && isset($user[self::KEY_USER_PASSWORD])) {
            $appid = isset($user[self::FIELD_APP_ID]) ? $user[self::FIELD_APP_ID] : '';
            $reduri = isset($user[self::FIELD_APP_REDIRECT_URI]) ? $user[self::FIELD_APP_REDIRECT_URI] : '';
            $app = $this->getAppMapper()->getApp($appid, $reduri);
            if ($app) {
                $u = $this->verify($user);
                if ($u) {
                    $uarr = $u->getUser();
                    $uarr[self::FIELD_APP_ID] = $appid;
                    $uarr[self::FIELD_APP_REDIRECT_URI] = $reduri;
                    $code = substr(md5($u->getUser()[IUser::FIELD_USER_ID] . mt_rand(0, 1000) . time()), 2, 16);
                    $this->cache->set('code_' . $code, json_encode($uarr, JSON_UNESCAPED_UNICODE), $this->codeTtl);
                    $this->msg = 'Get code successfully!';
                    return $code;
                }
            } else {
                $this->msg = 'Invalid app!';
            }
        } else {
            $this->msg = 'Invalid username or password!';
        }
        return null;
    }

    /**
     * @param string $code
     * @param string $secret
     * @return array|null
     */
    protected function checkCode($code, $secret)
    {
        $c = $this->cache->get('code_' . $code);
        if ($c) {
            $user = json_decode($c, true);
            $appid = $user[self::FIELD_APP_ID];
            $reduri = $user[self::FIELD_APP_REDIRECT_URI];
            $re = $this->getAppMapper()->verifySecret($appid, $secret, $reduri);
            if ($re) {
                unset($user[self::FIELD_APP_ID]);
                unset($user[self::FIELD_APP_REDIRECT_URI]);
                return $user;
            }
        }
        return null;
    }

    /**
     * @return \Wwtg99\DataPool\Common\IDataMapper
     */
    protected function getAppMapper()
    {
        return $this->conn->getMapper('App');
    }

}