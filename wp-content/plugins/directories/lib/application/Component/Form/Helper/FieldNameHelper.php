<?php
namespace SabaiApps\Directories\Component\Form\Helper;

use SabaiApps\Directories\Application;

class FieldNameHelper
{
    /**
     * @param Application $application
     * @param array $names
     * @return string
     */
    public function help(Application $application, array $names)
    {
        if (is_array($names[0])) {
            $ret = $names[0][0];
            unset($names[0][0]);
        } else {
            $ret = array_shift($names);
        }
        $ret = $application->H($ret);
        foreach ($names as $name) {
            if (is_array($name)) {
                foreach ($name as $_name) {
                    $ret .= '[' . $application->H($_name) . ']';
                }
            } else {
                $ret .= '[' . $application->H($name) . ']';
            }
        }

        return $ret;
    }
}