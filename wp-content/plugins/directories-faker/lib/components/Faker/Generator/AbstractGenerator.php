<?php
namespace SabaiApps\Directories\Component\Faker\Generator;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\Field;
use SabaiApps\Directories\Component\Entity;

abstract class AbstractGenerator implements IGenerator
{
    protected static $_faker = [], $_fakerLocale;
    protected $_application, $_name, $_info;

    public function __construct(Application $application, $name)
    {
        $this->_application = $application;
        $this->_name = $name;
    }

    public function fakerGeneratorInfo($key = null)
    {
        if (!isset($this->_info)) {
            $this->_info = (array)$this->_fakerGeneratorInfo();
        }

        return isset($key) ? (isset($this->_info[$key]) ? $this->_info[$key] : null) : $this->_info;
    }
    
    public function fakerGeneratorSettingsForm(Field\IField $field, array $settings, array $parents = []){}

    protected function _fakerGeneratorInfo()
    {
        return array(
            'field_types' => array($this->_name),
        );
    }
    
    public function fakerGeneratorSupports(Entity\Model\Bundle $bundle, Field\IField $field)
    {
        return true;
    }
    
    protected function _getMaxNumItems(Field\IField $field, $max = null)
    {
        $max = isset($max) ? (int)$max : $field->getFieldMaxNumItems();
        return mt_rand(1, $max ? $max : 5);
    }
    
    protected function _getTimestampSettingForm($default = null, $title = null)
    {
        return array(
            '#type' => 'datepicker',
            '#enable_range' => true,
            '#title' => isset($title) ? $title : __('Date range', 'directories-faker'),
            '#description' => __('Select the range of possible dates for the field.', 'directories-faker'),
            '#default_value' => $default,
            '#required' => true,
            '#min_date' => null,
            '#max_date' => null,
        );
    }
    
    protected function _generateTimestamp($range, $default = null)
    {
        if (empty($range[0]) || empty($range[1]) || $range[0] > $range[1]) return isset($default) ? $default : time();
            
        return mt_rand($range[0], $range[1]);
    }
    
    protected function _getMaxNumItemsSettingForm(Field\IField $field, $default = null, $max = null)
    {
        $max = isset($max) ? (int)$max : $field->getFieldMaxNumItems();
        if (empty($max) || $max > 1) {
            $_max = $max ? $max : 20;
            return array(
                '#type' => 'slider',
                '#title' => __('Max number of items to be assigned', 'directories-faker'),
                '#default_value' => isset($default) ? $default : $_max,
                '#min_value' => 1,
                '#max_value' => $_max,
            );
        } else {
            return array(
                '#type' => 'hidden',
                '#value' => 1,
            );
        }
    }
    
    protected function _getProbabilitySettingForm($default = null)
    {
        return array(
            '#title' => __('Probability of generating value for this field', 'directories-faker'),
            '#type' => 'slider',
            '#field_suffix' => '%',
            '#min_value' => 0,
            '#max_value' => 100,
            '#integer' => true,
            '#default_value' => isset($default) ? $default : 100,
        );
    }
    
    protected function _getFaker($locale = null)
    {
        if (!isset($locale)) {
            if (!isset(self::$_fakerLocale)) {
                self::$_fakerLocale = defined('DRTS_FAKER_LOCALE') ? DRTS_FAKER_LOCALE : $this->_application->getPlatform()->getLocale();
            }
            $locale = self::$_fakerLocale;
        }
        if (!isset(self::$_faker[$locale])) {
            require_once dirname(__DIR__) . '/lib/Faker/autoload.php';
            self::$_faker[$locale] = \Faker\Factory::create($locale);
        }
        return self::$_faker[$locale];
    }
}