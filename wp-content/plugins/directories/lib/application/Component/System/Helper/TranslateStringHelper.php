<?php
namespace SabaiApps\Directories\Component\System\Helper;

use SabaiApps\Directories\Application;

class TranslateStringHelper
{
    protected $_translate, $_autoRegister;

    public function __construct(Application $application)
    {
        $this->_translate = $application->getPlatform()->getLanguages() ? true : false;
        $this->_autoRegister = $this->_translate && $application->getComponent('System')->getConfig('auto_reg_str');
    }

    public function help(Application $application, $str, $name, $domain = 'directories', $lang = null)
    {
        if (!$this->_translate) return $str;

        $translated = $application->getPlatform()->translateString($str, $name, $domain, $lang);

        if ($this->_autoRegister
            && $translated === $str
        ) $application->getPlatform()->registerString($str, $name, $domain);

        return $translated;
    }
}