<?php
namespace SabaiApps\Framework\DB;

use SabaiApps\Framework\Exception;

class PDOMySQLConnection extends AbstractConnection
{
    protected $_host, $_port, $_dbname, $_user, $_password, $_charset, $_pdo;

    public function __construct(array $config)
    {
        parent::__construct('MySQL');
        $this->_host = $config['host'];
        $this->_dbname = $config['dbname'];
        $this->_port = !empty($config['port']) ? $config['port'] : 3306;
        $this->_user = $config['user'];
        $this->_password = $config['pass'];
        $this->_charset = isset($config['charset']) ? $config['charset'] : 'utf8';
    }

    protected function _doConnect()
    {
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $this->_host,
            $this->_port,
            $this->_dbname,
            $this->_charset
        );
        try {
            $this->_pdo = new \PDO($dsn, $this->_user, $this->_password, [\PDO::MYSQL_ATTR_FOUND_ROWS => true]);
            $this->_pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        } catch (\PDOException $e) {
            throw new Exception($e->getMessage(), $e->getCode(), $e);
        }
    }

    public function getPDO()
    {
        return $this->_pdo;
    }

    public function getCharset()
    {
        return $this->_charset;
    }
}