<?php
namespace SabaiApps\Directories\Component\Location\FieldRenderer;

use SabaiApps\Directories\Component\Field\IField;
use SabaiApps\Directories\Component\Entity\Type\IEntity;
use SabaiApps\Directories\Component\Field\Renderer\AbstractRenderer;

class AddressFieldRenderer extends AbstractRenderer
{    
    protected function _fieldRendererInfo()
    {
        return array(
            'field_types' => array($this->_name),
            'default_settings' => array(
                'custom_format' => false,
                'format' => '{street}, {city}, {province} {zip}, {country}',
                'link' => false,
                'target' => '_self',
                '_separator' => '<br />',
            ),
            'inlineable' => true,
        );
    }
    
    protected function _fieldRendererSettingsForm(IField $field, array $settings, array $parents = [])
    {
        $form = [];
        if (!$field->isCustomField()) {
            $location_bundle = $this->_getLocationBundle($field);
            $location_hierarchy = $this->_application->Location_Hierarchy($location_bundle ? $location_bundle : null);
        } else {
            $location_hierarchy = null;
        }
        $form += [
            'custom_format' => [
                '#type' => 'checkbox',
                '#title' => __('Customize address format', 'directories-pro'),
                '#default_value' => !empty($settings['custom_format']),
            ],
            'format' => [
                '#type' => 'textfield',
                '#title' => __('Address format', 'directories-pro'),
                '#description' => $this->_application->System_Util_availableTags($this->_application->Location_FormatAddress_tags($field->Bundle, $location_hierarchy)),
                '#description_no_escape' => true,
                '#default_value' => $settings['format'],
                '#states' => [
                    'visible' => [
                        sprintf('input[name="%s[custom_format]"]', $this->_application->Form_FieldName($parents)) => [
                            'type' => 'checked',
                            'value' => true
                        ],
                    ],
                ],
                '#required' => function ($form) use ($parents) {
                    $val = $form->getValue(array_merge($parents, ['custom_format']));
                    return !empty($val);
                },
            ],
            'link' => [
                '#type' => 'checkbox',
                '#title' => __('Link to Google Maps', 'directories-pro'),
                '#default_value' => !empty($settings['link']),
            ],
            'target' => [
                '#title' => __('Open link in', 'directories-pro'),
                '#type' => 'select',
                '#options' => $this->_getLinkTargetOptions(),
                '#default_value' => $settings['target'],
                '#states' => [
                    'visible' => [
                        sprintf('[name="%s"]', $this->_application->Form_FieldName(array_merge($parents, ['link']))) => ['type' => 'checked', 'value' => true],
                    ],
                ],
            ],
        ];

        return $form;
    }

    protected function _fieldRendererRenderField(IField $field, array &$settings, IEntity $entity, array $values, $more = 0)
    {
        $is_mile = $this->_application->getComponent('Map')->getConfig('map', 'distance_unit') === 'mi';
        $ret = [];
        foreach ($values as $key => $value) {
            if (!empty($settings['custom_format'])) {
                if (!isset($location_hierarchy)) {
                    $location_hierarchy = $this->_application->Location_Hierarchy(($location_bundle = $this->_getLocationBundle($field)) ? $location_bundle : null);
                }
                $address = $this->_application->Location_FormatAddress($value, $settings['format'], $location_hierarchy, $entity);
            } else {
                $address = isset($value['display_address']) ? $value['display_address'] : $this->_application->H($value['address']);
            }
            if (!strlen($address)) continue;

            if ($settings['link']) {
                $target = $rel = '';
                if ($settings['target'] === '_blank') {
                    $target = ' target="_blank"';
                    $rel = 'noopener noreferrer';
                }
                $address = sprintf(
                    '<a href="https://www.google.com/maps/search/?api=1&query=%s,%s" rel="%s"%s>%s</a>',
                    $value['lat'],
                    $value['lng'],
                    $rel,
                    $target,
                    $address
                );
            }
            $ret[] = sprintf(
                '<span class="drts-location-address drts-map-marker-trigger drts-map-marker-trigger-%1$d" data-key="%1$d">%2$s%3$s</span>',
                $key,
                $address,
                isset($value['distance']) && is_numeric($value['distance'])
                    ? ' <span class="' . DRTS_BS_PREFIX . 'badge ' . DRTS_BS_PREFIX . 'badge-dark ' . DRTS_BS_PREFIX . 'mx-1 drts-location-distance">' . sprintf($is_mile ? __('%s mi', 'directories-pro') : __('%s km', 'directories-pro'), round($value['distance'], 2)) . '</span>'
                    : ''
            );
        }
        return implode($settings['_separator'], $ret);
    }
    
    protected function _getLocationBundle(IField $field)
    {
        return $this->_application->Entity_Bundle('location_location', $field->Bundle->component, $field->Bundle->group);
    }
    
    protected function _fieldRendererReadableSettings(IField $field, array $settings)
    {
        $ret = [];
        $field_settings = $field->getFieldSettings();
        if (!empty($field_settings['custom_format'])
            && strlen($field_settings['format'])
        ) {
            $ret['format'] = [
                'label' => __('Address format', 'directories-pro'),
                'value' => $field_settings['format'],
            ];
        }
        $ret['link'] = [
            'label' => __('Link to Google Maps', 'directories-pro'),
            'value' => !empty($settings['link']),
            'is_bool' => true,
        ];
        if (!empty($settings['link'])) {
            $targets = $this->_getLinkTargetOptions(true);
            $ret['target'] = [
                'label' => __('Open link in', 'directories-pro'),
                'value' => $targets[$settings['target']],
            ];
        }
        return $ret;
    }
}
