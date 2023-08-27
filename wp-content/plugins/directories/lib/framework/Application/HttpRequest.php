<?php
namespace SabaiApps\Framework\Application;

class HttpRequest extends AbstractRequest
{   
    protected static $_cookie;

    /**
     * Constructor
     *
     * @param bool $filterGlobals
     * @param bool $forceStripSlashes
     */
    public function __construct($filterGlobals = true, $forceStripSlashes = false, array $params = null)
    {
        if (!isset($params)) {
            switch (strtolower(self::method())) {
                case 'get':
                    $params = $_GET;
                    break;
                default:
                    $params = array_merge($_GET, $_POST);
            }
        }

        if (!isset(self::$_cookie)) {
            $cookie = $_COOKIE;
        }

        if ($filterGlobals) {
            if ($forceStripSlashes) {
                $params = self::_stripSlashes($params);
                if (!empty($cookie)) {
                    $cookie = self::_stripSlashes($cookie);
                }
            }
            // Filter malicious user inputs
            $list = ['GLOBALS', '_GET', '_POST', '_REQUEST', '_COOKIE', '_ENV', '_FILES', '_SERVER', '_SESSION'];
            self::_filterUserData($params, $list);
            if (!empty($cookie)) {
                self::_filterUserData($cookie, $list);
            }
        }

        parent::__construct($params);
        if (!isset(self::$_cookie)) self::$_cookie = $cookie;
    }

    /**
     * @param mixed $var
     */
    protected static function _stripSlashes($var)
    {
        if (is_array($var)) {
            return array_map([__CLASS__, __FUNCTION__], $var);
        }

        return stripslashes($var);
    }

    /**
     * @param mixed $var
     * @param array $globalKeys
     */
    protected static function _filterUserData(&$var, $globalKeys = [])
    {
        if (is_array($var)) {
            $var_keys = array_keys($var);
            if (array_intersect($globalKeys, $var_keys)) {
                $var = [];
            } else {
                foreach ($var_keys as $key) {
                    self::_filterUserData($var[$key], $globalKeys);
                }
            }
        } else {
            $var = str_replace("\x00", '', $var);
        }
    }

    public static function cookie($name)
    {
        return isset(self::$_cookie[$name]) ? self::$_cookie[$name] : null;
    }

    public static function header($name)
    {
        $php_name = 'HTTP_' . strtoupper($name);

        return isset($_SERVER[$php_name]) ? $_SERVER[$php_name] : null;
    }
    
    public static function method()
    {
        return $_SERVER['REQUEST_METHOD'];
    }

    public static function isPostMethod()
    {
        return strcasecmp(self::method(), 'POST') === 0;
    }
    
    public static function url($withQueryStr = true)
    {
        $url = sprintf(
            '%s://%s%s',
            !empty($_SERVER['HTTPS']) && 'off' !== $_SERVER['HTTPS'] ? 'https' : 'http',
            !empty($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost',
            self::uri()
        );
        if (!$withQueryStr) {
            if ($pos = strpos($url, '?')) {
                $url = substr($url, 0, $pos);
            }
        }
        return $url;
    }

    public static function uri()
    {
        if (empty($_SERVER['PHP_SELF']) || empty($_SERVER['REQUEST_URI'])) {
            // IIS
            $_SERVER['REQUEST_URI'] = $_SERVER['SCRIPT_NAME'];

            if (!empty($_SERVER['QUERY_STRING'])) {
                $_SERVER['REQUEST_URI'] .= '?' . $_SERVER['QUERY_STRING'];
            }
        }

        return $_SERVER['REQUEST_URI'];
    }

    public static function isXhr()
    {
        return (isset($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strcasecmp($_SERVER['HTTP_X_REQUESTED_WITH'], 'XMLHttpRequest') === 0);
    }

    public static function files($name)
    {
        if (empty($_FILES)) return [];

        if (isset($_FILES[$name])) return $_FILES[$name];

        if (false === $pos = strpos($name, '[')) return [];

        $base = substr($name, 0, $pos);
        $key = str_replace([']', '['], ['', '"]["'], substr($name, $pos + 1, -1));
        $code = [sprintf('if (!isset($_FILES["%s"]["name"]["%s"])) return [];', $base, $key)];
        $code[] = '$file = [];';
        foreach (['name', 'type', 'size', 'tmp_name', 'error'] as $property) {
            $code[] = sprintf('$file["%1$s"] = $_FILES["%2$s"]["%1$s"]["%3$s"];', $property, $base, $key);
        }
        $code[] = 'return $file;';

        return eval(implode(PHP_EOL, $code));
    }
    
    public static function ip()
    {
        foreach (['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'] as $key) {
            if (!empty($_SERVER[$key])
                && filter_var($_SERVER[$key], FILTER_VALIDATE_IP)
            ) return $_SERVER[$key];
        }
        return '';
    }
    
    public static function userAgent()
    {
        return empty($_SERVER['HTTP_USER_AGENT']) ? '' : $_SERVER['HTTP_USER_AGENT'];
    }
    
    public static function remove($name)
    {
        unset($_GET[$name], $_POST[$name], $_REQUEST[$name]);
    }
}