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
                    "host"=>"192.168.83.128",
                    "database"=>4
                ]
            ],
            'auth_method'=>7,
            'token_ttl'=>60
        ];
        $auth = new \Wwtg99\PgAuth\Auth\NormalAuth($conn, $config);
        $user1 = ['name'=>'u1', 'password'=>'1', 'label'=>'user 1', 'email'=>'u1@a.com'];
        $u = $auth->signUp($user1);
        echo "\nSign up: " . $auth->getMessage();
        self::assertNotNull($u);
//        var_dump($u->getUser());
        $user2 = [\Wwtg99\PgAuth\Auth\IAuth::KEY_USERNAME=>'u1', \Wwtg99\PgAuth\Auth\IAuth::KEY_PASSWORD=>'1'];
        $u = $auth->signIn($user2);
        echo "\nSign in: " . $auth->getMessage();
        self::assertNotNull($u);
//        var_dump($u->getUser());
        $token = $u->getUserArray()[\Wwtg99\PgAuth\Auth\IUser::FIELD_TOKEN];
        $user3 = [\Wwtg99\PgAuth\Auth\IAuth::KEY_TOKEN=>$token, \Wwtg99\PgAuth\Auth\IAuth::KEY_USERNAME=>'u1'];
        $re = $auth->verify($user3);
        echo "\nVerify: " . $auth->getMessage();
        self::assertTrue($re);
        //change user
        $user4 = ['label'=>'user 111', 'descr'=>'aaaa'];
        $re = $u->changeInfo($user4);
        self::assertTrue($re);
        self::assertEquals('user 111', $u->getUserArray()['label']);
        //inactive
        $re = $u->inactive();
        self::assertTrue($re);
        //active
        $re = $u->active();
        self::assertTrue($re);
        $re = $u->changePassword('2');
        self::assertTrue($re);
        $re = $auth->verify($user2);
        echo "\nVerify: " . $auth->getMessage();
        self::assertFalse($re);
        $user5 = [\Wwtg99\PgAuth\Auth\IAuth::KEY_USERNAME=>'u1', \Wwtg99\PgAuth\Auth\IAuth::KEY_PASSWORD=>'2'];
        $re = $auth->verify($user5);
        echo "\nVerify: " . $auth->getMessage();
        self::assertTrue($re);
        $u = $auth->signOut($user3);
        echo "\nSign out: " . $auth->getMessage();
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
                    "host"=>"192.168.83.128",
                    "database"=>4
                ]
            ],
            'auth_method'=>7,
            'token_ttl'=>60,
            'code_ttl'=>60
        ];
        $appModel = $conn->getMapper('App');
        $app1 = ['app_name'=>'test_app', 'redirect_uri'=>'localhost'];
        $aid = $appModel->insert($app1);
        self::assertTrue($aid != false);
        $appu = new \Wwtg99\PgAuth\Utils\AppUtils($conn);
        //step 1 get code
        $user1 = ['username'=>'u1', 'password'=>'2'];
        $app = $appu->verifyAppIdUri($aid, $app1['redirect_uri']);
        $this->assertNotFalse($app);
        $auth = new \Wwtg99\PgAuth\Auth\NormalAuth($conn, $config);
        $oauth = new \Wwtg99\PgAuth\Utils\OAuthUtils($config);
        $code = '';
        if ($app) {
            $u = $auth->signIn($user1);
            $this->assertNotNull($u);
            if ($u) {
                $code = $oauth->generateCode($u->getUserArray(), $aid, $app[\Wwtg99\PgAuth\Utils\AppUtils::FIELD_REDIRECT_URI]);
                self::assertNotNull($code);
                echo "\nCode: $code";
            }
        }
        //step 2 get access_token
        $secret = $appModel->get($aid, 'app_secret');
        $re = $appu->verifySecret($aid, $secret);
        $this->assertTrue($re);
        if ($re) {
            $u = $oauth->verifyCode($code);
            $this->assertNotFalse($u);
            echo "\nUser: ";
            print_r($u);
        }
    }
}
