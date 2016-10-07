<?php
/**
 * Created by PhpStorm.
 * User: wwt
 * Date: 2016/10/7 0007
 * Time: 下午 1:56
 */

date_default_timezone_set('Asia/Shanghai');
require_once implode(DIRECTORY_SEPARATOR, [__DIR__, '..', 'vendor', 'autoload.php']);

function showVersion()
{
    return "Version 0.1.0\n";
}

function showHelp()
{
    return "Pgauth client\n" . showVersion() . "\n-a  --action    actions: signin, signup, signout, verify, code(for oauth)\n--oauth    OAuth server mode (default normal mode)\n--config    specify config file, default auth_conf.json\n--user    user json string\n--user-id    specify user id\n--user-name    specify user name\n--user-password    specify user password\n--user-label    specify user label\n--user-email    specify user email\n--user-token    specify user access token\n--app-id    specify app id\n--app-uri    specify app redirect uri\n--app-secret    specify app secret\n--app-code    specify code (for oauth)\n-v    --version    show version\n-h    --help    show help\n";
}

/**
 * @param $conf
 * @return array
 */
function loadConfig($conf = null)
{
    if (!$conf) {
        $conf = 'auth_conf.json';
    }
    $cont = file_get_contents($conf);
    return json_decode($cont, true);
}

/**
 * @param $type
 * @param array $conf
 * @return \Wwtg99\PgAuth\Auth\IAuth
 */
function getAuth($type, array $conf)
{
    switch (strtolower($type)) {
        case 'oauth':
            $pool = new \Wwtg99\DataPool\Common\DefaultDataPool($conf['connections'], __DIR__ . DIRECTORY_SEPARATOR . '..');
            $auth = new \Wwtg99\PgAuth\Auth\OAuthServer($pool->getConnection('main'), $conf);
            break;
        case 'normal':
        default:
            $pool = new \Wwtg99\DataPool\Common\DefaultDataPool($conf['connections'], __DIR__ . DIRECTORY_SEPARATOR . '..');
            $auth = new \Wwtg99\PgAuth\Auth\NormalAuth($pool->getConnection('main'), $conf);
            break;
    }
    return $auth;
}

/**
 * @param $action
 * @param \Wwtg99\PgAuth\Auth\IAuth $auth
 * @param array $user
 * @param array $conf
 * @return string
 */
function handleAction($action, $auth, array $user, array $conf = [])
{
    switch (strtolower($action)) {
        case 'signup': $re = signUp($auth, $user, $conf); break;
        case 'signin': $re = signIn($auth, $user, $conf); break;
        case 'signout': $re = signOut($auth, $user, $conf); break;
        case 'verify': $re = verify($auth, $user, $conf); break;
        case 'code': $re = getCode($auth, $user, $conf); break;
        default: $re = '';
    }
    return $re;
}

/**
 * @param \Wwtg99\PgAuth\Auth\IAuth $auth
 * @param array $user
 * @param array $conf
 * @return string
 */
function signUp($auth, array $user, array $conf = [])
{
    $u = $auth->signUp($user);
    if ($u) {
        return json_encode($u->getUser(), JSON_UNESCAPED_UNICODE);
    } else {
        return $auth->getMessage();
    }
}

/**
 * @param \Wwtg99\PgAuth\Auth\IAuth $auth
 * @param array $user
 * @param array $conf
 * @return string
 */
function signIn($auth, array $user, array $conf = [])
{
    $u = $auth->signIn($user);
    if ($u) {
        return json_encode($u->getUser(), JSON_UNESCAPED_UNICODE);
    } else {
        return $auth->getMessage();
    }
}

/**
 * @param \Wwtg99\PgAuth\Auth\IAuth $auth
 * @param array $user
 * @param array $conf
 * @return string
 */
function signOut($auth, array $user, array $conf = [])
{
    $u = $auth->signOut($user);
    if ($u) {
        return json_encode($u->getUser(), JSON_UNESCAPED_UNICODE);
    } else {
        return $auth->getMessage();
    }
}

