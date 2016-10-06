<?php
/**
 * Created by PhpStorm.
 * User: wwt
 * Date: 2016/10/6 0006
 * Time: 下午 4:12
 */

namespace Wwtg99\PgAuth\Utils;


use Desarrolla2\Cache\Adapter\Apcu;
use Desarrolla2\Cache\Adapter\File;
use Desarrolla2\Cache\Adapter\Memcache;
use Desarrolla2\Cache\Adapter\NotCache;
use Desarrolla2\Cache\Adapter\Predis;
use Desarrolla2\Cache\Cache as DCache;
use Predis\Client;

class Cache
{

    /**
     * @param $type
     * @param $options
     * @return DCache
     */
    public static function getCache($type, $options = null)
    {
        switch (strtolower($type)) {
            case 'apcu': $cache = self::initApcu($options); break;
            case 'file': $cache = self::initFile($options); break;
            case 'redis': $cache = self::initRedis($options); break;
            case 'memcache': $cache = self::initMemcache($options); break;
            default: $cache = self::initNoCache();
        }
        return $cache;
    }

    /**
     * @param $options
     * @return DCache
     */
    private static function initApcu($options = null)
    {
        $adapter = new Apcu();
        if (isset($options['ttl'])) {
            $adapter->setOption('ttl', $options['ttl']);
        }
        return new DCache($adapter);
    }

    /**
     * @param $options
     * @return DCache
     */
    private static function initFile($options = null)
    {
        $cdir = null;
        if (isset($options['dir'])) {
            $cdir = $options['dir'];
        }
        $adapter = new File($cdir);
        if (isset($options['ttl'])) {
            $adapter->setOption('ttl', $options['ttl']);
        }
        return new DCache($adapter);
    }

    /**
     * @param $options
     * @return DCache
     */
    private static function initRedis($options = null)
    {
        $adapter = new Predis(new Client($options));
        return new DCache($adapter);
    }

    /**
     * @param $options
     * @return DCache
     */
    private static function initMemcache($options = null)
    {
        $adapter = new Memcache();
        return new DCache($adapter);
    }

    /**
     * @return DCache
     */
    private static function initNoCache()
    {
        return new DCache(new NotCache());
    }
}