# PgAuth

Auth library depends on Postgresql.

Support:
- Normal auth and OAuth
- User management
- Role management
- Department management
- App management

### Usage
#### Initialize database
`Run \i install.sql in psql`

#### Auth
- Normal Auth
```
$auth = new \Wwtg99\PgAuth\Auth\NormalAuth($conn, $config);
//sign up
$user1 = ['name'=>'u1', 'password'=>'1', 'label'=>'user 1', 'email'=>'u1@a.com'];
$u = $auth->signUp($user1);
var_dump($u->getUser());
//sign in
$user2 = ['username'=>'u1', 'password'=>'1'];
$u = $auth->signIn($user2);
var_dump($u->getUser());
//verify but not sign in
$re = $auth->verify($user2);
var_dump($re);
//sign out
$auth->signOut($u->getUser());

//change user info by user object
$user3 = ['label'=>'user 111', 'descr'=>'aaaa'];
$re = $u->changeInfo($user3);
//inactive user
$re = $u->inactive();
//active user
$re = $u->active();
```

- OAuth
```
$auth = new \Wwtg99\PgAuth\Auth\OAuthServer($conn, $config);
//step 1 get code
$app_id = 'asmfi2fnn'; //app id
$url = 'http://localhost'; //redirect url
$user1 = ['username'=>'u1', 'password'=>'2'];
$code = $auth->getCode($app_id, $url, $user1);
//step 2 get access_token
$secret = 'asdfasfawefas'; //app secret
$u = $auth->signIn(['code'=>$code, 'app_secret'=>$secret]);
$token = $u->getUser()[\Wwtg99\PgAuth\Auth\IUser::FIELD_TOKEN];
//verify token
$user2 = ['access_token'=>$token];
$re = $auth->verify($user2);
var_dump($re);
```

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

### Client `deprecated in 0.1.7`
The pgauth-cli is used for user management (signup, signin, signout) and user verify.

#### Usage
```
//sign up
php pgauth-cli -a signup --user-name u1 --user-password 1 --user-email a@a.com
//sign in
php pgauth-cli -a signin --user-name u1 --user-password 1
//output
//{"user_id":"U000001","name":"u1","label":null,"email":"a@a.com","descr":null,"department_id":null,"department":null,"department_descr":null,"superuser":false,"roles":["common_user"],"created_at":"2016-10-06 04:52:39.098799+08","updated_at":"2016-10-06 04:52:39.098799+08","deleted_at":null,"access_token":"ff763e1c98"}
//verify
php pgauth-cli -a verify --user-token ff763e1c98
//sign out
php pgauth-cli -a signout --user-token ff763e1c98
```
