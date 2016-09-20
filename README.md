# PgAuth

Auth library depends on Postgresql.

Support:
- User management
- Role management
- Department management
- App management

### Usage
- Initialize database
`Run \i install.sql in psql`
- Manage department (One user belongs to one department or not)
```
// create department
$dep = $this->pool->getConnection('main')->getMapper('Department');
$data = ['department_id'=>'d1', 'name'=>'dep1', 'descr'=>'dddd'];
$re = $dep->insert($data);
// update department
$data_update = ['name'=>'dep11', 'descr'=>'ffff'];
$re = $dep->update($data_update, null, 'd1');
// get all departments
$re = $dep->select();
// get department by id
$re = $dep->get('d1');
// delete department
$re = $dep->delete('d1');
```
- Manage role (One user can belong to many roles)
```
// create role
$role = $this->pool->getConnection('main')->getMapper('Role');
$data = ['name'=>'r1', 'descr'=>'rrr'];
$rid = $role->insert($data);
// update role
$data_update = ['name'=>'role1', 'descr'=>'ggg'];
$re = $role->update($data_update, null, $rid);
// get all roles
$re = $role->select();
// get role by id
$re = $role->get($rid);
// delete role
$re = $role->delete($rid);
```
- Manage user
```
// create user
$user = $this->pool->getConnection('main')->getMapper('User');
$data = ['name'=>'u1', 'label'=>'user1', 'email'=>'u@u.com', 'descr'=>'rrr', 'department_id'=>'d1'];
$uid = $user->insert($data);
// update user
$data_update = ['name'=>'user1', 'descr'=>'ggg'];
$re = $user->update($data_update, null, $uid);
// get all user
$re = $user->select();
// get user by id
$re = $user->get($uid);
// add role
$re = $user->addRole($uid, 1);
// remove role
$re = $user->removeRole($uid, 1);
// change roles
$re = $user->changeRoles($uid, [['role_name'=>'role1']]);
// user view (get all info of user)
$re = $user->view(); //view all
$re = $user->view('*', ['user_id'=>$uid]); //view by user_id
// delete user
$re = $user->delete($uid);
```
- Manage app
```
// create app
$app = $this->pool->getConnection('main')->getMapper('App');
$data = ['app_name'=>'app1', 'descr'=>'rrr', 'redirect_uri'=>'localhost'];
$aid = $app->insert($data);
// update app
$data_update = ['app_name'=>'app11', 'descr'=>'ggg'];
$re = $app->update($data_update, null, $aid);
// get all app
$re = $app->select();
// get app by id
$re = $app->get($aid);
// get app by app_id and redirect_uri
$re = $app->getApp($aid, 'http://localhost/path');
// verify app
$secret = 'XXXXX';
$re = $app->verifySecret($aid, $secret, 'http://localhost/aa');
// delete app
$re = $app->delete($aid);
```
