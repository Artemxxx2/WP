<?php
namespace SabaiApps\Directories\Component\Faker\Generator;

use SabaiApps\Directories\Component\Field;

class SocialGenerator extends AbstractGenerator
{
    protected function _fakerGeneratorInfo()
    {
        $info = parent::_fakerGeneratorInfo();
        switch ($this->_name) {
            case 'social_accounts':
                $info += array(
                    'default_settings' => array(
                        'probability' => 80,
                    ),
                );
                foreach ($this->_application->Social_Medias() as $media_name => $media) {
                    if (isset($media['default'])) {
                        $info['default_settings'][$media_name] = $media['default'];
                    }
                }
                break;
        }
        return $info;
    }
    
    public function fakerGeneratorSettingsForm(Field\IField $field, array $settings, array $parents = [])
    {
        switch ($this->_name) {
            case 'social_accounts':
                $form = array(
                    'probability' => $this->_getProbabilitySettingForm($settings['probability']),
                );
                foreach ($this->_application->Social_Medias() as $media_name => $media) {
                    $form[$media_name] = [
                        '#group' => true,
                        '#title' => $media['label'],
                    ];
                    $form[$media_name][0] = array(
                        '#type' => isset($media['type']) ? $media['type'] : 'url',
                        '#field_prefix' => isset($media['icon']) ? sprintf('<i class="%s"></i>', $media['icon']) : null,
                        '#default_value' => isset($settings[$media_name]) ? $settings[$media_name] : null,
                        '#regex' => isset($media['regex']) ? $media['regex'] : null,
                        '#placeholder' => isset($media['placeholder']) ? $media['placeholder'] : '',
                        '#description' => __('Enter dummy data for this social media account.', 'directories-faker'),
                    );
                    $form[$media_name]['_add'] = [
                        '#type' => 'addmore',
                        '#next_index' => 1,
                    ];
                }
                return $form;
        }
    }
    
    public function fakerGeneratorGenerate(Field\IField $field, array $settings, array &$values, array &$formStorage)
    {        
        switch ($this->_name) {
            case 'social_accounts':
                $ret = [];
                foreach ($this->_application->Social_Medias() as $media_name => $media) {
                    if (!empty($settings[$media_name])
                        && mt_rand(0, 100) <= $settings['probability']
                    ) {
                        $ret[$media_name] = $settings[$media_name][mt_rand(0, count($settings[$media_name]) - 1)];
                    }
                }
                return empty($ret) ? null : $ret;
        }
    }   
}