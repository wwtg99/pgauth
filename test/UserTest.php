<?php

/**
 * Created by PhpStorm.
 * User: wwt
 * Date: 2016/10/6 0006
 * Time: 下午 5:02
 */
class UserTest extends PHPUnit_Framework_TestCase
{
    public static function setUpBeforeClass()
    {
        date_default_timezone_set("Asia/Shanghai");
        require_once '../vendor/autoload.php';
    }

    public function testNormalAuth()
    {
        $pool = new \Wwtg99\DataPool\Common\DefaultDataPool('../conf.json');
        $conn = $pool->getConnection('main');
        $config = [
            "cache"=>[
                "type"=>"redis",
                "options"=>[
                    "schema"=>"tcp",
                    "host"=>"192.168.0.21",
                    "database"=>6
                ]
            ],
            'token_ttl'=>60
        ];
        $auth = new \Wwtg99\PgAuth\Auth\NormalAuth($conn, $config);
        $user1 = ['name'=>'u1', 'password'=>'1', 'label'=>'user 1', 'email'=>'u1@a.com'];
        $u = $auth->signUp($user1);
        echo "\nMessage: " . $auth->getMessage();
        self::assertNotNull($u);
//        var_dump($u->getUser());
        $user2 = ['username'=>'u1', 'password'=>'1'];
        $u = $auth->signIn($user2);
        echo "\nMessage: " . $auth->getMessage();
        self::assertNotNull($u);
//        var_dump($u->getUser());
        $token = $u->getUser()['access_token'];
        $user3 = ['access_token'=>$token, 'username'=>'u1'];
        $u = $auth->verify($user3);
        echo "\nMessage: " . $auth->getMessage();
        self::assertNotNull($u);
        //change user
        $user4 = ['label'=>'user 111', 'descr'=>'aaaa'];
        $re = $u->changeInfo($user4);
        self::assertTrue($re);
        self::assertEquals('user 111', $u->getUser()['label']);
        //inactive
        $re = $u->inactive();
        self::assertTrue($re);
        //active
        $re = $u->active();
        self::assertTrue($re);
        $re = $u->changePassword('2');
        self::assertTrue($re);
        $user5 = ['username'=>'u1', 'password'=>'2'];
        $u = $auth->verify($user5);
        echo "\nMessage: " . $auth->getMessage();
        self::assertNotNull($u);
        $u = $auth->signOut($user3);
        echo "\nMessage: " . $auth->getMessage();
        self::assertNull($u);
    }

    public function testOAuthServer()
    {
        $pool = new \Wwtg99\DataPool\Common\DefaultDataPool('../conf.json');
        $conn = $pool->getConnection('main');
        $config = [
            "cache"=>[
                "type"=>"redis",
                "options"=>[
                    "schema"=>"tcp",
                    "host"=>"192.168.0.21",
                    "database"=>6
                ]
            ],
            'token_ttl'=>60
        ];
        $appModel = $conn->getMapper('App');
        $app1 = ['app_name'=>'test_app', 'redirect_uri'=>'localhost'];
        $aid = $appModel->insert($app1);
        self::assertTrue($aid != false);
        $app = $appModel->get($aid);
        $auth = new \Wwtg99\PgAuth\Auth\OAuthServer($conn, $config);
        $user1 = ['username'=>'u1', 'password'=>'2', 'app_id'=>$aid, 'redirect_uri'=>'http://localhost'];
        $code = $auth->getCode($user1);
        self::assertNotNull($code);
        echo "\nCode: $code";
        echo "\nMessage: " . $auth->getMessage();
        $secret = $app['app_secret'];
        $u = $auth->signIn(['code'=>$code, 'app_secret'=>$secret]);
        self::assertNotNull($u);
        echo "\nMessage: " . $auth->getMessage();
        $token = $u->getUser()['access_token'];
        $user2 = ['access_token'=>$token, 'app_id'=>$aid];
        $u = $auth->verify($user2);
        self::assertNotNull($u);
        echo "\nMessage: " . $auth->getMessage();
    }
}
