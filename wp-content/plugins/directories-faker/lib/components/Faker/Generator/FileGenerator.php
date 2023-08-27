<?php
namespace SabaiApps\Directories\Component\Faker\Generator;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\Field;

class FileGenerator extends AbstractGenerator
{
    protected $_ids, $_idCount;
    
    protected function _fakerGeneratorInfo()
    {
        $info = parent::_fakerGeneratorInfo();
        $info['default_settings'] = array(
            'probability' => 100,
            'max' => null,
        );
        return $info;
    }
    
    public function fakerGeneratorSettingsForm(Field\IField $field, array $settings, array $parents = [])
    {
        switch ($this->_name) {
            case 'file_image':
            case 'file_file':
                return array(
                    'probability' => $this->_getProbabilitySettingForm($settings['probability']),
                    'max' => $this->_getMaxNumItemsSettingForm($field, $settings['max']),
                ) + $this->_application->File_LocationSettingsForm($parents);
        }
    }
    
    public function fakerGeneratorGenerate(Field\IField $field, array $settings, array &$values, array &$formStorage)
    {        
        switch ($this->_name) {
            case 'file_image':
            case 'file_file':
                if (mt_rand(0, 100) > $settings['probability']) return;
                
                $ret = [];
                $count = $this->_getMaxNumItems($field, $settings['max']);
                if (!isset($this->_ids)) {
                    if ($settings['location'] === 'none') {
                        $ids = $this->_application->File_LocationSettingsForm_saveFiles(
                            $settings,
                            null,
                            array('image_only' => $this->_name === 'file_image')
                        );
                    } else {
                        if (!isset($this->_fileDir)) {
                            $this->_fileDir = $this->_application->File_LocationSettingsForm_uploadDir($settings);
                        }
                        if (!$this->_fileDir) return false; // will skip this field
                        
                        $files = $this->_application->File_LocationSettingsForm_saveFiles(
                            $settings,
                            null,
                            array('image_only' => $this->_name === 'file_image'),
                            $this->_fileDir
                        );
                        $ids = array_keys($files);
                    }
                    $this->_ids = array_keys($ids);
                    $this->_idCount = count($this->_ids);
                }
                if ($this->_idCount > 0) {
                    $max_index = $this->_idCount - 1;
                    for ($i = 0; $i < $count; ++$i) {
                        $ret[] = array('id' => $this->_ids[mt_rand(0, $max_index)]);
                    }
                }
                
                return empty($ret) ? null : $ret;
        }
    }
}