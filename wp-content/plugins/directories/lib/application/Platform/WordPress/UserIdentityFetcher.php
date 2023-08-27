<?php
namespace SabaiApps\Directories\Platform\WordPress;

use SabaiApps\Framework\User\AbstractIdentityFetcher;
use SabaiApps\Framework\User\AnonymousIdentity;
use SabaiApps\Framework\User\RegisteredIdentity;

class UserIdentityFetcher extends AbstractIdentityFetcher
{
    private static $_instance;

    public function __construct()
    {
        $this->_idField = 'ID';
        $this->_usernameField = 'login';
        $this->_nameField = 'display_name';
        $this->_emailField = 'email';
        $this->_urlField = 'url';
        $this->_timestampField = 'registered';
    }

    /**
     * @return UserIdentityFetcher
     */
    public static function getInstance()
    {
        if (!isset(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    protected function _doFetch($limit, $offset, $sort, $order)
    {
        $ret = [];
        $options = array(
            'number' => $limit,
            'offset' => $offset,
            'orderby' => $sort,
            'order' => $order,
        );
        foreach (get_users($options) as $user) {
            $ret[] = $this->_buildIdentity($user);
        }

        return $ret;
    }

    public function count()
    {
        $count = count_users();
        return $count['total_users'];
    }

    protected function _doFetchByIds(array $userIds)
    {
        $ret = [];
        $sql = sprintf(
            'SELECT * FROM %s WHERE ID IN (%s)',
            $GLOBALS['wpdb']->users,
            implode(',', array_map('intval', $userIds))
        );
        foreach ($GLOBALS['wpdb']->get_results($sql) as $result) {
            $ret[$result->ID] = $this->_buildIdentity($result);
        }

        return $ret;
    }

    protected function _doFetchByUsername($userName)
    {
        $user = \WP_User::get_data_by('login', $userName);

        return $user ? $this->_buildIdentity($user) : false;
    }

    protected function _doFetchByEmail($email)
    {
        $user = \WP_User::get_data_by('email', $email);

        return $user ? $this->_buildIdentity($user) : false;
    }

    protected function _doSearch($term, $limit, $offset, $sort, $order)
    {
        $ret = [];
        $sql = $GLOBALS['wpdb']->prepare('SELECT * FROM ' . $GLOBALS['wpdb']->users . ' WHERE user_login LIKE %s ORDER BY %s %s LIMIT %d, %d', $term. '%', $sort, $order, $offset, $limit);
        foreach ($GLOBALS['wpdb']->get_results($sql) as $result) {
            $ret[$result->ID] = $this->_buildIdentity($result);
        }

        return $ret;
    }

    public function getAnonymous()
    {
        return new AnonymousIdentity(array(
            'id' => 0,
            'username' => '',
            'name' => null,
            'url' => '',
            'email' => '',
            'created' => 0,
        ));
    }

    private function _buildIdentity($user)
    {
        return new RegisteredIdentity(array(
            'id' => $user->ID,
            'username' => $user->user_login,
            'name' => $user->display_name,
            'email' => $user->user_email,
            'url' => $user->user_url,
            'created' => strtotime($user->user_registered),
        ));
    }
}