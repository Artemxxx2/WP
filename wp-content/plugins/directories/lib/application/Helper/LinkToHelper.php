<?php
namespace SabaiApps\Directories\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Link;

class LinkToHelper
{
    private static $_alwaysPost = false;
    
    public function __construct()
    {
        if (defined('DRTS_FIX_URI_TOO_LONG') && DRTS_FIX_URI_TOO_LONG) {
            self::$_alwaysPost = true;
        }
    }
    
    public function help(Application $application, $linkText, $url, array $options = [], array $attributes = [])
    {
        if (isset($options['container'])) {
            if ($options['container'] === 'popover') {
                return $this->_popover($application, $linkText, $url, $options, $attributes);
            }
            if ($options['container'] === 'modal') $options['container'] = '#drts-modal';
            return $this->_remote($application, $linkText, $options['container'], $url, $options, $attributes);
        }
        
        return new Link(
            is_string($url) && ($url === '' || $url === '#') ? $url : $application->Url($url),
            $linkText,
            $options,
            $attributes
        );
    }
    
    protected function _popover(Application $application, $linkText, $url, array $options = [], array $attributes = [])
    {
        $attributes['onclick'] = 'DRTS.popover(this, {html:true}); event.preventDefault();';
        if (strlen($url)) {
            $url = $attributes['data-popover-url'] = (string)$application->Url($url);
        } else {
            $url = '#';
        }
        
        return new Link($url, $linkText, $options, $attributes); 
    }
    
    protected function _remote(Application $application, $linkText, $update, $url, array $options = [], array $attributes = [])
    {
        if (isset($options['url'])) {
            $ajax_url =  $application->Url($options['url']);
            $url = strlen($url) ? $application->Url($url) : '#';
        } else {
            if (is_string($url)
                && strpos($url, '#') === 0
            ) {
                $ajax_url = $url;
            } else {
                $url = $application->Url($url);
                $ajax_url = clone $url;
            }

        }
        $attributes['onclick'] = 'DRTS.ajax({' . implode(',', $this->_getAjaxOptions($application, $update, $options)) . '}); event.preventDefault();';
        $attributes['data-ajax-url'] = $ajax_url;
        
        return new Link($url, $linkText, $options, $attributes); 
    }
    
    protected function _getAjaxOptions(Application $application, $update, array $options)
    {
        $ajax_options = empty($options['ajax']) ? [] : $options['ajax'];
        if (!empty($options['target'])) {
            $ajax_options[] = "target:'" . $application->H($options['target']) . "'";
        }
        if (isset($options['loadingImage']) && !$options['loadingImage']) $ajax_options[] = 'loadingImage:false';
        if (!empty($options['slide'])) $ajax_options[] = "effect:'slide'";
        if (!empty($options['scroll'])) {
            $ajax_options[] = is_string($options['scroll']) ? "scroll:'" . $application->H($options['scroll']) . "'" : 'scroll:true';
        }
        if (!empty($options['highlight'])) $ajax_options[] = 'highlight:true';
        if (!empty($options['replace'])) $ajax_options[] = 'replace:true';
        if (!empty($options['cache'])) {
            $ajax_options[] = 'cache:true';
            if (is_string($options['cache'])) {
                $ajax_options[] = "cacheId:'" . $application->H($options['cache']) . "'";
            }
        }
        if (!empty($options['sendData'])) {
            $ajax_options[] = 'onSendData:function(data, trigger){' . $options['sendData'] . '}';
        }
        if (!empty($options['success'])) {
            $ajax_options[] = 'onSuccess:function(result, target, trigger){' . $options['success'] . '}';
        }
        if (!empty($options['error'])) {
            $ajax_options[] = 'onError:function(result, target, trigger){' . $options['error'] . '}';
        }
        if (!empty($options['redirect'])) {
            $ajax_options[] = 'onSuccessRedirect:true, onErrorRedirect:true';
        }
        if (!empty($options['content'])) {
            $ajax_options[] = 'onContent:function(response, target, trigger, isCache){' . $options['content'] . '}';
        }
        if (!empty($options['readyState'])) {
            $ajax_options[] = 'onReadyState:function(response, target, trigger, count){' . $options['readyState'] . '}';
        }
        if (!empty($options['post']) || self::$_alwaysPost) {
            $ajax_options[] = "type:'post'";
        }
        if (!empty($options['pushState'])) {
            $ajax_options[] = "pushState:true";
        }
        if (isset($options['modalSize'])) {
            $ajax_options[] = "modalSize:'" . $options['modalSize'] . "'";
        }
        $ajax_options[] = "trigger:jQuery(this), container:'" . $application->H($update) . "'";

        if (!empty($options['append'])) {
            $ajax_options[] = "append:'" . $application->H($options['append']) . "'";
            if (!empty($options['appendTo'])) {
                $ajax_options[] = "appendTo:'" . $application->H($options['appendTo']) . "'";
            }
        }
        
        return $ajax_options;
    }
}