<?php
namespace SabaiApps\Framework\Application;

abstract class AbstractHttpApplication extends AbstractApplication
{
    protected $_scriptUrls = [], $_currentScriptName = 'main', $_modRewriteFormat, $_isSsl = false, $_addUrlTrailingSlash = false;

    public function run(IController $controller, Context $context, $route = null)
    {
        return parent::run($controller, $context, $route);
    }

    public function setCurrentScriptName($name)
    {
        $this->_currentScriptName = $name;

        return $this;
    }

    public function getCurrentScriptName()
    {
        return $this->_currentScriptName;
    }

    public function getUrl($route = '', array $params = [], $fragment = '', $separator = '&amp;')
    {
        return $this->createUrl([
            'route' => $route,
            'params' => $params,
            'fragment' => $fragment,
            'separator' => $separator,
        ]);
    }

    public function getScriptUrl($name = 'main')
    {
        return $this->_scriptUrls[$name];
    }
    
    public function setScriptUrl($url, $name = 'main')
    {
        $this->_scriptUrls[$name] = $url;

        return $this;
    }
    
    public function setModRewriteFormat($modRewriteFormat, $script)
    {
        $this->_modRewriteFormat[$script] = $modRewriteFormat;

        return $this;
    }
    
    public function isSsl($flag = null)
    {
        if (isset($flag)) {
            $this->_isSsl = $flag;
            return $this;
        }
        return $this->_isSsl;
    }
    
    public function addUrlTrailingSlash($flag = null)
    {
        if (isset($flag)) {
            $this->_addUrlTrailingSlash = $flag;
            return $this;
        }
        return $this->_addUrlTrailingSlash;
    }

    public function createUrl(array $options = [])
    {
        $options += [
            'script_url' => null,
            'route' => '',
            'params' => [],
            'fragment' => '',
            'script' => null,
            'separator' => '&amp;',
            'mod_rewrite' => true,
            'force_trailing_slash' => false,
        ];
        if (!isset($options['script_url'])) {
            $route = rtrim($options['route'], '/');
            if (($this->_addUrlTrailingSlash && false === strpos($route, '.'))
                || $options['force_trailing_slash']
            ) {
                $route .= '/';
            }
            $script_name = isset($options['script']) && isset($this->_scriptUrls[$options['script']]) ? $options['script'] : $this->_currentScriptName;
            if (!isset($this->_modRewriteFormat[$script_name]) || !$options['mod_rewrite']) {
                $options['script_url'] = $this->getScriptUrl($script_name);
                // Append route data to request parameters if not the root route
                if ($route) $options['params'][$this->getRouteParam()] = $route;
            } else {
                $options['script_url'] = sprintf($this->_modRewriteFormat[$script_name], $route);
            }
            if ($this->_isSsl && strpos($options['script_url'], 'http:') === 0) {
                $options['script_url'] = 'https:' . substr($options['script_url'], 5);
            }
        }
        
        return new Url($options['script_url'], $options['params'], $options['fragment'], $options['separator'], $options['route']);
    }
}