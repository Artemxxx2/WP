<?php
namespace SabaiApps\Directories\Component\DirectoryPro\Controller\Admin;

use SabaiApps\Directories\Component\Form;
use SabaiApps\Directories\Context;

class ExportBundle extends Form\Controller
{
    protected function _doGetFormSettings(Context $context, array &$storage)
    {
        $this->_submitable = false;
        
        foreach ($this->DirectoryPro_ExportBundle($context->bundle, true) as $key => $arr) {
            $ret[$key] = [
                '#title' => $arr['title'],
                '#type' => 'textarea',
                '#default_value' => '<?php' . PHP_EOL . 'return ' . strtr(var_export($arr['data'], true), $arr['placeholders']) . ';',
                '#rows' => 30,
                '#attributes' => ['style' => 'max-height:500px; overflow:scroll;'],
            ];
        }
        
        return $ret;
    }
}