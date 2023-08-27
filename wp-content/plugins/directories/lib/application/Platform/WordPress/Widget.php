<?php
namespace SabaiApps\Directories\Platform\WordPress;

class Widget extends \WP_Widget
{
    protected $_widgetName;

    public function __construct($widgetName, $widgetTitle, $widgetSummary)
    {
        $options = array('description' => $widgetSummary);
        parent::__construct(false, sprintf('Directories: %s', $widgetTitle), $options);
        $this->_widgetName = $widgetName;
    }
    
    /**
     * Call an application helper
     */
    public function __call($name, $args)
    {
        return Platform::getInstance()->getApplication()->callHelper($name, $args);
    }

    function widget($args, $instance)
    {
        if (!$content = Platform::getInstance()->getApplication()->System_Widgets_renderWidget($this->_widgetName, $instance)) return;

        // Display content
        echo $args['before_widget'];
        if (strlen($instance['_title_'])) {
            echo $args['before_title'];
            echo esc_html($instance['_title_']);
            echo $args['after_title'];
        }
        echo $content;
        echo $args['after_widget'];
        echo Platform::getInstance()->getJsHtml();        
    }

    function update($new_instance, $old_instance)
    {
        if ($widget = Platform::getInstance()->getApplication()->System_Widgets_widget($this->_widgetName)) {
            $widget->systemWidgetOnSettingsSaved($new_instance, $old_instance);
        }
        $new_instance['filter'] = false;
        
        return $new_instance;
    }

    function form($instance)
    {        
        if (!$widget = Platform::getInstance()->getApplication()->System_Widgets_widget($this->_widgetName)) return;

        // Get additional settings
        $elements = array('#token' => false, '#build_id' => false);
        if ($settings = $widget->systemWidgetSettings($instance)) {
            foreach ($settings as $key => $data) {
                if ($data['#type'] === 'checkbox') {
                    $default_value = isset($instance[$key]) && is_array($instance[$key]) && array_shift($instance[$key]) ? true : false;
                    $data['#switch'] = false;
                } else {
                    $default_value = array_key_exists($key, $instance) ? $instance[$key] : @$settings[$key]['#default_value'];
                }
                $elements[$this->get_field_name($key)] = array_merge(
                    $data,
                    array(
                        '#type' => @$data['#type'],
                        '#title' => isset($data['#title']) ? $data['#title'] : null,
                        '#description' => isset($data['#description']) ? $data['#description'] : null,
                        '#default_value' => $default_value,
                    )
                );
            }
        }
        $elements[$this->get_field_name('_title_')] = array(
            '#title' => __('Title', 'directories'),
            '#type' => 'textfield',
            '#default_value' => isset($instance['_title_'])
                ? $instance['_title_']
                : $widget->systemWidgetInfo('title'),
            '#weight' => -1,
        );
        Platform::getInstance()->loadDefaultAssets(true, false);
        $form = Platform::getInstance()->getApplication()->Form_Build($elements)->render();
        echo '<div class="drts">';
        echo $form->getHtml();
        echo $form->getHiddenHtml();
        echo '</div>';
    }
}