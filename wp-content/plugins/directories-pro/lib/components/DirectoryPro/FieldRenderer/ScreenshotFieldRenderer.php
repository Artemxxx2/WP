<?php
namespace SabaiApps\Directories\Component\DirectoryPro\FieldRenderer;

use SabaiApps\Directories\Component\Field;
use SabaiApps\Directories\Component\Entity;

class ScreenshotFieldRenderer extends Field\Renderer\ImageRenderer
{
    protected function _fieldRendererInfo()
    {
        $info = [
            'label' => __('Screenshot', 'directories-pro'),
            'field_types' => ['url'],
        ] + parent::_fieldRendererInfo();
        $info['default_settings']['link'] = 'url';
        $info['default_settings']['target'] = '_blank';
        $info['default_settings']['screenshot_width'] = 320;

        return $info;
    }

    protected function _fieldRendererSettingsForm(Field\IField $field, array $settings, array $parents = [])
    {
        $form = parent::_fieldRendererSettingsForm($field, $settings, $parents);
        unset($form['link_image_size']);
        $form['size']['#value'] = 'thumbnail';
        $form['size']['#type'] = 'hidden';
        $form['target'] = [
            '#title' => __('Open link in', 'directories-pro'),
            '#type' => 'select',
            '#options' => $this->_getLinkTargetOptions(),
            '#default_value' => $settings['target'],
            '#states' => [
                'visible' => [
                    sprintf('select[name="%s[link]"]', $this->_application->Form_FieldName($parents)) => ['value' => 'url'],
                ],
            ],
            '#weight' => 7,
        ];
        $form['screenshot_width'] = [
            '#title' => __('Screenshot image width', 'directories-pro'),
            '#type' => 'slider',
            '#weight' => 1,
            '#default_value' => $settings['screenshot_width'],
            '#min_value' => 100,
            '#max_value' => 1000,
            '#step' => 10,
            '#field_suffix' => 'px',
        ];

        return $form;
    }

    protected function _getImageLinkTypeOptions(Entity\Model\Bundle $bundle)
    {
        $options = parent::_getImageLinkTypeOptions($bundle);
        unset($options['photo']);
        $options['url'] = __('URL', 'directories-pro');
        return $options;
    }

    protected function _getLinkTarget(Field\IField $field, array $settings)
    {
        if ($settings['link'] === 'url') return $settings['target'];

        return parent::_getLinkTarget($field, $settings);
    }

    protected function _getImageLinkUrl(Field\IField $field, array $settings, $value, $permalinkUrl, $imageUrl)
    {
        if ($settings['link'] === 'url') return $value;

        return parent::_getImageLinkUrl($field, $settings, $value, $permalinkUrl, $imageUrl);
    }

    protected function _getImageUrl(Field\IField $field, array $settings, $value, $size)
    {
        $width = empty($settings['screenshot_width']) || (!$width = intval($settings['screenshot_width'])) ? 320 : $width;
        try {
            $url = $this->_application->getPlatform()->downloadUrl(
                $this->_getScreenshotUrl($settings, $value),
                function (&$file) use ($settings, $value) {
                    if (!$this->_isScreenshotFileValid($file, $settings, $value)) {
                        $file = false;
                        return false;
                    }
                    return true;
                },
                trim(preg_replace('#^https?://#', '', $value), '/') . '-' . $width . 'px',
                '.jpeg'
            );
        } catch (\Exception $e) {
            $this->_application->logError($e->getMessage());
            return;
        }

        return $url;
    }

    protected function _getImageAlt(Field\IField $field, array $settings, $value)
    {
        return $value;
    }

    protected function _getImageTitle(Field\IField $field, array $settings, $value)
    {
        return $value;
    }

    protected function _getScreenshotUrl(array $settings, $value)
    {
        $width = empty($settings['screenshot_width']) || (!$width = intval($settings['screenshot_width'])) ? 320 : $width;
        return 'http://s.wordpress.com/mshots/v1/' . urlencode($value) . '?w=' . $width;
    }

    protected function _isScreenshotFileValid($file, array $settings, $value)
    {
        $mime = $this->_application->FileType($file);
        return strpos($mime, 'image/jpeg') !== false;
    }
}
