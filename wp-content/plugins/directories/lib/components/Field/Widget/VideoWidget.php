<?php
namespace SabaiApps\Directories\Component\Field\Widget;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Field\IField;
use SabaiApps\Directories\Component\Form;
use SabaiApps\Directories\Component\Field\Type\VideoType;

class VideoWidget extends AbstractWidget
{
    protected function _fieldWidgetInfo()
    {
        return array(
            'label' => __('Video field', 'directories'),
            'field_types' => array('video'),
            'default_settings' => [],
            'repeatable' => true,
        );
    }

    public function fieldWidgetForm(IField $field, array $settings, $value = null, Entity\Type\IEntity $entity = null, array $parents = [], $language = null)
    {
        return array(
            '#type' => 'fieldset',
            '#group' => true, // required for Add More to work correctly
            '#row' => true,
            '#element_validate' => [function(Form\Form $form, &$value, $element) {
                switch ($value['provider']) {
                    case 'youtube':
                    case 'vimeo':
                        if (strpos($value['id'], 'http') === 0) {
                            if (!$value['id'] = VideoType::getVideoIdFromUrl($value['provider'], $value['id'])) {
                                $form->setError(__('Invalid video ID or URL', 'directories'), $element['#name'] . '[id]');
                            }
                        }
                        break;
                    default:
                        $value = null;
                }
            }],
            'provider' => array(
                '#type' => 'select',
                '#options' => [
                    'youtube' => 'YouTube', 
                    'vimeo' => 'Vimeo',
                ],
                '#default_value' => isset($value['provider']) ? $value['provider'] : 'youtube',
                '#col' => ['xs' => 6, 'md' => 3],
            ),
            'id' => array(
                '#type' => 'textfield',
                '#default_value' => isset($value['id']) ? $value['id'] : null,
                '#states' => [
                    'visible' => [
                        sprintf('select[name="%s"]', $this->_application->Form_FieldName(array_merge($parents, ['provider']))) => ['type' => 'one', 'value' => ['youtube', 'vimeo']],
                    ],
                ],
                '#col' => ['xs' => 6, 'md' => 9],
                '#placeholder' => __('Enter video ID or URL', 'directories'),
            ),
        );
    }
}