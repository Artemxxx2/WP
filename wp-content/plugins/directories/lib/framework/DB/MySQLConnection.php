<?php
namespace SabaiApps\Framework\DB;

use SabaiApps\Framework\Exception;

// mysql_affeceted_rows() returns 0 if no data is modified
// even there was a match, not desirable for implementing
// the optimistic offline locking pattern in which we need
// to return false or 0 only when no matching record was found.
// We can change this behaviour of mysql by supplying the
// following constant to mysql_connect()
if (!defined('MYSQL_CLIENT_FOUND_ROWS')) {
    define('MYSQL_CLIENT_FOUND_ROWS', 2);
}

class MySQLConnection extends AbstractConnection
{
    /**
     * @var string
     */
    protected $_resourceHost;
    /**
     * @var string
     */
    protected $_resourcePort;
    /**
     * @var string
     */
    protected $_resourceName;
    /**
     * @var string
     */
    protected $_resourceUser;
    /**
     * @var string
     */
    protected $_resourceUserPassword;
    /**
     * @var bool
     */
    protected $_resourceSecure;
    /**
     * @var resource
     */
    protected $_link;
    /**
     * @var string
     */
    protected $_clientFlags;
    /**
     * @var string
     */
    protected $_charset = 'utf8';

    /**
     * @var array
     */
    protected static $_charsets = [
        'utf-8' => 'utf8',
        'big5' => 'big5',
        'cp-866' => 'cp866',
        'euc-jp' => 'ujis',
        'euc-kr' => 'euckr',
        'gb2312' => 'gb2312',
        'gbk' => 'gbk',
        'iso-8859-1' => 'latin1',
        'iso-8859-2' => 'latin2',
        'iso-8859-7' => 'greek',
        'iso-8859-8' => 'hebrew',
        'iso-8859-8-i' => 'hebrew',
        'iso-8859-9' => 'latin5',
        'iso-8859-13' => 'latin7',
        'iso-8859-15' => 'latin1',
        'koi8-r' => 'koi8r',
        'shift_jis' => 'sjis',
        'tis-620' => 'tis620',
    ];

    /**
     * Constructor
     */
    public function __construct(array $config)
    {
        parent::__construct('MySQL');
        $this->_resourceName = $config['dbname'];
        $this->_resourceHost = $config['host'];
        $this->_resourcePort = !empty($config['port']) ? $config['port'] : 3306;
        $this->_resourceUser = $config['user'];
        $this->_resourceUserPassword = $config['pass'];
        $this->_resourceSecure = !empty($config['secure']);
        if (isset($config['charset'])) {
            $charset = strtolower($config['charset']);
            $this->_charset = isset(self::$_charsets[$charset]) ? self::$_charsets[$charset] : $charset;
        }
    }

    /**
     * Connects to the mysql server and DB
     *
     * @throws Exception
     */
    protected function _doConnect()
    {
        $this->_clientFlags = $this->_resourceSecure ? MYSQL_CLIENT_FOUND_ROWS | MYSQL_CLIENT_SSL : MYSQL_CLIENT_FOUND_ROWS;
        $host = $this->_resourceHost . ':' . $this->_resourcePort;
        $link = mysql_connect($host, $this->_resourceUser, $this->_resourceUserPassword, true, $this->_clientFlags);
        if ($link === false) {
            throw new Exception(sprintf('Unable to connect to database server @%s', $this->_resourceHost));
        }
        if (!mysql_select_db($this->_resourceName, $link)) {
            throw new Exception(sprintf('Unable to connect to database %s', $this->_resourceName));
        }

        // Set client encoding if requested
        if (!empty($this->_charset)) {
            if (function_exists('mysql_set_charset')) {
                mysql_set_charset($this->_charset, $link);
            } else {
                mysql_query('SET NAMES ' . $this->_charset, $link);
            }
        }

        $this->_link = $link;
    }

    public function getLink()
    {
        return $this->_link;
    }

    public function getCharset()
    {
        return $this->_charset;
    }
}