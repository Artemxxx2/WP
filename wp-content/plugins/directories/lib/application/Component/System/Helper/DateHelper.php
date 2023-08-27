<?php
namespace SabaiApps\Directories\Component\System\Helper;

use SabaiApps\Directories\Application;

class DateHelper
{
    public function help(Application $application, $timestamp, $html = false, $format = null)
    {
        return $this->_render($application, isset($format) ? $format : $application->getPlatform()->getDateFormat(), $timestamp, $html);
    }

    public function time(Application $application, $timediff, $html = false, $format = null)
    {
        $timestamp = mktime(0, 0, 0) + $timediff;
        $ret = date(isset($format) ? $format : $application->getPlatform()->getTimeFormat(), $timestamp);
        return $html ? '<time class="drts-datetime">' . $ret . '</time>' : $ret;
    }

    public function datetime(Application $application, $timestamp, $html = false, $format = null)
    {
        if (isset($format)
            && !is_array($format)
        ) $format = [$format];

        $date_format = isset($format[0]) ? $format[0] : $application->getPlatform()->getDateFormat();
        $time_format = isset($format[1]) ? $format[1] : $application->getPlatform()->getTimeFormat();

        return $this->_render(
            $application,
            sprintf(_x('%s %s', 'date/time format', 'directories'), $date_format, $time_format),
            $timestamp,
            $html
        );
    }

    protected function _render(Application $application, $format, $timestamp, $html = false)
    {
        $ret = $application->getPlatform()->getDate($format, $timestamp);
        return $html ? '<time class="drts-datetime" datetime="' . date('c' , $timestamp) . '">' . $ret . '</time>' : $ret;
    }
}
