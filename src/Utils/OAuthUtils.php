<?php
/**
 * Created by PhpStorm.
 * User: wwt
 * Date: 2017/2/20
 * Time: 21:52
 */

namespace Wwtg99\PgAuth\Utils;



use Wwtg99\PgAuth\Auth\IUser;

class OAuthUtils
{

    /**
     * @var \Desarrolla2\Cache\Cache
     */
    protected $cache = null;

    /**
     * @var int
     */
    protected $codeTtl = 300;

    /**
     * @var string
     */
    protected $codePrefix = 'oauth_code_';

    protected $keyAppId = 'app_id';
    protected $keyAppRedirectUri = 'redirect_uri';
    protected $keyAppSecret = 'app_secret';
    protected $keyCode = 'code';

    /**
     * OAuthUtils constructor.
     * @param $config
     */
    public function __construct($config)
    {
        if (isset($config['cache'])) {
            $ch = $config['cache'];
            if (isset($ch['type']) && isset($ch['options'])) {
                $this->cache = Cache::getCache($ch['type'], $ch['options']);
            }
        }
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
     * @param $user
     * @param $app_id
     * @param $redirect_url
     * @return string
     */
    public function generateCode($user, $app_id, $redirect_url)
    {
        $uid = $user[IUser::FIELD_USER_ID];
        $code = substr(md5($uid . $app_id . mt_rand(0, 1000) . time()), 2, 10);
        $this->saveCode($code, $user, $app_id, $redirect_url);
        return $code;
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
     * @return bool|array
     */
    public function verifyCode($code)
    {
        $c = $this->cache->get($this->codePrefix . $code);
        if ($c) {
            $this->cache->delete($this->codePrefix . $code);
            $user = json_decode($c, true);
            return $user;
        }
        return false;
    }

}