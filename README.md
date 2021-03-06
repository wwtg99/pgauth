# PgAuth

Auth library depends on Postgresql.

### Support:
- Normal auth and OAuth
- User management
- Role management
- Department management
- App management

### Auth Method
config auth_method
- Login by name and password: 1
- Login by name and access_token: 2
- Login by access_token only: 4
- Login with user_id only: 8
- Login by email and password: 16
- Login by email and access_token: 32
- Login by tel and password: 64
- Login by tel and access_token: 128

And login TTL, config token_ttl
- Login once (config token_ttl with 0, no cache)
- Login for some time (config token_ttl with > 0, use cache)

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
$pool = new \Wwtg99\DataPool\Common\DefaultDataPool('../conf.json');
$conn = $pool->getConnection('main');
$config = [
    "cache"=>[
        "type"=>"redis",
        "options"=>[
            "schema"=>"tcp",
            "host"=>"192.168.83.128",
            "database"=>4
        ]
    ],
    'auth_method'=>7,
    'token_ttl'=>60,
    'code_ttl'=>60
];
$appu = new \Wwtg99\PgAuth\Utils\AppUtils($conn);
//step 1 get code
$user1 = ['username'=>'u1', 'password'=>'2'];
$aid = 'asfafs';
$redirect_uri = 'localhost';
$auth = new \Wwtg99\PgAuth\Auth\NormalAuth($conn, $config);
$oauth = new \Wwtg99\PgAuth\Utils\OAuthUtils($config);
$code = '';
$app = $appu->verifyAppIdUri($aid, $redirect_uri);
if ($app) {
    $u = $auth->signIn($user1);
    if ($u) {
        $code = $oauth->generateCode($u->getUserArray(), $aid, $redirect_uri);
        echo "\nCode: $code";
    }
}
//step 2 get access_token
$secret = 'fasf3wfsdf';
$u = $oauth->verifyCode($code);
if ($u) {
    $aid = $u['app_id'];
    $re = $appu->verifySecret($aid, $secret);
    if ($re) {
        $token = $u['access_token'];
        echo "\nUser: ";
        print_r($u);
    }
}
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
// delete app
$re = $app->delete($aid);
```
