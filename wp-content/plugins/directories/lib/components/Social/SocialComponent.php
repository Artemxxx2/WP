<?php
namespace SabaiApps\Directories\Component\Social;

use SabaiApps\Directories\Component\AbstractComponent;
use SabaiApps\Directories\Component\Field;

class SocialComponent extends AbstractComponent implements
    IMedias,
    Field\ITypes,
    Field\IWidgets,
    Field\IFilters,
    Field\IRenderers
{
    const VERSION = '1.3.108', PACKAGE = 'directories';
    
    public static function description()
    {
        return 'Enables social media account fields and display content fetched from social media sites.';
    }
    
    public function socialMediaNames()
    {
        $names = ['facebook', 'twitter', 'pinterest', 'tumblr', 'linkedin', 'flickr', 'youtube', 'instagram', 'rss', 'tripadvisor', 'github', 'telegram', 'tiktok'];
        if ($custom = $this->_application->Filter('social_medias', [])) {
            $names = array_merge($names, array_keys($custom));
        }
        return $names;
    }
    
    public function socialMediaInfo($name)
    {
        switch ($name) {
            case 'facebook': 
                return [
                    'type' => 'textfield',
                    'label' => 'Facebook',
                    'icon' => 'fab fa-facebook-square',
                    //'regex' => '/^https?:\/\/((w{3}\.)?)facebook.com\/.*/i',
                    'default' => 'facebook',
                    'placeholder' => sprintf(__('Enter %s username or URL.', 'directories'), 'Facebook'),
                ];
            case 'twitter': 
                return [
                    'type' => 'textfield',
                    'label' => 'Twitter',
                    'icon' => 'fab fa-twitter-square',
                    //'regex' => '/^https?:\/\/twitter\.com\/(#!\/)?[a-z0-9_]+[\/]?$/i',
                    'default' => 'twitter',
                    'placeholder' => sprintf(__('Enter %s username or URL.', 'directories'), 'Twitter'),
                ];
            case 'pinterest': 
                return [
                    'type' => 'textfield',
                    'label' => 'Pinterest',
                    'icon' => 'fab fa-pinterest-square',
                    'default' => 'pinterest',
                    'placeholder' => sprintf(__('Enter %s username or URL.', 'directories'), 'Pinterest'),
                ];
            case 'instagram': 
                return [
                    'type' => 'textfield',
                    'label' => 'Instagram',
                    'icon' => 'fab fa-instagram-square',
                    'default' => 'instagram',
                    'placeholder' => __('Enter Instagram username or URL. Prefix with "#" if hashtag.', 'directories'),
                ];
            case 'youtube': 
                return [
                    'type' => 'textfield',
                    'label' => 'YouTube',
                    'icon' => 'fab fa-youtube-square',
                    'default' => 'YouTube',
                    'placeholder' => sprintf(__('Enter %s username or URL.', 'directories'), 'YouTube'),
                ];
            case 'tumblr': 
                return [
                    'label' => 'Tumblr',
                    'icon' => 'fab fa-tumblr-square',
                    'default' => 'http://staff.tumblr.com/',
                ];
            case 'linkedin': 
                return array(
                    'label' => 'LinkedIn',
                    'icon' => 'fab fa-linkedin',
                    'default' => 'https://www.linkedin.com/company/linkedin',
                );
            case 'flickr': 
                return [
                    'label' => 'Flickr',
                    'icon' => 'fab fa-flickr',
                    'default' => 'https://www.flickr.com/people/flickr',
                ];
            case 'rss': 
                return [
                    'label' => 'RSS',
                    'icon' => 'fas fa-rss-square',
                    'default' => $this->_application->getPlatform()->getSiteUrl(),
                ];
            case 'tripadvisor':
                return [
                    'label' => 'Tripadvisor',
                    'icon' => 'fab fa-tripadvisor',
                    'default' => 'https://www.tripadvisor.jp/Profile/Tripadvisor',
                ];
            case 'github':
                return [
                    'type' => 'textfield',
                    'label' => 'GitHub',
                    'icon' => 'fab fa-github-square',
                    'default' => 'github',
                    'placeholder' => sprintf(__('Enter %s username or URL.', 'directories'), 'GitHub'),
                ];
            case 'telegram':
                return [
                    'type' => 'textfield',
                    'label' => 'Telegram',
                    'icon' => 'fab fa-telegram',
                    'default' => 'telegram',
                    'placeholder' => sprintf(__('Enter %s username or URL.', 'directories'), 'Telegram'),
                ];
            case 'tiktok':
                return [
                    'type' => 'textfield',
                    'label' => 'TikTok',
                    'icon' => 'fab fa-tiktok',
                    'default' => '@tiktok',
                    'placeholder' => sprintf(__('Enter %s username or URL.', 'directories'), 'TikTok'),
                ];
            default:
                $custom = $this->_application->Filter('social_medias', []);
                if (isset($custom[$name])) {
                    return $custom[$name];
                }
        }
    }

    public function socialMediaUrl($name, $value)
    {
        if (strpos($value, 'https://') === 0
            || strpos($value, 'http://') === 0
        ) return $value;

        switch ($name) {
            case 'facebook':
                if (strpos($value, '!') === 0) {
                    $value = substr($value, 1);
                }
                return 'https://www.facebook.com/' . rawurlencode($value);
            case 'twitter':
                if (strpos($value, '#') === 0) {
                    return 'https://twitter.com/hashtag/' . rawurlencode(substr($value, 1));
                }
                if (strpos($value, '@') === 0) {
                    $value = substr($value, 1);
                }
                return 'https://twitter.com/' . rawurlencode($value);
            case 'pinterest':
                return 'https://www.pinterest.com/' . rawurlencode($value);
            case 'instagram':
                if (strpos($value, '#') === 0) {
                    return 'https://instagram.com/explore/tags/' . rawurlencode(substr($value, 1));
                }
                return 'https://instagram.com/' . rawurlencode($value);
            case 'youtube':
                return 'https://www.youtube.com/user/' . rawurlencode($value);
            case 'github':
                return 'https://www.github.com/' . rawurlencode($value);
            case 'telegram':
                return 'https://t.me/' . rawurlencode($value);
            case 'tiktok':
                return 'https://tiktok.com/@' . rawurlencode(ltrim($value, '@'));
            case 'tumblr':
            case 'linkedin':
            case 'flickr':
            case 'rss':
            case 'tripadvisor':
                return $value;
            default:
                return $this->_application->Filter('social_media_url', $value, [$name, $value]);
        }
    }

    public function fieldGetTypeNames()
    {
        return ['social_accounts'];
    }

    public function fieldGetType($name)
    {
        return new FieldType\AccountsFieldType($this->_application, $name);
    }

    public function fieldGetWidgetNames()
    {
        return ['social_accounts'];
    }

    public function fieldGetWidget($name)
    {
        return new FieldWidget\AccountsFieldWidget($this->_application, $name);
    }
    
    public function fieldGetFilterNames()
    {
        return ['social_accounts'];
    }

    public function fieldGetFilter($name)
    {
        return new FieldFilter\AccountsFieldFilter($this->_application, $name);
    }
    
    public function fieldGetRendererNames()
    {
        return ['social_accounts', 'social_twitter_feed', 'social_facebook_page', 'social_facebook_messenger_link'];
    }

    public function fieldGetRenderer($name)
    {
        switch ($name) {
            case 'social_accounts':
                return new FieldRenderer\AccountsFieldRenderer($this->_application, $name);
            case 'social_twitter_feed':
                return new FieldRenderer\TwitterFeedFieldRenderer($this->_application, $name);
            case 'social_facebook_page':
                return new FieldRenderer\FacebookPageFieldRenderer($this->_application, $name);
            case 'social_facebook_messenger_link':
                return new FieldRenderer\FacebookMessengerLinkFieldRenderer($this->_application, $name);
        }
    }
}
