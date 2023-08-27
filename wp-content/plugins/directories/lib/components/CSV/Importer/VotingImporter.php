<?php
namespace SabaiApps\Directories\Component\CSV\Importer;

use SabaiApps\Directories\Component\Entity;

class VotingImporter extends AbstractImporter
{
    public function csvImporterSupports(Entity\Model\Bundle $bundle, Entity\Model\Field $field)
    {
        if (!$voting = (array)$this->_application->Entity_BundleTypeInfo($bundle, 'voting_enable')) return false;

        return in_array(substr($field->getFieldName(), strlen('voting_')), $voting);
    }

    public function csvImporterDoImport(Entity\Model\Field $field, array $settings, $column, $value, &$formStorage, array &$logs)
    {
        if ((!$value = json_decode($value))
            || !is_array($value)
        ) return;
        
        switch ($this->_name) {
            case 'voting_vote':
                $ret = [];
                if (!isset($value[0])) {
                    $value = [$value];
                }
                // Need to convert each to array since json_decode will return object(s)
                foreach ($value as $_value) {
                    $ret[] = (array)$_value;
                }
                return $ret;
        }
    }
}
