<?php
namespace SabaiApps\Directories\Component\CSV\Importer;

use SabaiApps\Directories\Component\Entity\Model\Field;
use SabaiApps\Directories\Component\Entity;

class MapImporter extends AbstractImporter implements IWpAllImportImporter
{    
    protected function _csvImporterInfo()
    {
        switch ($this->_name) {
            case 'map_map':
                return array(
                    'field_types' => array($this->_name),
                    'columns' => array(
                        'lat' => __('Latitude', 'directories'),
                        'lng' => __('Longitude', 'directories'),
                        'zoom' => __('Zoom Level', 'directories'),
                    ),
                );
        }
    }
    
    public function csvImporterSettingsForm(Field $field, array $settings, $column, $enclosure, array $parents = [])
    {
        switch ($this->_name) {
            case 'map_map':
                return $this->_acceptMultipleValues($field, $enclosure, $parents);
        }
    }
    
    public function csvImporterDoImport(Field $field, array $settings, $column, $value, &$formStorage, array &$logs)
    {
        switch ($this->_name) {
            case 'map_map':
                if (!empty($settings['_multiple'])) {
                    if (!$values = explode($settings['_separator'], $value)) {
                        return;
                    }
                } else {
                    $values = array($value);
                }
                $ret = [];
                foreach ($values as $value) {
                    $ret[] = array($column => $value);
                }
        
                return $ret;
        }
    }

    public function csvWpAllImportImporterAddField(\RapidAddon $addon, Entity\Model\Field $field)
    {
        switch ($this->_name) {
            case 'map_map':
                $addon->add_title($field->getFieldLabel());
                $addon->add_field(
                    $field->getFieldName() . '-lat',
                    __('Latitude', 'directories'),
                    'text',
                    null,
                    '',
                    true,
                    ''
                );
                $addon->add_field(
                    $field->getFieldName() . '-lng',
                    __('Longitude', 'directories'),
                    'text',
                    null,
                    '',
                    true,
                    ''
                );
                $addon->add_field(
                    $field->getFieldName() . '-zoom',
                    __('Zoom Level', 'directories'),
                    'text',
                    null,
                    '',
                    true,
                    '10'
                );
                return true;
        }
    }

    public function csvWpAllImportImporterDoImport(\RapidAddon $addon, Entity\Model\Field $field, array $data, $options, array $article)
    {
        switch ($this->_name) {
            case 'map_map':
                if (empty($data[$field->getFieldName() . '-lat'])
                    || empty($data[$field->getFieldName() . '-lng'])
                    || !is_numeric($data[$field->getFieldName() . '-lat'])
                    || !is_numeric($data[$field->getFieldName() . '-lng'])
                ) return;

                $zoom = (int)@$data[$field->getFieldName() . '-zoom'];
                if (!$zoom < 1 || $zoom > 20) $zoom = 10;

                return [[
                    'lat' => $data[$field->getFieldName() . '-lat'],
                    'lng' => $data[$field->getFieldName() . '-lng'],
                    'zoom' => $zoom,
                ]];
        }
    }
}