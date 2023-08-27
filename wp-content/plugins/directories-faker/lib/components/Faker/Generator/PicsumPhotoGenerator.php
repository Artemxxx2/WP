<?php
namespace SabaiApps\Directories\Component\Faker\Generator;

use SabaiApps\Directories\Component\Field;
use SabaiApps\Directories\Exception;

class PicsumPhotoGenerator extends AbstractGenerator
{
    protected function _fakerGeneratorInfo()
    {
        return [
            'label' => __('Picsum Photo Generator', 'directories-faker'),
            'description' => __('Download random photos from https://picsum.photos/.', 'directories-faker'),
            'field_types' => ['wp_image'],
            'default_settings' => [
                'probability' => 100,
                'max' => 5,
            ],
        ];
    }

    public function fakerGeneratorSettingsForm(Field\IField $field, array $settings, array $parents = [])
    {
        return [
            'probability' => $this->_getProbabilitySettingForm($settings['probability']),
            'max' => $this->_getMaxNumItemsSettingForm($field, $settings['max']),
        ];
    }

    public function fakerGeneratorGenerate(Field\IField $field, array $settings, array &$values, array &$formStorage)
    {
        if (mt_rand(0, 100) > $settings['probability']) return;

        if (!$picsum_photos = $this->_application->getPlatform()->getCache('faker_picsum_photos')) {
            try {
                $result = $this->_application->getPlatform()->remoteGet('https://picsum.photos/list');
                if (!$picsum_photos = json_decode($result, true)) {
                    throw new Exception\RuntimeException('Failed decoding photo list JSON return from picsum.photos.');
                }
            } catch (\Exception $e) {
                $this->_application->logError($e);
                return false;
            }
            $this->_application->getPlatform()->setCache($picsum_photos, 'faker_picsum_photos', 86400 * 7);
        }

        if (!isset($formStorage['picsum_photos'])) {
            $formStorage['picsum_photos'] = [];
        }

        $ret = [];
        $num = $this->_getMaxNumItems($field, $settings['max']);
        $max_index = count($picsum_photos) - 1;
        $i = 0;
        do {
            ++$i;
            $index = mt_rand(0, $max_index);
            $photo = $picsum_photos[$index];
            if (isset($ret[$photo['id']])) continue;

            if (!isset($formStorage['picsum_photos'][$photo['id']])) {
                $url = 'https://picsum.photos/1280/720?image=' . $photo['id'];
                try {
                    $file_path = $this->_application->getPlatform()->downloadUrl($url);
                } catch (\Exception $e) {
                    $this->_application->logError($e);
                    continue;
                }

                try {
                    $file = $this->_application->getPlatform()->uploadFile(
                        $file_path,
                        'picsum-photo-' . $photo['id'] . '.jpeg',
                        sprintf('Photo by %s (%s)', $photo['author'], $photo['author_url'])
                    );
                } catch (\Exception $e) {
                    @unlink($file_path);
                    $this->_application->logError($e);
                    continue;
                }

                $formStorage['picsum_photos'][$photo['id']] = $file;
            }

            $ret[$photo['id']] = $formStorage['picsum_photos'][$photo['id']];

        } while (count($ret) < $num && $i < $num + 5);

        return array_values($ret);
    }
}