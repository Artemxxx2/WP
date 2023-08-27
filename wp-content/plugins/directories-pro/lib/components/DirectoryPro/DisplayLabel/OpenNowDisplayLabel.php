<?php
namespace SabaiApps\Directories\Component\DirectoryPro\DisplayLabel;

use SabaiApps\Directories\Component\Display;
use SabaiApps\Directories\Component\Entity;

class OpenNowDisplayLabel extends Display\Label\AbstractLabel
{
    protected function _displayLabelInfo(Entity\Model\Bundle $bundle)
    {
        return array(
            'label' => __('Open now label', 'directories-pro'),
            'default_settings' => array(
                '_label' => _x('Open Now', 'featured label', 'directories-pro'),
                'field' => null,
            ),
            'colorable' => false,
        );
    }

    public function displayLabelSettingsForm(Entity\Model\Bundle $bundle, array $settings, array $parents = array())
    {
        $options = $this->_application->Entity_Field_options($bundle, [
            'interface' => 'Field\Type\TimeType',
            'prefix' => __('Field - ', 'directories-pro'),
            'return_disabled' => true,
        ]);

        return [
            'field' => [
                '#type' => 'select',
                '#title' => __('Select field', 'directories-pro'),
                '#options' => $options[0],
                '#options_disabled' => array_keys($options[1]),
                '#horizontal' => true,
                '#default_value' => $settings['field'],
            ],
        ];
    }

    public function displayLabelText(Entity\Model\Bundle $bundle, Entity\Type\IEntity $entity, array $settings)
    {
        if (empty($settings['field'])
            || (!$values = $entity->getFieldValue($settings['field']))
            || (!$timezone = $this->_getTimezone($entity, $settings))
        ) return;

        try {
            $dt = new \DateTime('now', new \DateTimeZone($timezone));
            $current_day = (int)$dt->format('N');
            $current_time = $dt->format('G') * 3600 + (int)$dt->format('i') * 60;
        } catch (\Exception $e) {
            $this->_application->logError('Invalid timezone or error (ID: ' . $entity->getId() . ', timezone: ' . $timezone . ', message: ' . $e->getMessage());
            return;
        }

        $is_open = false;
        foreach ($values as $i => $value) {
            if (empty($value['day'])) continue;

            if ($value['day'] === $current_day) {
                if ($value['start'] <= $current_time
                    && $value['end'] >= $current_time
                ) {
                    $is_open = true;
                    break;
                }
            } else {
                if ($value['end'] > 86400
                    && ($value['day'] === $current_day - 1 || ($current_day === 1 && $value['day'] === 7))
                    && $value['start'] <= $current_time + 86400
                    && $value['end'] >= $current_time + 86400
                ) {
                    $is_open = true;
                    break;
                }
            }
        }
        if (!$is_open) return;

        return [
            'label' => $settings['_label'],
            'color' => ['type' => 'success'],
        ];
    }

    protected function _getTimezone(Entity\Type\IEntity $entity, array $settings)
    {
        if (!$timezone = $entity->getSingleFieldValue('location_address', 'timezone')) {
            $timezone = $this->_application->getPlatform()->getTimeZone();
        }
        return $timezone;
    }
}
