<?php
namespace SabaiApps\Directories\Component\CSV\Importer;

use SabaiApps\Directories\Component\Entity;

class SocialImporter extends AbstractImporter implements IWpAllImportImporter
{
    protected function _csvImporterInfo()
    {
        $columns = [];
        foreach ($this->_application->Social_Medias() as $media_name => $media) {
            $columns[$media_name] = $media['label'];
        }
        return array(
            'field_types' => array($this->_name),
            'columns' => $columns,
        );
    }

    public function csvWpAllImportImporterAddField(\RapidAddon $addon, Entity\Model\Field $field)
    {
        switch ($this->_name) {
            case 'social_accounts':
                $addon->add_title($field->getFieldLabel());
                foreach ($this->_application->Social_Medias() as $media_name => $media) {
                    $addon->add_field(
                        $field->getFieldName() . '-' . $media_name,
                        $media['label'],
                        'text',
                        null,
                        !isset($media['type']) ? __('Enter a URL', 'directories') : ''
                    );
                }
                return true;
        }
    }

    public function csvWpAllImportImporterDoImport(\RapidAddon $addon, Entity\Model\Field $field, array $data, $options, array $article)
    {
        switch ($this->_name) {
            case 'social_accounts':
                $values = [];
                foreach ($this->_application->Social_Medias() as $media_name => $media) {
                    if (!isset($data[$field->getFieldName() . '-' . $media_name])
                        || (!$value = trim($data[$field->getFieldName() . '-' . $media_name]))
                    ) continue;

                    $values[$media_name] = $value;
                }
                return [$values];
        }
    }
}