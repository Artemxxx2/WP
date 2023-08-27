<?php
namespace SabaiApps\Directories\Component\Field\SystemTool;

use SabaiApps\Directories\Component\System\Tool\AbstractTool;
use SabaiApps\Directories\Exception;

class AdjustTimeSystemTool extends AbstractTool
{
    protected function _systemToolInfo()
    {
        return [
            'label' => __('Adjust time', 'directories'),
            'description' => __('This tool will let you bulk adjust time fields values.', 'directories'),
            'weight' => 100,
        ];
    }

    public function systemToolSettingsForm(array $parents = [])
    {
        $time_fields = [];
        foreach ($this->_application->Entity_Bundles() as $bundle) {
            $time_fields += $this->_application->Entity_Field_options(
                $bundle, ['prefix' => $bundle->getGroupLabel() . ' - ', 'name_prefix' => $bundle->name . '-', 'type' => 'time']
            );
        }
        $hours = [];
        foreach (range(-11, 12) as $hour) {
            if ($hour === 0) continue;

            $hours[$hour] = sprintf(
                _n('%s hour', '%s hours', $hour, 'directories'),
                $hour > 0 ? '+' . $hour : $hour
            );
        }
        return [
            'field' => [
                '#title' => __('Select field', 'directories'),
                '#type' => 'select',
                '#options' => $time_fields,
                '#horizontal' => true,
                '#required' => true,
            ],
            'hours' => [
                '#title' => __('Adjust time', 'directories'),
                '#type' => 'select',
                '#options' => $hours,
                '#horizontal' => true,
                '#default_value' => 1,
            ],
        ];
    }

    public function systemToolInit(array $settings, array &$storage, array &$logs)
    {
        $tasks = [];
        if (!empty($settings['field'])
            && !empty($settings['hours'])
            && ($parts = explode('-', $settings['field']))
            && ($bundle = $this->_application->Entity_Bundle($parts[0]))
            && ($field = $this->_application->Entity_Field($bundle, $parts[1]))
            && $field->getFieldType() === 'time'
        ) {
            if ($langs = $this->_application->getPlatform()->getLanguages()) {
                foreach ($langs as $lang) {
                    $tasks[$lang] = $this->_count($bundle, $field, $lang);
                }
            } else {
                $tasks[''] = $this->_count($bundle, $field);
            }
        }

        return $tasks;
    }

    protected function _count($bundle, $field, $lang = null)
    {
        return $this->_application->Entity_Query($bundle->entitytype_name, $bundle->name)
            ->fieldIsNotNull($field->getFieldName(), 'start')
            ->count($lang);
    }

    public function systemToolRunTask($task, array $settings, $iteration, $total, array &$storage, array &$logs)
    {
        $parts = explode('-', $settings['field']);
        if (empty($parts[0])
            || (!$bundle = $this->_application->Entity_Bundle($parts[0]))
            || (!$field = $this->_application->Entity_Field($bundle, $parts[1]))
        ) return false;

        $lang = empty($task) ? null : $task;
        $paginator = $this->_application->Entity_Query($bundle->entitytype_name, $bundle->name)
            ->fieldIsNotNull($field->getFieldName(), 'start')
            ->sortById()
            ->paginate(50, 0, $lang)
            ->setCurrentPage($iteration);
        $adjust = $settings['hours'] * 3600;
        foreach ($paginator->getElements() as $entity) {
            if (!$time_values = $entity->getFieldValue($field->getFieldName())) continue;

            foreach (array_keys($time_values) as $i) {
                $time_values[$i]['start'] += $adjust;
                $time_values[$i]['end'] += $adjust;
            }
            try {
                $this->_application->Entity_Save($entity, [
                    $field->getFieldName() => $time_values,
                ]);
            } catch (Exception\IException $e) {
                $logs['error'][] = $e->getMessage();
            }
        }

        $label = $bundle->getGroupLabel() . ' - ' . $bundle->getLabel();
        $offset = $paginator->getElementOffset();
        $logs['success'][] = sprintf(
            'Adjust time done for %s (%d - %d)',
            isset($lang) ? $label . '[' . $lang . ']' : $label,
            $offset + 1,
            $offset + $paginator->getElementLimit()
        );

        return $paginator->getElementLimit();
    }
}