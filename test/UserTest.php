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
        $config = new \Wwtg99\Config\Common\ConfigPool();
        $source = new \Wwtg99\Config\Source\FileSource(__DIR__, 'conf.json');
        $source->addLoader(new \Wwtg99\Config\Source\Loader\JsonLoader());
        $config->addSource($source);
        $config->load();
        $auth = new \Wwtg99\PgAuth\Auth\NormalAuth($config);
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
        echo "\ntoken: $token\n";
        $user3 = ['access_token'=>$token];
        $u = $auth->verify($user3);
        echo "\nMessage: " . $auth->getMessage();
        self::assertNotNull($u);
//        var_dump($u->getUser());
        $u = $auth->signOut($user3);
        echo "\nMessage: " . $auth->getMessage();
        self::assertNull($u);
    }
}
