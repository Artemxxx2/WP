<?php
namespace SabaiApps\Directories\Component\Review\CSVExporter;

use SabaiApps\Directories\Component\CSV\Exporter\AbstractExporter;
use SabaiApps\Directories\Component\Entity\Model\Field;

class ReviewCSVExporter extends AbstractExporter
{
    protected function _csvExporterInfo()
    {
        switch ($this->_name) {
            case 'review_rating':
                return [
                    'field_types' => [$this->_name],
                ];
        }
    }

    public function csvExporterSettingsForm(Field $field, array $settings, $column, $enclosure, array $parents = [])
    {
        switch ($this->_name) {
            case 'review_rating':
                return [
                    'separator' => [
                        '#type' => 'textfield',
                        '#title' => __('Rating criteria/value separator', 'directories-reviews'),
                        '#description' => __('Enter the character used to separate the rating criteria and value.', 'directories-reviews'),
                        '#default_value' => '|',
                        '#horizontal' => true,
                        '#min_length' => 1,
                        '#required' => true,
                        '#weight' => 1,
                    ],
                ] + $this->_acceptMultipleValues($field, $enclosure, $parents);
        }
    }

    public function csvExporterDoExport(Field $field, array $settings, $value, array $columns, array &$formStorage, array &$logs)
    {
        switch ($this->_name) {
            case 'review_rating':
                $ret = [];
                foreach ($value[0] as $criteria => $rating) {
                    $ret[] = $criteria . $settings['separator'] . $rating['value'];
                }
                return isset($settings['_separator']) ? implode($settings['_separator'], $ret) : $ret[0];
        }
    }
}