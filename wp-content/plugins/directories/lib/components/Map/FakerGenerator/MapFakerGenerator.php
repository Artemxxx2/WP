<?php
namespace SabaiApps\Directories\Component\Map\FakerGenerator;

use SabaiApps\Directories\Component\Faker;
use SabaiApps\Directories\Component\Field;
use SabaiApps\Directories\Exception;

class MapFakerGenerator extends Faker\Generator\AbstractGenerator
{
    protected $_viewport;
    
    protected function _fakerGeneratorInfo()
    {
        switch ($this->_name) {
            case 'map_map':
                return array(
                    'field_types' => array($this->_name),
                    'default_settings' => array(
                        'probability' => 100,
                        'max' => 5,
                    ),
                );
        }
    }
    
    public function fakerGeneratorSettingsForm(Field\IField $field, array $settings, array $parents = [])
    {
        switch ($this->_name) {
            case 'map_map':
                return array(
                    'probability' => $this->_getProbabilitySettingForm($settings['probability']),
                    'max' => $this->_getMaxNumItemsSettingForm($field, $settings['max']),
               );
        }
    }
    
    public function fakerGeneratorGenerate(Field\IField $field, array $settings, array &$values, array &$formStorage)
    {
        switch ($this->_name) {
            case 'map_map':
                if (mt_rand(0, 100) > $settings['probability']) return;
                
                $ret = [];
                $count = $this->_getMaxNumItems($field, $settings['max']);
                $faker = $this->_getFaker();
                for ($i = 0; $i < $count; ++$i) {
                    $ret[$i] = array(
                        'lat' => $faker->latitude(),
                        'lng' => $faker->longitude(),
                    );
                }
                return $ret;
        }
    }
}