<?php
namespace SabaiApps\Directories;

use SabaiApps\Framework\Application\HttpContext;

class Context extends HttpContext
{
    private $_successUrl, $_successAttributes, $_errorType, $_errorMessage, $_errorUrl, $_errorAttributes, $_redirectType, $_container, $_target,
        $_flash = [], $_flashEnabled = true, $_info = [], $_title, $_menus = [],
        $_tabs = [], $_tabInfo = [], $_tabMenus = [],
        $_currentTabSet = 0, $_currentTab = [], $_templates = [],
        $_redirectMessage;
    
    private static $_current;
    
    public static function getCurrent()
    {
        if (!isset(self::$_current)) {
            self::$_current = new self();
        }
        return self::$_current; 
    }

    public function getTemplates()
    {
        return $this->_templates;
    }

    public function hasTemplate()
    {
        return !empty($this->_templates);
    }
    
    public function addTemplate($template)
    {
        foreach ((array)$template as $_template) {
            $this->_templates[] = $_template;
        }
        
        return $this;
    }
    
    public function clearTemplates()
    {
        $this->_templates = [];
        
        return $this;
    }
    
    public function setSuccess($url = null, array $attributes = [])
    {
        if (isset($url)) {
            $this->_successUrl = $url;
        }
        $this->_successAttributes = $attributes;

        return parent::setSuccess();
    }
    
    public function getSuccessAttributes()
    {
        return $this->_successAttributes;
    }

    public function getSuccessUrl()
    {
        return $this->_successUrl;
    }

    public function getRedirectType()
    {
        return $this->_redirectType;
    }

    public function setRedirect($url, $type = Response::REDIRECT_TEMPORARY)
    {
        $this->_redirectType = $type;

        return parent::setRedirect($url);
    }
       
    public function getRedirectMessage()
    {
        return $this->_redirectMessage;
    }
       
    public function setRedirectMessage($message)
    {
        $this->_redirectMessage = $message;
        return $this;
    }
    
    public function getErrorType()
    {
        return $this->_errorType;
    }

    public function getErrorMessage()
    {
        return $this->_errorMessage;
    }
    
    public function getErrorUrl()
    {
        return $this->_errorUrl;
    }
    
    public function getErrorAttributes()
    {
        return $this->_errorAttributes;
    }
    
    public function setErrorUrl($url)
    {
        $this->_errorUrl = $url;
        
        return $this;
    }

    public function setError($message = null, $url = null, array $attributes = [], $type = Response::ERROR_INTERNAL_SERVER_ERROR)
    {
        $this->_errorMessage = (string)$message;
        if (isset($url)) {
            $this->_errorUrl = $url;
        }
        $this->_errorAttributes = $attributes;
        $this->_errorType = $type;

        return parent::setError();
    }

    public function setBadRequestError($url = null, $message = null, array $attributes = [])
    {
        return $this->setError($message, $url, $attributes, Response::ERROR_BAD_REQUEST);
    }

    public function setUnauthorizedError($url = null, $message = null, array $attributes = [])
    {
        return $this->setError($message, $url, $attributes, Response::ERROR_UNAUTHORIZED);
    }

    public function setForbiddenError($url = null, $message = null, array $attributes = [])
    {
        return $this->setError($message, $url, $attributes, Response::ERROR_FORBIDDEN);
    }

    public function setNotFoundError($url = null, $message = null, array $attributes = [])
    {
        return $this->setError($message, $url, $attributes, Response::ERROR_NOT_FOUND);
    }

    public function setMethodNotAllowedError($url = null, $message = null, array $attributes = [])
    {
        return $this->setError($message, $url, $attributes, Response::ERROR_METHOD_NOT_ALLOWED);
    }

    public function setNotAcceptableError($url = null, $message = null, array $attributes = [])
    {
        return $this->setError($message, $url, $attributes, Response::ERROR_NOT_ACCEPTABLE);
    }
    
    public function setInternalServerError($url = null, $message = null, array $attributes = [])
    {
        return $this->setError($message, $url, $attributes, Response::ERROR_INTERNAL_SERVER_ERROR);
    }

    public function setNotImplementedError($url = null, $message = null, array $attributes = [])
    {
        return $this->setError($message, $url, $attributes, Response::ERROR_NOT_IMPLEMENTED);
    }

    public function setServiceUnavailableError($url = null, $message = null, array $attributes = [])
    {
        return $this->setError($message, $url, $attributes, Response::ERROR_SERVICE_UNAVAILABLE);
    }
    
    public function setValidateFormError($url = null, $message = null, array $attributes = [])
    {
        return $this->setError($message, $url, $attributes, Response::ERROR_VALIDATE_FORM);
    }

    public function getContainer()
    {
        $this->_resolveContainer();

        return $this->_container;
    }
        
    public function getTarget()
    {
        $this->_resolveContainer();

        return $this->_target;
    }
    
    protected function _resolveContainer()
    {
        if (!isset($this->_container)) {
            $this->_container = $this->getRequest()->isAjax();
            $this->_target = '';
            if (is_string($this->_container)) {
                if (strpos($this->_container, ' ')) {
                    list($this->_container, $this->_target) = explode(' ', $this->_container);
                }
            } else {
                $this->_container = '#drts-content';
            }
        }
    }

