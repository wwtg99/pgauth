<?php
/**
 * Created by PhpStorm.
 * User: wuwentao
 * Date: 2016/9/19
 * Time: 16:37
 */

namespace Wwtg99\PgAuth\Mapper;


use Wwtg99\DataPool\Mappers\ArrayPgInsertMapper;

class Role extends ArrayPgInsertMapper
{

    protected $key = 'role_id';

    protected $name = 'roles';
}