<?php
namespace SabaiApps\Directories\Component\Faker\Generator;

use SabaiApps\Directories\Component\Field\IField;
use SabaiApps\Directories\Component\Form\Form;
use SabaiApps\Directories\Exception;

class GeneratedPhotosPhotoGenerator extends AbstractGenerator
{
    protected function _fakerGeneratorInfo()
    {
        return [
            'label' => __('Generated Photos Photo Generator', 'directories-faker'),
            'description' => __('Generate user face photos using Generated Photos API (https://generated.photos/).', 'directories-faker'),
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
                '#title' => __('Generated Photos API key', 'directories-faker'),
                '#description' => sprintf(
                    $this->_application->H(__('Get API key from %s.', 'directories-faker')),
                    '<a href="https://generated.photos/api" target="_blank" rel="nofollow">https://generated.photos/api</a>'
                ),
                '#description_no_escape' => true,
                '#required' => function($form) use ($parents) {
                    return $form->getValue(array_merge(array_slice($parents, 0, -2), ['generator'])) === 'generated_photos';
                },
                '#default_value' => ($api_key = $this->_application->getPlatform()->getCache('faker_generated_photos_api_key')) ? $api_key : null,
                '#element_validate' => [
                    function (Form $form, &$value) {
                        if (empty($value)) return;
                        $this->_application->getPlatform()->setCache($value, 'faker_generated_photos_api_key', 86400 * 365);
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
        if (empty($settings['api_key'])
            || mt_rand(0, 100) > $settings['probability']
        ) return;

        if (!$user_photos = $this->_application->getPlatform()->getCache('faker_generated_photos_photos')) {
            $url = 'https://api.generated.photos/api/v1/faces';
            $params = ['perpage' => 50];
            if (!empty($settings['gender'])) {
                $params['gender'] = $settings['gender'];
            }
            try {
                $result = $this->_application->getPlatform()->remoteGet($this->_application->Url($url, $params, '', '&'), [
                    'headers' => [
                        'Authorization' => 'API-Key ' . $settings['api_key'],
                        'Accept' => 'application/json',
                        'Cache-Control' => 'no-cache',
                    ],
                ]);

                if ((!$user_photos = json_decode($result, true))
                    || empty($user_photos['faces'])
                ) {
                    throw new Exception\RuntimeException('Failed decoding JSON returned from generated.photos.');
                }
                $user_photos = $user_photos['faces'];
            } catch (\Exception $e) {
                $this->_application->logError($e->getMessage());
                return false;
            }
            $this->_application->getPlatform()->setCache($user_photos, 'faker_generated_photos_photos', 86400);
        }

        if (empty($user_photos)) return false;

        if (!isset($formStorage['faker_generated_photos_photos'])) {
            $formStorage['faker_generated_photos_photos'] = [];
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

            if (isset($ret[$photo['id']])) continue;

            if (!isset($formStorage['faker_generated_photos_photos'][$photo['id']])) {
                $url = isset($photo['urls'][5]['1024']) ? $photo['urls'][5]['1024'] : $photo['urls'][4]['512'];
                try {
                    $file_path = $this->_application->getPlatform()->downloadUrl($url);
                } catch (\Exception $e) {
                    $this->_application->logError($e);
                    continue;
                }

                try {
                    $file = $this->_application->getPlatform()->uploadFile(
                        $file_path,
                        'generated-photos-photo-' . $photo['id'] . '.jpeg',
                        $photo['id']
                    );
                } catch (\Exception $e) {
                    @unlink($file_path);
                    $this->_application->logError($e);
                    continue;
                }

                $formStorage['faker_generated_photos_photos'][$photo['id']] = $file;
            }

            $ret[$photo['id']] = $formStorage['faker_generated_photos_photos'][$photo['id']];

        } while (count($ret) < $num && $i < $num + 5 && count($user_photos) > 0);

        $this->_application->getPlatform()->setCache($user_photos, 'faker_generated_photos_photos', 86400 * 7);

        return array_values($ret);
    }
}