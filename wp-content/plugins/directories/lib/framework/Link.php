<?php
namespace SabaiApps\Framework;

class Link
{
    private $_url, $_label, $_attributes;
    protected $_options;

    public function __construct($url, $label, array $options = [], array $attributes = [])
    {
        $this->_url = $url;
        $this->_label = $label;
        $this->_options = $options;
        $this->_attributes = $attributes;
    }

    public function getUrl()
    {
        return $this->_url;
    }

    public function setUrl($url)
    {
        $this->_url = $url;
        return $this;
    }
    
    public function getLabel()
    {
        return $this->_label;
    }
    
    public function setLabel($label, $escape = true)
    {
        $this->_label = $label;
        $this->_options['no_escape'] = !$escape;
        return $this;
    }
        
    public function isNoEscape()
    {
        return !empty($this->_options['no_escape']);
    }
    
    public function getIcon()
    {
        return isset($this->_options['icon']) ? $this->_options['icon'] : null;
    }
    
    public function setIcon($icon)
    {
        $this->_options['icon'] = $icon;
        return $this;
    }
    
    public function getAttribute($key)
    {
        return isset($this->_attributes[$key]) ? $this->_attributes[$key] : null;
    }
    
    public function setAttribute($key, $value)
    {
        $this->_attributes[$key] = $value;
        return $this;
    }

    public function removeAttribute($key)
    {
        unset($this->_attributes[$key]);
        return $this;
    }

    public function setBtn($flag)
    {
        $this->_options['btn'] = (bool)$flag;
        return $this;
    }
    
    public function getBtn()
    {
        return isset($this->_options['btn']) ? $this->_options['btn'] : null;
    }

    public function __toString()
    {
        if (!empty($this->_options['icon'])) {
            $icon = htmlspecialchars($this->_options['icon'], ENT_QUOTES, 'UTF-8', false);
            $icon = '<i class="' . $icon . '"></i>';
        }
        if (strlen($this->_label)) {
            $label = empty($this->_options['no_escape']) ? htmlspecialchars($this->_label, ENT_QUOTES, 'UTF-8', false) : $this->_label;
            if (isset($icon)) {
                if (empty($this->_options['no_icon_space'])) {
                    $icon .= ' ';
                }
                if (empty($this->_options['no_wrap_label'])) {
                    $label = $icon . '<span>' . $label . '</span>';
                } else {
                    $label = $icon . $label;
                }
            }
        } else {
            if (isset($icon)) {
                $label = $icon;
            } else {
                $label = '';
            }
        }

        $attributes = [];
        foreach ($this->_getAttributes() as $k => $v) {
            if (empty($v)) continue;

            $attributes[$k] = $k . '="' . htmlspecialchars($v, ENT_COMPAT, 'UTF-8', false) . '"'; // Avoid escaping quotes used in javascript
        }
        
        if (!empty($this->_options['btn'])) {
            if (!array_key_exists('onclick', $this->_attributes) // $this->_attributes['onclick'] may be empty string if disabled explicitly
                && strlen($this->_url)
                && strpos($this->_url, '#') !== 0
            ) {
                if (!empty($this->_attributes['target'])) {
                    $attributes['onclick'] = 'onclick="window.open(\'' . $this->_url . '\', \'' . htmlspecialchars($this->_attributes['target'], ENT_COMPAT, 'UTF-8', false) . '\'); return false;"';
                    unset($attributes['target']);
                } else {
                    $attributes['onclick'] = 'onclick="location.href=\'' . $this->_url . '\'; return false;"';
                }
            }
            return '<button ' . implode(' ', $attributes) . '>' . $label . '</button>';
        }
        
        if (!strlen($this->_url)) return $label;

        return '<a href="' . $this->_url . '" ' . implode(' ', $attributes) . '>' . $label . '</a>';
    }
    
    protected function _getAttributes()
    {
        return $this->_attributes;
    }
}