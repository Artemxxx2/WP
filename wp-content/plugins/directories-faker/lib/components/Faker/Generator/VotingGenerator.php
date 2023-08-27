<?php
namespace SabaiApps\Directories\Component\Faker\Generator;

use SabaiApps\Directories\Component\Field;
use SabaiApps\Directories\Component\Entity;

class VotingGenerator extends AbstractGenerator
{    
    protected function _fakerGeneratorInfo()
    {
        $info = parent::_fakerGeneratorInfo();
        switch ($this->_name) {
            case 'voting_vote':
                $info += array(
                    'default_settings' => array(
                        'probability' => 100,
                        'count' => array('min' => 0, 'max' => 100),
                    ),
                );
                break;
        }
        
        return $info;
    }
    
    public function fakerGeneratorSupports(Entity\Model\Bundle $bundle, Field\IField $field)
    {
        if (strpos($field->getFieldName(), 'voting_') !== 0) return false;

        if (!$voting = (array)$this->_application->Entity_BundleTypeInfo($bundle, 'voting_enable')) return false;

        return in_array(substr($field->getFieldName(), strlen('voting_')), $voting);
    }
    
    public function fakerGeneratorSettingsForm(Field\IField $field, array $settings, array $parents = [])
    {
        switch ($this->_name) {
            case 'voting_vote':
                return array(
                    'probability' => $this->_getProbabilitySettingForm($settings['probability']),
                    'count' => array(
                        '#type' => 'range',
                        '#title' => __('Vote count range', 'directories-faker'),
                        '#integer' => true,
                        '#min_value' => 0,
                        '#max_value' => 100,
                        '#default_value' => $settings['count'],
                    ),
                );
        }
    }
    
    public function fakerGeneratorGenerate(Field\IField $field, array $settings, array &$values, array &$formStorage)
    {
        switch ($this->_name) {
            case 'voting_vote':
                if (mt_rand(0, 100) > $settings['probability']) return;
                
                $count = mt_rand($settings['count']['min'], $settings['count']['max']);
                $sum = 0;
                $type = substr($field->getFieldName(), 7/*strlen('voting_')*/);
                switch ($type) {
                    case 'rating':
                        for ($i = 0; $i < $count; ++$i) {
                            $sum += mt_rand(1, 5);
                        }
                        break;
                    case 'updown':
                        $num = array(-1, 1);
                        for ($i = 0; $i < $count; ++$i) {
                            $sum += $num[mt_rand(0, 1)];
                        }
                        break;
                    default:
                        $sum = $count;
                }
                return array('name' => '', 'count' => 0, 'sum' => 0, 'count_init' => $count, 'sum_init' => $sum, 'force' => true);
        }
    }
}