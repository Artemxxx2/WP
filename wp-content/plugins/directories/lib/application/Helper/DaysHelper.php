<?php
namespace SabaiApps\Directories\Helper;

use SabaiApps\Directories\Application;

class DaysHelper
{
    protected $_days;
    
    public function help(Application $application, $value = null)
    {
        if (isset($value)) {
            return isset($this->_days) ? $this->_days[$value] : $this->_day($value);
        }
        
        if (!isset($this->_days)) {
            foreach (range(1, 7) as $value) {
                $this->_days[$value] = $this->_day($value);
            }
            $start_of_week = (int)$application->getPlatform()->getStartOfWeek();
            if (isset($this->_days[$start_of_week]) && $start_of_week !== 1) {
                $_days = [$start_of_week => $this->_days[$start_of_week]];
                unset($this->_days[$start_of_week]);
                for ($i = $start_of_week + 1; $i <= 7; $i++) {
                    $_days[$i] = $this->_days[$i];
                    unset($this->_days[$i]);
                }
                foreach ($this->_days as $i => $_day) {
                    $_days[$i] = $_day;
                }
                $this->_days = $_days;
            }
        }
        
        return $this->_days;
    }
    
    protected function _day($value)
    {
        switch ($value) {
            case 1: 
                return __('Monday', 'directories');
            case 2:
                return __('Tuesday', 'directories');
            case 3:
                return __('Wednesday', 'directories');
            case 4:
                return __('Thursday', 'directories');
            case 5:
                return __('Friday', 'directories');
            case 6:
                return __('Saturday', 'directories');
            case 7:
                return __('Sunday', 'directories');
            default:
                return '';
        }
    }
}