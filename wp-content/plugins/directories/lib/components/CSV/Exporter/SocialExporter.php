<?php
namespace SabaiApps\Directories\Component\CSV\Exporter;

class SocialExporter extends AbstractExporter
{
    protected function _csvExporterInfo()
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
}