/**
 * @param \Wwtg99\PgAuth\Auth\IAuth $auth
 * @param array $user
 * @param array $conf
 * @return string
 */
function verify($auth, array $user, array $conf = [])
{
    $u = $auth->verify($user);
    if ($u) {
        return json_encode($u->getUser(), JSON_UNESCAPED_UNICODE);
    } else {
        return $auth->getMessage();
    }
}

/**
 * @param \Wwtg99\PgAuth\Auth\OAuthServer $auth
 * @param array $user
 * @param array $conf
 * @return string
 */
function getCode($auth, array $user, array $conf = [])
{
    if ($auth instanceof \Wwtg99\PgAuth\Auth\OAuthServer) {
        $code = $auth->getCode($user);
        if ($code) {
            return $code;
        } else {
            return $auth->getMessage();
        }
    } else {
        return 'Must use oauth';
    }
}

/**
 * @param array $opts
 * @return array
 */
function getUser(array $opts)
{
    if (isset($opts['user'])) {
        $user = $opts['user'];
        $u = json_decode($user, true);
    } else {
        $u = [];
        if (isset($opts['user-id'])) {
            $u[\Wwtg99\PgAuth\Auth\IUser::FIELD_USER_ID] = $opts['user-id'];
        }
        if (isset($opts['user-name'])) {
            $u[\Wwtg99\PgAuth\Auth\IUser::FIELD_USER_NAME] = $opts['user-name'];
        }
        if (isset($opts['user-password'])) {
            $u[\Wwtg99\PgAuth\Auth\IUser::FIELD_PASSWORD] = $opts['user-password'];
        }
        if (isset($opts['user-label'])) {
            $u[\Wwtg99\PgAuth\Auth\IUser::FIELD_LABEL] = $opts['user-label'];
        }
        if (isset($opts['user-email'])) {
            $u[\Wwtg99\PgAuth\Auth\IUser::FIELD_EMAIL] = $opts['user-email'];
        }
        if (isset($opts['user-token'])) {
            $u[\Wwtg99\PgAuth\Auth\IAuth::KEY_USER_TOKEN] = $opts['user-token'];
        }
        if (isset($opts['app-id'])) {
            $u[\Wwtg99\PgAuth\Auth\OAuthServer::FIELD_APP_ID] = $opts['app-id'];
        }
        if (isset($opts['app-uri'])) {
            $u[\Wwtg99\PgAuth\Auth\OAuthServer::FIELD_APP_REDIRECT_URI] = $opts['app-uri'];
        }
        if (isset($opts['app-secret'])) {
            $u['secret'] = $opts['app-secret'];
        }
        if (isset($opts['app-code'])) {
            $u['code'] = $opts['app-code'];
        }
    }
    return $u;
}


$opts = getopt('vha:', ['version', 'help', 'action:', 'config:', 'oauth::', 'user:', 'user-id:', 'user-name:', 'user-password:', 'user-label:', 'user-email:', 'user-token:', 'app-id:', 'app-uri:', 'app-secret:', 'app-code:']);
if (isset($opts['h']) || isset($opts['help'])) {
    echo showHelp();
    exit(0);
}
if (isset($opts['v']) || isset($opts['version'])) {
    echo showVersion();
    exit(0);
}
//load config
$conf = null;
if (isset($opts['config'])) {
    $conf = $opts['config'];
}
$conf = loadConfig($conf);
//auth
if (isset($opts['oauth'])) {
    $auth = 'oauth';
} else {
    $auth = 'normal';
}
$auth = getAuth($auth, $conf);
//user
$user = getUser($opts);
//action
if (isset($opts['a'])) {
    $action = $opts['a'];
} elseif (isset($opts['action'])) {
    $action = $opts['action'];
} else {
    echo "No action provided!\n";
    exit(0);
}
if (isset($action)) {
    $re = handleAction($action, $auth, $user, $conf);
    echo $re;
    echo "\n";
}
exit(0);
