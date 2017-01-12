<?php
/**
 * Created by PhpStorm.
 * User: wwt
 * Date: 2016/10/6 0006
 * Time: 下午 3:40
 */

namespace Wwtg99\PgAuth\Auth;


use Wwtg99\PgAuth\Mapper\User;

abstract class AbstractUser implements IUser
{

    /**
     * @var string
     */
    protected $userId = '';

    /**
     * @var string
     */
    protected $token = '';

    /**
     * @var array
     */
    protected $user = [];

    /**
     * @var User
     */
    protected $mapper = null;

    /**
     * AbstractUser constructor.
     * @param $user_id
     * @param $user
     * @param $token
     */
    public function __construct($user_id, $user, $token = '')
    {
        $this->userId = $user_id;
        $this->token = $token;
        if ($user instanceof User) {
            $this->mapper = $user;
            $this->refreshUser();
        } elseif (is_array($user)) {
            $this->user = $user;
            if ($token) {
                $this->user[IUser::FIELD_TOKEN] = $token;
            }
        }
    }

    /**
     * @return array
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->userId;
    }

    /**
     * Refresh user
     */
    protected function refreshUser()
    {
        if ($this->mapper) {
            $u = $this->mapper->view('*', [IUser::FIELD_USER_ID=>$this->userId]);
            if ($u && isset($u[0])) {
                $this->user = $u[0];
                if ($this->token) {
                    $this->user[IUser::FIELD_TOKEN] = $this->token;
                }
            }
        }
    }

}