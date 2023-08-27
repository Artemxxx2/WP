<?php
namespace SabaiApps\Directories\Component\Review\CSVImporter;

use SabaiApps\Directories\Component\CSV\Importer\AbstractImporter;
use SabaiApps\Directories\Component\Entity\Model\Field;

class ReviewCSVImporter extends AbstractImporter
{
    protected function _csvImporterInfo()
    {
        switch ($this->_name) {
            case 'review_rating':
                return [
                    'field_types' => [$this->_name],
                ];
        }
    }

    public function csvImporterSettingsForm(Field $field, array $settings, $column, $enclosure, array $parents = [])
    {
        switch ($this->_name) {
            case 'review_rating':
                $form = array(
                    'separator' => array(
                        '#type' => 'textfield',
                        '#title' => __('Rating criteria/value separator', 'directories-reviews'),
                        '#description' => __('Enter the character used to separate the rating criteria and value.', 'directories-reviews'),
                        '#default_value' => '|',
                        '#horizontal' => true,
                        '#min_length' => 1,
                        '#required' => true,
                        '#weight' => 1,
                    ),
                );

                return $form + $this->_acceptMultipleValues($field, $enclosure, $parents);
        }
    }

    public function csvImporterDoImport(Field $field, array $settings, $column, $value, &$formStorage, array &$logs)
    {
        switch ($this->_name) {
            case 'review_rating':
                if (!empty($settings['_multiple'])) {
                    if (!$values = explode($settings['_separator'], $value)) {
                        return;
                    }
                } else {
                    $values = [$value];
                }
                $ret = [];
                foreach ($values as $value) {
                    if ($value = explode($settings['separator'], $value)) {
                        $ret[(string)$value[0]] = $value[1];
                    }
                }
                return $ret;
        }
    }
}