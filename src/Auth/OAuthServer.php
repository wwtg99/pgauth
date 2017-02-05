<?php
/**
 * Created by PhpStorm.
 * User: wwt
 * Date: 2016/10/6 0006
 * Time: 下午 9:21
 */

namespace Wwtg99\PgAuth\Auth;


use Wwtg99\DataPool\Common\IDataConnection;
use Wwtg99\PgAuth\Mapper\App;

/**
 * Class OAuthServer
 * OAuth server
 *
 * First, get code by username, password, app_id, redirect_uri
 * Second, get access_token by code and app_secret
 * Then, verify or get user info by access_token, app_id
 * @package Wwtg99\PgAuth\Auth
 */
class OAuthServer extends NormalAuth
{

    protected $keyAppId = 'app_id';
    protected $keyAppRedirectUri = 'redirect_uri';
    protected $keyAppSecret = 'app_secret';
    protected $keyCode = 'code';

    public $codePrefix = 'oauth_code_';

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
     * @param $uid
     * @return string
     */
    public function generateCode($uid)
    {
        return substr(md5($uid . mt_rand(0, 1000) . time()), 2, 16);
    }

    /**
     * @param $code
     * @param $user
     * @param $app_id
     * @param $redirect_url
     */
    public function saveCode($code, $user, $app_id, $redirect_url)
    {
        $user[$this->keyAppId] = $app_id;
        $user[$this->keyAppRedirectUri] = $redirect_url;
        $this->cache->set($this->codePrefix . $code, json_encode($user, JSON_UNESCAPED_UNICODE), $this->codeTtl);
    }

    /**
     * @param $code
     * @param $secret
     * @return bool
     */
    public function verifyCode($code, $secret)
    {
        $c = $this->cache->get($this->codePrefix . $code);
        if ($c) {
            $this->cache->delete($this->codePrefix . $code);
            $user = json_decode($c, true);
            $appid = $user[$this->keyAppId];
            $reduri = $user[$this->keyAppRedirectUri];
            $re = $this->getAppMapper()->verifySecret($appid, $secret, $reduri);
            if ($re) {
                $uid = $user[IUser::FIELD_USER_ID];
                $userModel = $this->conn->getMapper('User');
                $token = $this->generateToken($uid);
                $this->user = new NormalUser($uid, $userModel, $token);
                $this->msg = 'Code is valid!';
                return true;
            }
        }
        return false;
    }

    /**
     * @param $app_id
     * @param $redirect_url
     * @param $user
     * @return null|string
     */
    public function getCode($app_id, $redirect_url, $user)
    {
        //check app
        $appmodel = $this->getAppMapper();
        $app = $appmodel->getApp($app_id, $redirect_url);
        if ($app && $this->verify($user)) {
            $code = $this->generateCode($this->user->getId());
            $this->saveCode($code, $this->user->getUser(), $app_id, $redirect_url);
            return $code;
        }
        return null;
    }

    /**
     * @param array $user
     * @return IUser|null
     */
    public function signIn(array $user)
    {
        $code = isset($user[$this->keyCode]) ? $user[$this->keyCode] : '';
        $secret = isset($user[$this->keyAppSecret]) ? $user[$this->keyAppSecret] : '';
        if ($code && $secret) {
            if ($this->verifyCode($code, $secret)) {
                $this->msg = 'Sign in successfully!';
                if ($this->tokenTtl) {
                    $this->saveCache();
                }
            }
        } else {
            return parent::signIn($user);
        }
        return $this->user;
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
     * @return App
     */
    protected function getAppMapper()
    {
        return $this->conn->getMapper('App');
    }

}