<?php
namespace SabaiApps\Directories\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Framework\Paginator\AbstractPaginator;

class PageNavHelper
{
    public static $offset = 1;

    public function help(Application $application, $update, AbstractPaginator $pages, $linkUrl, array $options = [], $offset = null)
    {
        if (1 >= $page_count = $pages->count()) return '';

        $options += [
            'class' => '',
            'color' => 'outline-secondary',
            'container' => $update,
            'scroll' => $update,
            'pushState' => true,
            'no_escape' => true
        ];
        $btn_class = DRTS_BS_PREFIX . 'btn ' . DRTS_BS_PREFIX . 'btn-' . $options['color'];
        if (isset($options['size'])) $btn_class .= ' ' . DRTS_BS_PREFIX . 'btn-' . $options['size'];
        $current_page = $pages->getCurrentPage();
        $current_html = sprintf('<a href="#" onclick="return false;" class="%s %sactive">%d</a>', $btn_class, DRTS_BS_PREFIX, $current_page);
        $html = [];
        if (!isset($offset)) $offset = self::$offset;
        $link_url = $application->Url($linkUrl); // convert to Url object
        $ajax_url = isset($options['url']) ? $application->Url($options['url']) : null; // convert to Url object
        $min = max(1, $current_page - $offset);
        $max = $current_page + $offset;
        if ($max > $page_count) $max = $page_count;
        if ($application->getPlatform()->isRtl()) {
            $first_icon = 'fa-angle-double-right';
            $last_icon = 'fa-angle-double-left';
        } else {
            $first_icon = 'fa-angle-double-left';
            $last_icon = 'fa-angle-double-right';
        }
        if ($current_page != 1) {
            $html[] = $this->_getPageLink($application, '<i class="fas ' . $first_icon . '"></i>', $current_page - 1, $link_url, $ajax_url, $options, $btn_class);
        } else {
            $html[] = '<a href="#" onclick="return false;" class="' . $btn_class . ' ' . DRTS_BS_PREFIX . 'disabled"><i class="fas ' . $first_icon . '"></i></a>';
        }
        if ($min > 1) {
            $html[] = $this->_getPageLink($application, 1, 1, $link_url, $ajax_url, $options, $btn_class);
            if ($min > 2) $html[] = '<a href="#" onclick="return false;" class="' . $btn_class . ' ' . DRTS_BS_PREFIX . 'disabled">...</a>';
        }
        for ($i = $min; $i <= $max; $i++) {
            $html[] = ($i == $current_page) ? $current_html : $this->_getPageLink($application, $i, $i, $link_url, $ajax_url, $options, $btn_class);
        }
        if ($max < $page_count) {
            if ($page_count - $max > 1) $html[] = '<a href="#" onclick="return false;" class="' . $btn_class . ' ' . DRTS_BS_PREFIX . 'disabled">...</a>';
            $html[] = $this->_getPageLink($application, $page_count, $page_count, $link_url, $ajax_url, $options, $btn_class);
        }
        if ($current_page != $page_count) {
            $icon = $application->getPlatform()->isRtl() ? 'fa-angle-double-left' : 'fa-angle-double-right';
            $html[] = $this->_getPageLink($application, '<i class="fas ' . $last_icon . '"></i>', $current_page + 1, $link_url, $ajax_url, $options, $btn_class);
        } else {
            $html[] = '<a href="#" onclick="return false;" class="' . $btn_class . ' ' . DRTS_BS_PREFIX . 'disabled"><i class="fas ' . $last_icon . '"></i></a>';
        }

        return sprintf('<div class="drts-pagination %sbtn-group %s">%s</div>', DRTS_BS_PREFIX, $options['class'], implode('', $html));
    }

    private function _getPageLink(Application $application, $text, $page, $linkUrl, $ajaxUrl, array $options, $class)
    {
        $url = clone $linkUrl;
        $url['params'] = [$p = $application->getPlatform()->getPageParam() => $page] + $url['params'];
        if (isset($ajaxUrl)) {
            $ajax_url = clone $ajaxUrl;
            $ajax_url['params'] = [$p => $page] + $ajax_url['params'];
        } else {
            $ajax_url = $url;
        }
        $options['url'] = $ajax_url;

        return $application->LinkTo($text, $url, $options, ['class' => $class]);
    }
}