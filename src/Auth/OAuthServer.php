<?php
/**
 * Created by PhpStorm.
 * User: wwt
 * Date: 2016/10/6 0006
 * Time: 下午 9:21
 */

namespace Wwtg99\PgAuth\Auth;


use Wwtg99\DataPool\Common\IDataConnection;

/**
 * Class OAuthServer
 * OAuth server
 *
 * First, get code by username, password, app_id, redirect_uri
 * Second, get access_token by code and app_secret
 * Then, verify or get user info by access_token, username, app_id
 * @package Wwtg99\PgAuth\Auth
 */
class OAuthServer extends NormalAuth
{

    protected $keyAppId = 'app_id';
    protected $keyAppRedirectUri = 'redirect_uri';
    protected $keyAppSecret = 'app_secret';
    protected $keyCode = 'code';

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
        if (isset($config['key_app_id'])) {
            $this->keyAppId = $config['key_app_id'];
        }
        if (isset($config['key_app_redirect_uri'])) {
            $this->keyAppRedirectUri = $config['key_app_redirect_uri'];
        }
        if (isset($config['key_app_secret'])) {
            $this->keyAppSecret = $config['key_app_secret'];
        }
        if (isset($config['key_code'])) {
            $this->keyCode = $config['key_code'];
        }
    }

    /**
     * @param array $user
     * @return IUser|null
     */
    public function verify(array $user)
    {
        if (isset($user[$this->keyAccessToken]) && isset($user[$this->keyUserName]) && isset($user[$this->keyAppId])) {
            //check access token
            $token = $user[$this->keyAccessToken];
            if ($this->cache->has($token)) {
                $u = $this->cache->get($token);
                if ($u) {
                    $uobj = json_decode($u, true);
                    $uname = $user[$this->keyUserName];
                    $appid = $user[$this->keyAppId];
                    if (isset($uobj[IUser::FIELD_USER_ID]) && $uname == $uobj[IUser::FIELD_USER_NAME] && $appid == $uobj[$this->keyAppId]) {
                        $this->msg = 'User is valid!';
                        return new OAuthUser($uobj);
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
                    $this->msg = 'User is valid!';
                    return new OAuthUser($u);
                }
            }
        }
        $this->msg = 'Verify user failed!';
        return null;
    }

    /**
     * @param array $user
     * @return IUser|null
     */
    public function signIn(array $user)
    {
        $code = isset($user[$this->keyCode]) ? $user[$this->keyCode] : '';
        $sec = isset($user[$this->keyAppSecret]) ? $user[$this->keyAppSecret] : '';
        if ($code && $sec) {
            $u = $this->checkCode($code, $sec);
            if ($u) {
                $this->msg = 'Sign in successfully!';
                //store token in cache
                $token = $u[$this->keyAccessToken];
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
     * @return IUser|null
     */
    public function signUp(array $user)
    {
        return null;
    }

    /**
     * @param array $user
     * @return null|string
     */
    public function getCode(array $user)
    {
        if (isset($user[$this->keyUserName]) && isset($user[$this->keyPassword])) {
            $appid = isset($user[$this->keyAppId]) ? $user[$this->keyAppId] : '';
            $reduri = isset($user[$this->keyAppRedirectUri]) ? $user[$this->keyAppRedirectUri] : '';
            $app = $this->getAppMapper()->getApp($appid, $reduri);
            if ($app) {
                $u = $this->verify($user);
                if ($u) {
                    $uarr = $u->getUser();
                    $uarr[$this->keyAppId] = $appid;
                    $uarr[$this->keyAppRedirectUri] = $reduri;
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
            $this->cache->delete('code_' . $code);
            $user = json_decode($c, true);
            $appid = $user[$this->keyAppId];
            $reduri = $user[$this->keyAppRedirectUri];
            $re = $this->getAppMapper()->verifySecret($appid, $secret, $reduri);
            if ($re) {
                unset($user[$this->keyAppRedirectUri]);
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