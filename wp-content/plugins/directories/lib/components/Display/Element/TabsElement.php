<?php
namespace SabaiApps\Directories\Component\Display\Element;

use SabaiApps\Directories\Component\Entity\Model\Bundle;
use SabaiApps\Directories\Component\Entity\Type\IEntity;
use SabaiApps\Directories\Component\Display\Model\Display;

class TabsElement extends AbstractElement
{
    protected function _displayElementInfo(Bundle $bundle)
    {
        return [
            'type' => 'utility',
            'label' => _x('Tabs', 'display element name', 'directories'),
            'description' => 'Adds a horizontal tabbed content area',
            'default_settings' => [
                'accordion' => false,
                'accordion_closed' => false,
            ],
            'containable' => true,
            'child_element_name' => 'tab',
            'child_element_create' => 2,
            'add_child_label' => __('Add Tab', 'directories'),
            'icon' => 'far fa-folder',
            'designable' => ['margin'],
        ];
    }
    
    public function displayElementSettingsForm(Bundle $bundle, array $settings, Display $display, array $parents = [], $tab = null, $isEdit = false, array $submitValues = [])
    {
        $ret = [
            'accordion' => [
                '#type' => 'checkbox',
                '#title' => __('Display tabs as accordion', 'directories'),
                '#weight' => 2,
                '#default_value' => !empty($settings['accordion']),
                '#horizontal' => true,
            ],
            'accordion_closed' => [
                '#type' => 'checkbox',
                '#title' => __('Close all accordion tabs by default', 'directories'),
                '#weight' => 3,
                '#default_value' => !empty($settings['accordion_closed']),
                '#horizontal' => true,
                '#states' => [
                    'visible' => [
                        sprintf('[name="%s"]', $this->_application->Form_FieldName(array_merge($parents, ['accordion']))) => ['type' => 'checked', 'value' => true],
                    ],
                ],
            ],
        ];
        if (!$isEdit) {
            $ret['tabs'] = [
                '#type' => 'options',
                '#title' => __('Tabs', 'directories'),
                '#options' => [
                    'tab1' => __('Tab label', 'directories'),
                    'tab2' => __('Tab label', 'directories'),
                ],
                '#default_value' => ['tab1', 'tab2'],
                '#hide_value' => true,
                '#slugify_value' => true,
                '#multiple' => true,
                '#horizontal' => true,
                '#disable_icon' => true,
                '#weight' => 1,
            ];
        }

        return $ret;
    }
    
    public function displayElementCreateChildren(Display $display, array $settings, $parentId)
    {
        $ret = [];
        if (!empty($settings['tabs']['default'])) {
            foreach ($settings['tabs']['default'] as $tab_name) {
                $ret[] = $this->_application->Display_AdminElement_create($display, 'tab', $parentId, ['settings' => ['label' => $settings['tabs']['options'][$tab_name]]]);
            }
        }
        return $ret;
    }

    public function displayElementRender(Bundle $bundle, array $element, $var)
    {
        if (empty($element['children'])) return;

        $tabs = [];
        foreach ($element['children'] as $child) {
            if (!$content = $this->_application->callHelper('Display_Render_element', [$bundle, $child, $var])) continue;

            $is_active = !isset($is_active);
            $tab_label = $this->_translateString($child['settings']['label'], 'label', $child['_element_id'], 'tab');
            $hash = 'drts-display-element-tabs-tab-' . $child['id'];
            if ($var instanceof IEntity) {
                $hash .= '-' . $var->getId();
                $tab_label = $this->_application->Entity_Tokens_replace($tab_label, $var, true);
            }
            $tabs[$child['id']] = [
                'label' => $tab_label,
                'content' => $content,
                'is_active' => $is_active,
                'hash' => empty($child['settings']['hash']) ? $hash : $child['settings']['hash'],
                'original_hash' => empty($child['settings']['hash']) ? null : $hash,
            ];
        }
        if (empty($tabs)) return;
        
        return empty($element['settings']['accordion']) ? $this->_renderTabs($element, $tabs, $var) : $this->_renderAccordion($element, $tabs, $var);
    }

    protected function _renderAccordion(array $element, array $tabs, $var)
    {
        $accordion_id = 'drts-display-element-tabs-' . $element['id'];
        if ($var instanceof IEntity) {
            $accordion_id .= '-' . $var->getId();
        }
        $ret = ['<div class="' . DRTS_BS_PREFIX . 'accordion" id="' . $accordion_id . '">'];
        $accordion_closed = !empty($element['settings']['accordion_closed']);
        foreach ($tabs as $element_id => $tab) {
            $ret[] = sprintf(
                '<div class="%1$scard">
    <div class="%1$scard-header %1$sm-0">
        <button type="button" class="drts-content-tab %1$sbtn %1$sbtn-link%6$s" data-target="#%8$s" data-toggle="%1$scollapse" id="%10$s-trigger">%3$s</button>
    </div>
    <div id="%8$s" class="%1$scollapse%7$s" data-parent="#%5$s"%9$s>
        <div class="%1$scard-body">
            %4$s
        </div>
    </div>
</div>',
                DRTS_BS_PREFIX,
                $element_id,
                $tab['label'],
                $tab['content'],
                $accordion_id,
                $tab['is_active'] && !$accordion_closed ? '' : ' ' . DRTS_BS_PREFIX . 'collapsed',
                $tab['is_active'] && !$accordion_closed ? ' ' . DRTS_BS_PREFIX . 'show' : '',
                $tab['hash'],
                isset($tab['original_hash']) ? ' data-original-id="' . $tab['original_hash'] . '"' : '',
                isset($tab['original_hash']) ? $tab['original_hash'] : $tab['hash']
            );
        }
        $ret[] = '</div>';

        return implode(PHP_EOL, $ret);
    }

    protected function _renderTabs(array $element, array $tabs, $var)
    {
        $content = [];
        $ret = array('<div class="' . DRTS_BS_PREFIX . 'nav ' . DRTS_BS_PREFIX . 'nav-tabs ' . DRTS_BS_PREFIX . 'mb-4">');
        foreach ($tabs as $element_id => $tab) {
            $ret[] = sprintf(
                '<a href="#" class="drts-content-tab %1$snav-item %1$snav-link %2$s" data-target="#%3$s" data-toggle="%1$stab" id="%5$s-trigger">%4$s</a>',
                DRTS_BS_PREFIX,
                $tab['is_active'] ? DRTS_BS_PREFIX . 'active' : '',
                $tab['hash'],
                $tab['label'],
                isset($tab['original_hash']) ? $tab['original_hash'] : $tab['hash']
            );
            $content[] = sprintf(
                '<div class="%1$stab-pane %1$sfade%2$s" id="%5$s"%6$s>
    %4$s
</div>',
                DRTS_BS_PREFIX,
                $tab['is_active'] ? ' ' . DRTS_BS_PREFIX . 'show ' . DRTS_BS_PREFIX . 'active' : '',
                $element_id,
                $tab['content'],
                $tab['hash'],
                isset($tab['original_hash']) ? ' data-original-id="' . $tab['original_hash'] . '"' : ''
            );
        }
        $ret[] = '</div><div class="' . DRTS_BS_PREFIX . 'tab-content">';
        $ret[] = implode(PHP_EOL, $content);
        $ret[] = '</div>';

        return implode(PHP_EOL, $ret);
    }
    
    protected function _displayElementSupports(Bundle $bundle, Display $display)
    {
        return $display->type !== 'filters';
    }
}
