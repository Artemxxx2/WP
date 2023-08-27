<?php
namespace SabaiApps\Directories\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Framework\User\AbstractIdentity;

class UserIdentityHtmlHelper
{
    protected $_thumbnails = [];
    
    public function help(Application $application, AbstractIdentity $identity = null, $format = '', $thumbnailOnly = false)
    {
        switch ($format) {
            case 'link':
                return $this->_doLink($application, $identity, $application->H($this->_getUserIdentityName($identity)));
            case 'text':
                return $this->_doLink($application, $identity, $application->H($this->_getUserIdentityName($identity)), false);
            case 'link_thumb_s':
                return $this->_linkWithThubmnail($application, $identity, 'sm', $thumbnailOnly);
            case 'link_thumb':
                return $this->_linkWithThubmnail($application, $identity, '', $thumbnailOnly);
            case 'thumb_s':
                return $this->_thumbnail($application, $identity, 'sm');
            case 'thumb':
                return $this->_thumbnail($application, $identity, '');
            default:
                return isset($identity) ? '' : [
                    'link' => __('Link', 'directories'),
                    'text' => __('No link', 'directories'),
                    'thumb_s' => __('Thumbnail (small)', 'directories'),
                    'thumb' => __('Thumbnail', 'directories'),
                    'link_thumb_s' => __('Thumbnail (small) with link', 'directories'),
                    'link_thumb' => __('Thumbnail with link', 'directories'),
                ];
        }
    }

    public function _linkWithThubmnail(Application $application, AbstractIdentity $identity, $size, $thumbnailOnly = false)
    {
        $content = $this->_thumbnail($application, $identity, $size);
        if (!$thumbnailOnly) $content .= '<span>' . $application->H($this->_getUserIdentityName($identity)) . '</span>';

        return $this->_doLink($application, $identity, $content);
    }
        
    protected function _doLink(Application $application, AbstractIdentity $identity, $html, $link = true)
    {
        if ($identity->isAnonymous()) {
            $class = 'drts-user drts-user-anonymous';
        } else {
            $class = 'drts-user drts-user-registered drts-user-' . $identity->id;
        }
        $attr = [
            'href' => $link ? $identity->url : false,
            'target' => '_blank',
            'rel' => 'nofollow external noopener',
            'class' => $class,
        ];
        $attr = $application->Filter('core_user_link_attr', $attr, [$identity]);
        if (!$link
            || empty($attr['href'])
        ) {
            $tag = 'span';
            unset($attr['href'], $attr['target']);
        } else {
            $tag = 'a';
        }
        return '<' . $tag . $application->Attr($attr) . '>' . $application->Filter('core_user_link_html', $html, [$identity]) . '</' . $tag . '>';
    }
    
    protected function _thumbnail(Application $application, AbstractIdentity $identity, $size)
    {        
        if ($identity->isAnonymous()) return $this->_getThumbnail($application, $identity, $size);
        
        $id = $identity->id;
        if (!isset($this->_thumbnails[$id][$size])) {
            $this->_thumbnails[$id][$size] = $this->_getThumbnail($application, $identity, $size);
        }
        return $this->_thumbnails[$id][$size];
    }
    
    protected function _getThumbnail(Application $application, AbstractIdentity $identity, $size)
    {        
        if (!$url = $this->_getGravatarUrl($application, $identity, $size)) return '';

        return sprintf(
            '<img src="%1$s" alt="%2$s" class="drts-user-thumbnail drts-icon %3$s" />',
            $application->H($url),
            $application->H($this->_getUserIdentityName($identity)),
            $size === 'sm' ? 'drts-icon-sm' : ''
        );
    }
    
    protected function _getGravatarUrl(Application $application, AbstractIdentity $identity, $size)
    {
        if (!$identity->email) return;
   
        return $application->GravatarUrl($identity->email, $size, $identity->gravatar_default, $identity->gravatar_rating);
    }

    protected function _getUserIdentityName(AbstractIdentity $identity)
    {
        $name = $identity->name;
        if ($name === null
            && $identity->isAnonymous()
        ) {
            $name = __('Guest', 'drts');
        }
        return $name;
    }
}