<?php

/**
 * Created by PhpStorm.
 * User: wuwentao
 * Date: 2016/9/19
 * Time: 16:45
 */
class AuthTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var \Wwtg99\DataPool\Common\IDataPool
     */
    private $pool;

    public static function setUpBeforeClass()
    {
        date_default_timezone_set("Asia/Shanghai");
        require_once '../vendor/autoload.php';
    }

    public function setUp()
    {
        $this->pool = new \Wwtg99\DataPool\Common\DefaultDataPool('../conf.json');
    }

    public function testDepartment()
    {
        $dep = $this->pool->getConnection('main')->getMapper('Department');
        $data1 = ['department_id'=>'d1', 'name'=>'dep1', 'descr'=>'dddd'];
        $re = $dep->insert($data1);
        self::assertTrue($re != false);
        $data1_update = ['name'=>'dep11', 'descr'=>'ffff'];
        $re = $dep->update($data1_update, null, 'd1');
        self::assertTrue($re != false);
        $re = $dep->select();
        self::assertTrue(count($re) > 0);
        $re = $dep->get('d1');
        self::assertEquals('dep11', $re['name']);
        $data2 = ['department_id'=>'d2', 'name'=>'dep2'];
        $re = $dep->insert($data2);
        self::assertTrue($re != false);
        $re = $dep->delete('d2');
        self::assertTrue($re != false);
    }

    public function testRole()
    {
        $role = $this->pool->getConnection('main')->getMapper('Role');
        $data1 = ['name'=>'r1', 'descr'=>'rrr'];
        $rid = $role->insert($data1);
        self::assertTrue($rid != false);
        $data1_update = ['name'=>'role1', 'descr'=>'ggg'];
        $re = $role->update($data1_update, null, $rid);
        self::assertTrue($re != false);
        $re = $role->select();
        self::assertTrue(count($re) > 0);
        $re = $role->get($rid);
        self::assertEquals('role1', $re['name']);
        $data2 = ['name'=>'r2', 'descr'=>'rrr'];
        $rid2 = $role->insert($data2);
        self::assertTrue($rid2 != false);
        $re = $role->delete($rid2);
        self::assertTrue($re != false);
    }

    public function testUser()
    {
        $user = $this->pool->getConnection('main')->getMapper('User');
        $data1 = ['name'=>'u1', 'label'=>'user1', 'email'=>'u@u.com', 'descr'=>'rrr', 'department_id'=>'d1', 'params'=>'{"a":"b"}'];
        $uid = $user->insert($data1);
        self::assertTrue($uid != false);
        $data1_update = ['name'=>'user1', 'descr'=>'ggg'];
        $re = $user->update($data1_update, null, $uid);
        self::assertTrue($re != false);
        $re = $user->select();
        self::assertTrue(count($re) > 0);
        $re = $user->get($uid);
        self::assertEquals('user1', $re['name']);
        $data2 = ['name'=>'u2', 'descr'=>'rrr'];
        $uid2 = $user->insert($data2);
        self::assertTrue($uid2 != false);
        $re = $user->delete($uid2);
        $re = $user->get($uid2);
        self::assertEmpty($re);
        //role
        $re = $user->addRole($uid, 1);
        self::assertTrue($re != false);
        $re = $user->view('*', ['user_id'=>$uid]);
        self::assertTrue($re != false);
        self::assertEquals('role1', $re[0]['roles']);
        $re = $user->removeRole($uid, 1);
        self::assertTrue($re != false);
        $re = $user->view('*', ['user_id'=>$uid]);
        self::assertTrue($re != false);
        self::assertEmpty($re[0]['roles']);
        $re = $user->changeRoles($uid, [['role_name'=>'role1']]);
        self::assertTrue($re != false);
        $re = $user->view('*', ['user_id'=>$uid]);
        self::assertTrue($re != false);
        self::assertEquals('role1', $re[0]['roles']);
        //active
        $re = $user->activeUser($uid, false);
        self::assertTrue($re);
        $re = $user->activeUser($uid, true);
        self::assertTrue($re);
    }

    public function testUserLog()
    {
        $user = $this->pool->getConnection('main')->getMapper('UserLog');
        $data1 = ['user_id'=>'U0000000001', 'log_event'=>'create', 'descr'=>'aaa', 'params'=>'{"a":"b"}', 'created_by'=>'U0000000001'];
        $id = $user->insert($data1);
        self::assertTrue($id != false);
        $re = $user->select();
        self::assertTrue(count($re) > 0);
        $re = $user->get($id);
        self::assertEquals('create', $re['log_event']);
    }

    public function testApp()
    {
        $app = $this->pool->getConnection('main')->getMapper('App');
        $data1 = ['app_name'=>'app1', 'descr'=>'rrr', 'redirect_uri'=>'localhost'];
        $aid = $app->insert($data1);
        self::assertTrue($aid != false);
        $data1_update = ['descr'=>'ggg'];
        $re = $app->update($data1_update, null, $aid);
        self::assertTrue($re != false);
        $re = $app->select();
        self::assertTrue(count($re) > 0);
        $re = $app->get($aid);
        self::assertEquals('app1', $re['app_name']);
        $data2 = ['app_name'=>'app2', 'descr'=>'rrr'];
        $aid2 = $app->insert($data2);
        self::assertTrue($aid2 != false);
        //delete
        $re = $app->delete($aid2);
        self::assertTrue($re != false);
    }
}
