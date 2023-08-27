<?php
namespace SabaiApps\Directories\Component\Directory\SystemWidget;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\Entity\SystemWidget\AbstractEntitiesSystemWidget;

class PostsSystemWidget extends AbstractEntitiesSystemWidget
{
    protected $_directoryType, $_contentType;

    public function __construct(Application $application, $name, $bundleType)
    {
        parent::__construct($application, $name, $bundleType);
        list($this->_directoryType, $this->_contentType) = explode('__', $bundleType);
    }

    protected function _systemWidgetInfo()
    {
        $info = parent::_systemWidgetInfo();
        $info['title'] = $this->_application->Directory_Types_impl($this->_directoryType)->directoryInfo('label') . ' - ' . $info['title'];
        return $info;
    }

    protected function _getDefaultSettings()
    {
        return [
            'sort' => 'post_published',
        ] + parent::_getDefaultSettings();
    }

    protected function _getBundle(array $settings)
    {
        if (empty($settings['directory'])) {
            if (!$directory = $this->_application->getModel('Directory', 'Directory')->type_is($this->_directoryType)->fetchOne()) {
                return;
            }
            $directory_name = $directory->name;
        } else {
            $directory_name = $settings['directory'];
        }

        return $this->_application->Entity_Bundle($this->_bundleType, 'Directory', $directory_name);
    }

    protected function _getWidgetSettings(array $settings)
    {
        $form = parent::_getWidgetSettings($settings);

        $directory_options = [];
        foreach ($this->_application->getModel('Directory', 'Directory')->type_is($this->_directoryType)->fetch() as $directory) {
            if (!$this->_application->Directory_Types_impl($directory->type, true)) continue;

            $directory_options[$directory->name] = $directory->getLabel();
        }
        if (!empty($directory_options)) {
            $form['directory'] = [
                '#title' => __('Select directory', 'directories'),
                '#options' => $directory_options,
                '#type' => count($directory_options) <= 1 ? 'hidden' : 'select',
                '#default_value' => current(array_keys($directory_options)),
                '#weight' => 1,
            ];
        }

        return $form;
    }
}
