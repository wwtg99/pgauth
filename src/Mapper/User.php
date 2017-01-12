<?php
/**
 * Created by PhpStorm.
 * User: wuwentao
 * Date: 2016/9/19
 * Time: 16:36
 */

namespace Wwtg99\PgAuth\Mapper;


use Wwtg99\DataPool\Mappers\ArrayPgInsertMapper;

class User extends ArrayPgInsertMapper
{

    protected $key = 'user_id';

    protected $name = 'users';

    protected $userRole = 'user_role';

    protected $viewName = 'view_users';

    /**
     * @param $user_id
     * @param $role_id
     * @return bool
     */
    public function addRole($user_id, $role_id)
    {
        $re = $this->connection->getEngine()->query(["select add_user_role('$user_id', $role_id)", [], true]);
        if ($re) {
            return $re['add_user_role'];
        }
        return false;
    }

    /**
     * @param $user_id
     * @param $role_id
     * @return bool
     */
    public function removeRole($user_id, $role_id)
    {
        $re = $this->connection->getEngine()->query(["select delete_user_role('$user_id', $role_id)", [], true]);
        if ($re) {
            return $re['delete_user_role'];
        }
        return false;
    }

    /**
     * @param $user_id
     * @param $roles
     * @return bool
     */
    public function changeRoles($user_id, $roles)
    {
        if (is_array($roles)) {
            $roles = json_encode($roles);
        }
        $re = $this->connection->getEngine()->query(["select change_roles('$user_id', '$roles')", [], true]);
        if ($re) {
            return $re['change_roles'];
        }
        return false;
    }

    /**
     * @param $user_id
     * @param bool $active
     * @return bool
     */
    public function activeUser($user_id, $active = true)
    {
        $b = $active ? 'true' : 'false';
        $re = $this->connection->getEngine()->query(["select active_user('$user_id', $b)", [], true]);
        if ($re) {
            return $re['active_user'];
        }
        return false;
    }

    /**
     * @param string $select
     * @param array $where
     * @return array
     */
    public function view($select = '*', $where = [])
    {
        $tmp = $this->name;
        $this->name = $this->viewName;
        $re = $this->select($select, $where);
        $this->name = $tmp;
        return $re;
    }
}