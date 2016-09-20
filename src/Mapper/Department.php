<?php
/**
 * Created by PhpStorm.
 * User: wuwentao
 * Date: 2016/9/19
 * Time: 16:38
 */

namespace Wwtg99\PgAuth\Mapper;


use Wwtg99\DataPool\Mappers\ArrayMapper;

class Department extends ArrayMapper
{

    protected $key = 'department_id';

    protected $name = 'departments';
}