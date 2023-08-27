<?php
namespace SabaiApps\Directories\Component\Faker\Generator;

use SabaiApps\Directories\Component\Field\IField;
use SabaiApps\Directories\Component\Form\Form;
use SabaiApps\Directories\Exception;

class UIFacesPhotoGenerator extends AbstractGenerator
{
    protected function _fakerGeneratorInfo()
    {
        return [
            'label' => __('User Face Photo Generator', 'directories-faker'),
            'description' => __('Generate user face photos using UI Faces API (https://uifaces.co/).', 'directories-faker'),
            'field_types' => ['wp_image'],
            'default_settings' => [
                'probability' => 100,
                'max' => 5,
            ],
        ];
    }

    public function fakerGeneratorSettingsForm(IField $field, array $settings, array $parents = [])
    {
        return [
            'api_key' => [
                '#type' => 'textfield',
                '#title' => __('UI Faces API key', 'directories-faker'),
                '#description' => sprintf(
                    $this->_application->H(__('Get API key from %s.', 'directories-faker')),
                    '<a href="https://uifaces.co/api-key" target="_blank" rel="nofollow">https://uifaces.co/api-key</a>'
                ),
                '#description_no_escape' => true,
                '#required' => function($form) use ($parents) {
                    return $form->getValue(array_merge(array_slice($parents, 0, -2), ['generator'])) === 'uifaces_photo';
                },
                '#default_value' => ($api_key = $this->_application->getPlatform()->getCache('faker_uifaces_api_key')) ? $api_key : null,
                '#element_validate' => [
                    function (Form $form, &$value) {
                        if (empty($value)) return;
                        $this->_application->getPlatform()->setCache($value, 'faker_uifaces_api_key', 86400 * 365);
                    }
                ],
            ],
            'gender' => [
                '#type' => 'select',
                '#title' => __('Gender', 'directories-faker'),
                '#options' => [
                    '' => __('Any', 'directories-faker'),
                    'male' => __('Male', 'directories-faker'),
                    'female' => __('Female', 'directories-faker'),
                ],
                '#default_value' => '',
            ],
            'probability' => $this->_getProbabilitySettingForm($settings['probability']),
            'max' => $this->_getMaxNumItemsSettingForm($field, $settings['max']),
        ];
    }

    public function fakerGeneratorGenerate(IField $field, array $settings, array &$values, array &$formStorage)
    {
        if (mt_rand(0, 100) > $settings['probability']) return;

        if (!$user_photos = $this->_application->getPlatform()->getCache('faker_uifaces_photos')) {
            $url = 'https://uifaces.co/api?limit=30';
            if (!empty($settings['gender'])) {
                $url .= '&gender[]=' . $settings['gender'];
            }
            try {
                $result = $this->_application->getPlatform()->remoteGet($url, [
                    'headers' => [
                        'X-API-KEY' => $settings['api_key'],
                        'Accept' => 'application/json',
                        'Cache-Control' => 'no-cache',
                    ],
                ]);
                if (!$user_photos = json_decode($result, true)) {
                    throw new Exception\RuntimeException('Failed decoding JSON returned from uifaces.co.');
                }
            } catch (\Exception $e) {
                $this->_application->logError($e->getMessage());
                return false;
            }
            $this->_application->getPlatform()->setCache($user_photos, 'faker_uifaces_photos', 86400);
        }

        if (empty($user_photos)) return false;

        if (!isset($formStorage['faker_uifaces_photos'])) {
            $formStorage['faker_uifaces_photos'] = [];
        }

        $ret = [];
        $num = $this->_getMaxNumItems($field, $settings['max']);
        $i = 0;
        do {
            ++$i;
            $index = mt_rand(0, count($user_photos) - 1);
            $photo = $user_photos[$index];
            unset($user_photos[$index]);
            $user_photos = array_values($user_photos);

            if (isset($ret[$photo['name']])) continue;

            if (!isset($formStorage['faker_uifaces_photos'][$photo['name']])) {
                $url = $photo['photo'];
                try {
                    $file_path = $this->_application->getPlatform()->downloadUrl($url);
                } catch (\Exception $e) {
                    $this->_application->logError($e);
                    continue;
                }

                try {
                    $file = $this->_application->getPlatform()->uploadFile(
                        $file_path,
                        'uifaces-photo-' . $photo['name'] . '.jpeg',
                        $photo['name']
                    );
                } catch (\Exception $e) {
                    @unlink($file_path);
                    $this->_application->logError($e);
                    continue;
                }

                $formStorage['faker_uifaces_photos'][$photo['name']] = $file;
            }

            $ret[$photo['name']] = $formStorage['faker_uifaces_photos'][$photo['name']];

        } while (count($ret) < $num && $i < $num + 5 && count($user_photos) > 0);

        $this->_application->getPlatform()->setCache($user_photos, 'faker_uifaces_photos', 86400 * 7);

        return array_values($ret);
    }
}