<?php
/**
 * Created by PhpStorm.
 * User: wuwentao
 * Date: 2016/9/19
 * Time: 16:26
 */

namespace Wwtg99\PgAuth\Mapper;


use Wwtg99\DataPool\Mappers\ArrayPgInsertMapper;

class App extends ArrayPgInsertMapper
{

    protected $name = 'apps';

    protected $key = 'app_id';

    /**
     * @param $app_id
     * @param $redirect_uri
     * @return array
     */
    public function getApp($app_id, $redirect_uri)
    {
        $app = $this->get($app_id, ['app_id', 'app_name', 'descr', 'redirect_uri', 'created_at', 'updated_at']);
        if ($app) {
            if ($app['redirect_uri']) {
                $host = parse_url($redirect_uri, PHP_URL_HOST);
                if ($app['redirect_uri'] == $host || $app['redirect_uri'] == $redirect_uri) {
                    return $app;
                }
            } else {
                return $app;
            }
        }
        return [];
    }

    /**
     * @param $app_id
     * @param $app_secret
     * @param $redirect_uri
     * @return bool
     */
    public function verifySecret($app_id, $app_secret, $redirect_uri)
    {
        $app = $this->get($app_id, ['app_id', 'app_secret', 'redirect_uri']);
        if ($app) {
            if ($app['app_secret'] != $app_secret) {
                return false;
            }
            if ($app['redirect_uri']) {
                $host = parse_url($redirect_uri, PHP_URL_HOST);
                if ($app['redirect_uri'] != $host && $app['redirect_uri'] != $redirect_uri) {
                    return false;
                }
            }
            return true;
        }
        return false;
    }
}