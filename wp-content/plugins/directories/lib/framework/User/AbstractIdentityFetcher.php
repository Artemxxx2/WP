<?php
namespace SabaiApps\Framework\User;

abstract class AbstractIdentityFetcher
{
    protected $_idField = 'id';
    protected $_usernameField = 'username';
    protected $_nameField = 'name';
    protected $_emailField = 'email';
    protected $_urlField = 'url';
    protected $_timestampField = 'created';
    private $_identities = [];

    public function clear()
    {
        $this->_identities = [];
    }

    /**
     * Loads user identity objects by user ids
     *
     * @param array $userIds ids of users to load
     */
    protected function _load(array $userIds)
    {
        // Check if requested identities are already oaded
        $user_ids = array_diff($userIds, array_keys($this->_identities));
        // Only load if there are any not loaded yet
        if ($user_ids) {
            $identities = $this->_doFetchByIds($user_ids);
            $this->_identities = $identities + $this->_identities;
            if ($userids_not_found = array_diff($user_ids, array_keys($identities))) {
                foreach ($userids_not_found as $uid) {
                    $this->_identities[$uid] = $this->getAnonymous();
                }
            }
        }
    }

    /**
     * Fetches a user identity object by user id
     *
     * @param string $userId
     * @return AbstractIdentity
     */
    public function fetchById($userId)
    {
        $this->_load([$userId]);
        return $this->_identities[$userId];
    }

    /**
     * Fetches user identity objects by user ids
     *
     * @param array $userIds
     * @return array array of AbstractIdentity objects indexed by user id
     */
    public function fetchByIds(array $userIds)
    {
        $this->_load($userIds);
        return array_intersect_key($this->_identities, array_combine($userIds, $userIds));
    }

    /**
     * Fetches user identity object by user name
     *
     * @param string $userName
     * @return AbstractIdentity
     */
    public function fetchByUsername($userName)
    {
        if (!$identity = $this->_doFetchByUsername($userName)) {
            return $this->getAnonymous();
        }

        $this->_identities[$identity->id] = $identity;

        return $identity;
    }

    /**
     * Fetches user identity object by email address
     *
     * @param string $email
     * @return AbstractIdentity
     */
    public function fetchByEmail($email)
    {
        if (!$identity = $this->_doFetchByEmail($email)) {
            return $this->getAnonymous();
        }

        $this->_identities[$identity->id] = $identity;

        return $identity;
    }

    /**
     * Paginate user identity objects
     *
     * @param int $perpage
     * @param string $sort
     * @param string $order
     * @return IdentityPaginator
     */
    public function paginate($perpage = 20, $sort = 'id', $order = 'ASC', $key = 0)
    {
        return new IdentityPaginator($this, $perpage, $sort, $order, $key);
    }

     /**
     * Fetches user identity objects
     *
     * @return \ArrayObject
     * @param int $limit
     * @param int $offset
     * @param string $sort
     * @param string $order
     */
    public function fetch($limit = 0, $offset = 0, $sort = null, $order = null)
    {
        $identities = $this->_doFetch(
            intval($limit),
            intval($offset),
            $this->_getSortFieldName($sort),
            $order === 'DESC' ? 'DESC' : 'ASC'
        );

        return new \ArrayObject($identities);
    }

    /**
     * Searches user identity objects
     *
     * @return \ArrayObject
     * @param string $term
     * @param int $limit
     * @param int $offset
     * @param string $sort
     * @param string $order
     */
    public function search($term, $limit = 0, $offset = 0, $sort = null, $order = null)
    {
        $identities = $this->_doSearch(
            $term,
            intval($limit),
            intval($offset),
            $this->_getSortFieldName($sort),
            $order === 'DESC' ? 'DESC' : 'ASC'
        );

        return new \ArrayObject($identities);
    }
    
    protected function _getSortFieldName($field)
    {
        switch ($field) {
            case 'name':
                return $this->_nameField;
            case 'username':
                return $this->_usernameField;
            case 'email':
                return $this->_emailField;
            case 'url':
                return $this->_urlField;
            case 'timestamp':
                return $this->_timestampField;
            default:
                return $this->_idField;
        }
    }

    /**
     * Fetches user identity objects
     *
     * @return array
     * @param int $limit
     * @param int $offset
     * @param string $sort
     * @param string $order
     */
    abstract protected function _doFetch($limit, $offset, $sort, $order);

    /**
     * Searches user identity objects
     *
     * @return array
     * @param string $term
     * @param int $limit
     * @param int $offset
     * @param string $sort
     * @param string $order
     */
    abstract protected function _doSearch($term, $limit, $offset, $sort, $order);

    /**
     * Counts user identities
     *
     * @return int
     */
    abstract public function count();

    /**
     * Fetches user identity objects by user ids
     *
     * @abstract
     * @param array $userIds
     * @return array array of AbstractIdentity objects indexed by user id
     */
    abstract protected function _doFetchByIds(array $userIds);

    /**
     * Fetches a user identity object by user name
     *
     * @param string $userName
     * @return mixed AbstractIdentity if user exists, false otherwise
     */
    abstract protected function _doFetchByUsername($userName);

    /**
     * Fetches a user identity object by email address
     *
     * @param string $email
     * @return mixed AbstractIdentity if user exists, false otherwise
     */
    abstract protected function _doFetchByEmail($email);

    /**
     * Creates an anonymous user identity object
     *
     * @return AnonymousIdentity
     */
    abstract public function getAnonymous();
}