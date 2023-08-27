<?php
namespace SabaiApps\Directories\Component\Slider;

use SabaiApps\Directories\Component\AbstractComponent;
use SabaiApps\Directories\Component\View;
use SabaiApps\Directories\Component\Field;

class SliderComponent extends AbstractComponent implements
    View\IModes,
    Field\IRenderers
{
    const VERSION = '1.3.108', PACKAGE = 'directories-pro';
    
    public static function description()
    {
        return 'Displays a list of content or content fields in a carousel or photo slider.';
    }
    
    public function viewGetModeNames()
    {
        return ['slider_photos', 'slider_carousel'];
    }
    
    public function viewGetMode($name)
    {
        switch ($name) {
            case 'slider_photos':
                return new ViewMode\PhotosViewMode($this->_application, $name);
            case 'slider_carousel':
                return new ViewMode\CarouselViewMode($this->_application, $name);
        }
    }
    
    public function fieldGetRendererNames()
    {
        return ['slider_photos'];
    }
    
    public function fieldGetRenderer($name)
    {
        switch ($name) {
            case 'slider_photos':
                return new FieldRenderer\PhotosFieldRenderer($this->_application, $name);
        }
    }
}