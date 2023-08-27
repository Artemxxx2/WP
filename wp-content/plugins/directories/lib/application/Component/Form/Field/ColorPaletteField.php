<?php
namespace SabaiApps\Directories\Component\Form\Field;

use SabaiApps\Directories\Component\Form\Form;

class ColorPaletteField extends AbstractField
{ 
    public function formFieldRender(array &$data, Form $form)
    {
        if (empty($data['#colors'])) return;
        
        if (!isset($data['#default_value'])) $data['#default_value'] = [];
        
        $html = [];
        $offset = 0;
        while ($colors = array_slice($data['#colors'], $offset, 4)) {
            $html[] = '<div class="' . DRTS_BS_PREFIX . 'btn-group ' . DRTS_BS_PREFIX . 'btn-group-toggle" data-toggle="' . DRTS_BS_PREFIX . 'buttons">';
            foreach ($colors as $color) {
                $checked = in_array($color, $data['#default_value']);
                $html[] = sprintf(
                    '<label class="%1$sbtn %1$sbtn-link drts-form-colorpalette-btn drts-form-colorpalette-btn-%2$s %3$s" title="%2$s" style="background-color:%2$s;">
    <input name="%4$s[]" value="%2$s" type="checkbox" autocomplete="off"%5$s>
    <i class="fas fa-check fa-fw" style="visibility:hidden;"></i>
</label>',
                    DRTS_BS_PREFIX,
                    $this->_application->H($color),
                    $checked ? DRTS_BS_PREFIX . 'active' : '',
                    $data['#name'],
                    $checked ? ' checked="checked"' : ''
                );
            }
            $html[] = '</div>';
            $offset += 4;
        }
        $this->_render(implode(PHP_EOL, $html), $data, $form);
    }
}
