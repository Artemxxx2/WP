<?php
namespace SabaiApps\Directories\Component\Display\Model;

class Display extends Base\Display
{
    public function isAmp()
    {
        return strpos($this->name, 'amp_') === 0;
    }
    
    public function getCssClasses($addDefaultClass = true)
    {
        $ret = [self::cssClass($this->name, $this->type)];
        if ($addDefaultClass) {
            $default_display = ($pos = strpos($this->name, '-')) ? substr($this->name, 0, $pos) : $this->name;
            $ret[] = 'drts-display-default-' . $default_display;
        }
        return $ret;
    }

    public static function cssClass($name, $type = 'entity')
    {
        if ($type !== 'entity') {
            return 'drts-display-name-' . $type . '-' . str_replace('_', '-', $name);
        }
        return 'drts-display--' . str_replace('_', '-', $name);
    }
}

class DisplayRepository extends Base\DisplayRepository
{
}