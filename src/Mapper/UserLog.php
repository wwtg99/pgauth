<?php
/**
 * Created by PhpStorm.
 * User: wuwentao
 * Date: 2016/9/20
 * Time: 11:02
 */

namespace Wwtg99\PgAuth\Mapper;


use Wwtg99\DataPool\Mappers\ArrayPgInsertMapper;

class UserLog extends ArrayPgInsertMapper
{

    protected $key = 'id';

    protected $name = 'user_log';

    /**
     * @param $data
     * @param $where
     * @param $key
     * @return mixed
     */
    public function update($data, $where = null, $key = null)
    {
        return false;
    }

    /**
     * @param $key
     * @param $where
     * @return mixed
     */
    public function delete($key, $where = null)
    {
        return false;
    }


}