    public function setContainer($container, $target = '')
    {
        $this->_container = $container;
        $this->_target = $target;

        return $this;
    }

    public function addFlash($message, $level = 'success', $delay = null)
    {
        $this->_flash[] = ['msg' => $message, 'level' => $level, 'delay' => $delay];

        return $this;
    }

    public function getFlash()
    {
        return $this->_flash;
    }

    public function hasFlash()
    {
        return !empty($this->_flash);
    }

    public function clearFlash()
    {
        $this->_flash = [];
        
        return $this;
    }

    public function setFlashEnabled($flag = true)
    {
        $this->_flashEnabled = $flag;

        return $this;
    }

    public function isFlashEnabled()
    {
        return $this->_flashEnabled;
    }

    public function getInfo()
    {
        return $this->_info;
    }

    public function setInfo($title, $url = null)
    {
        if (is_array($title)) {
            foreach ($title as $_title) {
                $this->setInfo($_title['title'], $_title['url']);
            }
        } else {
            if (empty($this->_tabs) || empty($this->_currentTab)) {
                $this->_info[] = ['title' => $title, 'url' => $url];
            } else {
                $this->_tabInfo[$this->_currentTabSet][] = ['title' => $title, 'url' => $url];
            }
        }

        return $this;
    }

    public function popInfo()
    {
        if (empty($this->_tabs) || empty($this->_currentTab)) {
            return array_pop($this->_info);
        }

        return array_pop($this->_tabInfo[$this->_currentTabSet]);
    }
    
    public function clearInfo()
    {
        $this->_info = $this->_tabInfo = [];
        
        return $this;
    }

    public function getTitle($fromInfo = true)
    {
        if (null !== $this->_title) return $this->_title;
        
        if ($fromInfo
            && ($info = $this->getInfo())
            && ($_info = array_values($info))
        ) {
            $_info = array_pop($_info);
            return $_info['title'];
        }
        return '';
    }

    public function setTitle($title)
    {
        $this->_title = $title;

        return $this;
    }

    public function getMenus()
    {
        return $this->_menus;
    }

    public function setMenus(array $menus, $page = false)
    {
        if ($page || empty($this->_tabs) || empty($this->_currentTab)) {
            $this->_menus = $menus;
        } else {
            $this->_tabMenus[$this->_currentTabSet] = $menus;
        }

        return $this;
    }

    public function addMenu($menu, $page = false)
    {
        if ($page || empty($this->_tabs) || empty($this->_currentTab)) {
            $this->_menus[] = $menu;
        } else {
            $this->_tabMenus[$this->_currentTabSet][] = $menu;
        }

        return $this;
    }

    public function clearMenus()
    {
        $this->_menus = $this->_tabMenus = [];
        
        return $this;
    }

    public function getTabs()
    {
        return $this->_tabs;
    }

    public function getTabInfo()
    {
        return $this->_tabInfo;
    }

    public function getTabMenus()
    {
        return $this->_tabMenus;
    }

    public function pushTabs(array $tabs)
    {
        $this->_tabs[++$this->_currentTabSet] = $tabs;

        return $this;
    }

    public function popTabs()
    {
        unset(
            $this->_tabs[$this->_currentTabSet],
            $this->_currentTab[$this->_currentTabSet],
            $this->_tabInfo[$this->_currentTabSet],
            $this->_tabMenus[$this->_currentTabSet]
        );
        --$this->_currentTabSet;

        return $this;
    }

    public function setCurrentTab($tabName)
    {
        if (isset($this->_tabs[$this->_currentTabSet][$tabName])) {
            $this->_currentTab[$this->_currentTabSet] = $tabName;
        }

        return $this;
    }

    public function getCurrentTab()
    {
        return $this->_currentTab;
    }
    
    public function clearTabs($mergeInfo = false)
    {
        foreach (array_keys($this->_currentTab) as $tab_set) {
            if (empty($this->_tabInfo[$tab_set])) continue;
            
            foreach ($this->_tabInfo[$tab_set] as $_tab_info) {    
                $last_key = (string)$_tab_info['url'];
                if (isset($this->_info[$last_key])) continue;
                
                $this->_info[$last_key] = ['title' => $_tab_info['title'], 'url' => $_tab_info['url']];
            }
        }
        if (!$mergeInfo && isset($last_key)) {
            $this->_info = [$last_key => $this->_info[$last_key]];
        }
        if (isset($this->_tabMenus[$this->_currentTabSet])) {
            $this->_menus = $this->_tabMenus[$this->_currentTabSet];
        }
        $this->_currentTab = $this->_tabs = $this->_tabMenu = $this->_tabInfo = [];
        $this->_currentTabSet = 0;
        
        return $this;
    }
    
    public function getAttributes()
    {
        $attr = parent::getAttributes();
        $attr['CONTEXT'] = $this;
        return $attr;
    }
    
    public function isEmbed()
    {
        return $this->getContainer() !== '#drts-content';
    }
}