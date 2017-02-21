<?php
/**
 * Created by PhpStorm.
 * User: wwt
 * Date: 2017/2/20
 * Time: 21:46
 */

namespace Wwtg99\PgAuth\Utils;


use Wwtg99\DataPool\Common\IDataConnection;
use Wwtg99\PgAuth\Mapper\App;

class AppUtils
{

    const FIELD_APP_ID = 'app_id';
    const FIELD_APP_SECRET = 'app_secret';
    const FIELD_REDIRECT_URI = 'redirect_uri';

    /**
     * @var IDataConnection
     */
    protected $conn;

    /**
     * AppUtils constructor.
     * @param IDataConnection $conn
     */
    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    /**
     * @param $app_id
     * @param $redirect_uri
     * @return array|bool
     */
    public function verifyAppIdUri($app_id, $redirect_uri)
    {
        $model = $this->getMapper();
        $app = $model->get($app_id, '*');
        if ($app) {
            $host = parse_url($redirect_uri, PHP_URL_HOST);
            if ($app[self::FIELD_REDIRECT_URI] == $host || $app[self::FIELD_REDIRECT_URI] == $redirect_uri) {
                return $app;
            }
        }
        return false;
    }

    /**
     * @param $app_id
     * @param $app_secret
     * @return bool
     */
    public function verifySecret($app_id, $app_secret)
    {
        $model = $this->getMapper();
        $app = $model->get($app_id, [self::FIELD_APP_ID, self::FIELD_APP_SECRET]);
        if ($app) {
            if ($app[self::FIELD_APP_SECRET] == $app_secret) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return App
     */
    protected function getMapper()
    {
        return $this->conn->getMapper('App');
    }
}