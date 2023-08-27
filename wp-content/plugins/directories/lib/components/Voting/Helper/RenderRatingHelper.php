<?php
namespace SabaiApps\Directories\Component\Voting\Helper;

use SabaiApps\Directories\Application;

class RenderRatingHelper
{
    public function help(Application $application, $value, array $options = [])
    {
        $options += array(
            'count' => null,
            'color' => null,
            'default_color' => 'warning',
            'decimals' => 1,
            'avg_first' => false,
        );
        $color_class = $color_style = '';
        if (isset($options['color']['type'])
            && $options['color']['type'] === 'custom'
        ) {
            $color_style = 'color:' . $application->H($options['color']['value']) . ';';
        } else {
            $color_class = DRTS_BS_PREFIX . 'text-' . $options['default_color'];
        }
        $rounded = round($value, 1) * 10;
        $remainder = $rounded % 5;
        $rounded -= $remainder;
        if ($remainder > 2) {
            $rounded += 5;
        }
        $stars = '<span class="drts-voting-rating-stars drts-voting-rating-stars-%2$d %5$s" style="%6$s" title="%3$s"></span>';
        if ($options['avg_first']) {
            $format = '<span class="drts-voting-rating-average %1$smr-1">%4$s</span>' . $stars;
        } else {
            $format = $stars . '<span class="drts-voting-rating-average %1$sml-1">%4$s</span>';
        }
        $html = sprintf(
            $format,
            DRTS_BS_PREFIX,
            $rounded,
            $application->H(sprintf(__('%.2f out of 5 stars', 'directories'), $value)),
            number_format($value, $options['decimals']),
            $color_class,
            $color_style
        );
        if (isset($options['count'])) {
            $html .= sprintf('<span class="drts-voting-rating-count %1$sml-1">%2$s</span>', DRTS_BS_PREFIX, $options['count']);
        }
        
        return $html;
    }
    
    public function bar(Application $application, $value, $label = null, array $options = [])
    {
        $options += array(
            'color' => null,
            'default_color' => 'primary',
            'decimals' => 1,
            'show_value' => true,
            'percent' => null,
            'height' => 12,
            'style' => 'margin-bottom:calc(0.5em + %2$dpx);',
            'inline' => false,
        );
        if (isset($options['percent'])) {
            $formatted_value = round($options['percent']) . '%';
        } else {
            $formatted_value = isset($value) ? number_format($value, $options['decimals']) : _x('N/A', 'no rating', 'directories');
        }

        $color_class = $color_style = '';
        if (isset($options['color']['type'])
            && $options['color']['type'] === 'custom'
        ) {
            $color_style = 'background-color:' . $application->H($options['color']['value']) . ';';
        } else {
            $color_class = DRTS_BS_PREFIX . 'bg-' . $options['default_color'];
        }
        return sprintf(
            '<div class="drts-voting-rating-bar %9$s" style="%8$s">
    <div class="drts-voting-rating-bar-title">%2$s</div>
    <div class="drts-voting-rating-bar-progress">
        <div class="%1$sprogress" style="height:%7$dpx;">
            <div class="%1$sprogress-bar %6$s" style="width:%3$d%%;%10$s" role="progressbar" aria-valuenow="%4$d" aria-valuemin="0" aria-valuemax="5"></div>
        </div>
    </div>
    <div class="drts-voting-rating-bar-value">%5$s</div>
</div>',
            DRTS_BS_PREFIX,
            $application->H($label),
            isset($options['percent']) ? $options['percent'] : $value * 20,
            $value,
            $formatted_value,
            $color_class,
            $options['height'],
            isset($options['style']) ? sprintf($options['style'], $options['height'], $options['height'] / 3) : '',
            $options['inline'] ? 'drts-voting-rating-bar-inline' : '',
            $color_style
        );
    }
    
    public function barHeightForm(Application $application, $value = null)
    {
        return array(
            '#title' => __('Bar height', 'directories'),
            '#type' => 'slider',
            '#default_value' => $value,
            '#min_value' => 5,
            '#max_value' => 50,
            '#integer' => true,
            '#step' => 1,
            '#horizontal' => true,
            '#field_suffix' => 'px',
        );
    }
    
    public function barsByLevel(Application $application, $bundleName, $entityId, $fieldName, $name, array $options = [])
    {
        $options += array(
            'default_color' => 'warning',
            'level_max' => 5,
            'style' => 'margin-bottom:0;',
        );
        $counts = $application->getModel(null, 'Voting')->getGateway('Vote')->countByLevel($bundleName, $entityId, $fieldName, $name);
        $num = array_sum($counts);
        $html = [];
        for ($i = $options['level_max']; $i > 0; --$i) {
            $html[] = $this->bar(
                $application,
                $count = isset($counts[$i]) ? $counts[$i] : 0,
                sprintf(_n('%d star', '%d stars', $i, 'directories'), $i),
                array('percent' => $count ? ($count / $num) * 100 : 0) + $options
            );
        }
        return implode(PHP_EOL, $html);
    }
    
    public function options(Application $application, $isSelect = false, $emptyValue = '', $isInclusive = true)
    {
        if (!$isSelect) {
            $class = 'drts-voting-rating-stars';
            if ($application->getPlatform()->isRtl()) {
                $class .= ' drts-voting-rating-stars-rtl';
            }
            $options = array(5 => '<span class="' . $class . ' drts-voting-rating-stars-50"></span>');
            for ($i = 4; $i > 0; --$i) {
                $label = '<span class="' . $class . ' drts-voting-rating-stars-'. $i * 10 .'"></span>';
                if ($isInclusive) {
                    $label = sprintf(__('%s & Up', 'directories'), $label);
                }
                $options[$i] = $label;
            }
            if (isset($emptyValue)) {
                $options[$emptyValue] = _x('Any', 'option', 'directories');
            }
        } else {
            if (isset($emptyValue)) {
                $options = [$emptyValue => _x('Any', 'option', 'directories')];
            }
            $options[5] = sprintf(_n('%d star', '%d stars', 5, 'directories'), 5);
            for ($i = 4; $i > 0; --$i) {
                if ($isInclusive) {
                    $options[$i] = sprintf(__('%d+ stars', 'directories'), $i);
                } else {
                    $options[$i] = sprintf(_n('%d star', '%d stars', $i, 'directories'), $i);
                }
            }
        }
        return $options;
    }
}