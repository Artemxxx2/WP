<?php
namespace SabaiApps\Directories\Component\System\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\Entity\Type\IEntity;

class NoImageHelper
{
    protected $_cache, $_urls = [];

    public function help(Application $application, $size = null, $srcOnly = false, IEntity $entity = null)
    {
        if (!isset($this->_cache)) $this->_cache = $application->Filter('core_no_image_cache', true);

        if (!$url = $this->url($application, $size, $entity)) {
            // 240x180 px transparent png
            $url = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAPAAAAC0CAQAAAAAlWljAAABH0lEQVR42u3RAQ0AAAzCsOPf9HVAOglrTtPFAsACLMACLMACLMCABViABViABViAAQuwAAuwAAuwAAswYAEWYAEWYAEWYMACLMACLMACLMCABViABViABViABRiwAAuwAAuwAAswYAEWYAEWYAEWYMACLMACLMACLMACDFiABViABViABRiwAAuwAAuwAAuwAAMWYAEWYAEWYAEGLMACLMACLMACDFiABViABViABViAAQuwAAuwAAuwAAMWYAEWYAEWYAEGLMACLMACLMACLMCABViABViABViAAQuwAAuwAAuwAAswYAEWYAEWYAEWYMACLMACLMACLMCABViABViABViABRiwAAuwAAuwAAswYAEWYAEWYAEWYMACrNYe6J4AtdAWxOcAAAAASUVORK5CYII=';
        }

        return $srcOnly ? $url : '<img class="drts-no-image" src="' . $application->H($url) . '" alt="" />';
    }

    public function url(Application $application, $size = null, IEntity $entity = null)
    {
        if (!$this->_cache) return $application->Filter('core_no_image_src', false, [$size, $entity]);

        if (!in_array($size, ['icon', 'thumbnail', 'medium', 'large'])) $size = 'full';
        if (!isset($this->_urls[$size])) {
            $this->_urls[$size] = $application->Filter('core_no_image_src', false, [$size, $entity]);;
        }
        return $this->_urls[$size];
    }
}
