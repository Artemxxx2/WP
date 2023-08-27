<?php
namespace SabaiApps\Framework\Application;

class Url implements \ArrayAccess
{
    private $_data = [];
    private static $_defined;

    public function __construct($scriptUrl, array $params = [], $fragment = '', $separator = '&amp;', $route = '')
    {
        $this->_data = [
            'script_url' => $scriptUrl,
            'params' => $params,
            'fragment' => $fragment,
            'separator' => $separator,
            'route' => $route,
        ];
    }

    public function &__get($name)
    {
        return $this->_data[$name];
    }

    public function __set($name, $value)
    {
        $this->_data[$name] = $value;
    }
    
    public function set($name, $value)
    {
        $this->_data[$name] = $value;
        return $this;
    }

    public function __toString()
    {
        if (!empty($this->_data['params'])
            && ($query_str = self::_httpBuildQuery($this->_data['params'], $this->_data['separator']))
        ) {
            if (strpos($this->_data['script_url'], '?')) {
                $url = $this->_data['script_url'] . '&' .  $query_str;
            } else {
                $url = $this->_data['script_url'] . '?' . $query_str;
            }
        } else {
            $url = $this->_data['script_url'];
        }

        return strlen($this->_data['fragment']) ? $url . '#' . rawurlencode(ltrim($this->_data['fragment'], '#')) : $url;
    }

    #[\ReturnTypeWillChange]
    public function offsetSet($offset, $value)
    {
        $this->_data[$offset] = $value;
    }

    #[\ReturnTypeWillChange]
    public function offsetExists($offset)
    {
        return isset($this->_data[$offset]);
    }

    #[\ReturnTypeWillChange]
    public function offsetUnset($offset)
    {
        unset($this->_data[$offset]);
    }

    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        return $this->_data[$offset];
    }
    
    protected static function _httpBuildQuery(array $params, $separator)
    {
        ksort($params);
        if (!isset(self::$_defined)) {
            self::$_defined = defined('PHP_QUERY_RFC3986'); // from PHP 5.4.0
        }
        if (self::$_defined) {
            return http_build_query($params, '', $separator, PHP_QUERY_RFC3986);
        }
        return ($ret = http_build_query($params, '', $separator))
            ? strtr($ret, ['%7E' => '~', '+' => '%20']) // For RFC3986 compat
            : $ret;
    }
}