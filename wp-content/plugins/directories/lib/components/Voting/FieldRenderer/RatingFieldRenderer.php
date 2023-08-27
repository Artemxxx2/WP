<?php
namespace SabaiApps\Directories\Component\Voting\FieldRenderer;

use SabaiApps\Directories\Component\Field;
use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Request;

class RatingFieldRenderer extends Field\Renderer\AbstractRenderer
{
    protected static $_count = 0;
    
    protected function _fieldRendererInfo()
    {
        return [
            'field_types' => ['voting_vote'],
            'default_settings' => [
                //'color' => '#edb867',
                'hide_empty' => false,
                'hide_count' => false,
                'read_only' => false,
            ],
            'inlineable' => true,
            'emptiable' => true,
        ];
    }
    
    public function fieldRendererSupports(Entity\Model\Bundle $bundle, Field\IField $field)
    {
        return $field->getFieldName() === 'voting_rating';
    }
    
    protected function _fieldRendererSettingsForm(Field\IField $field, array $settings, array $parents = [])
    {        
        return array(
            //'color' => array(
            //    '#type' => 'colorpicker',
            //    '#title' => __('Star color', 'directories'),
            //    '#default_value' => $settings['color'],
            //    '#horizontal' => true,
            //),
            'hide_empty' => array(
                '#type' => 'checkbox',
                '#title' => __('Hide if no ratings', 'directories'),
                '#default_value' => !empty($settings['hide_empty']),
                '#horizontal' => true,
            ),
            'hide_count' => array(
                '#type' => 'checkbox',
                '#title' => __('Hide count', 'directories'),
                '#default_value' => !empty($settings['hide_count']),
                '#horizontal' => true,
            ),
            'read_only' => array(
                '#type' => 'checkbox',
                '#title' => __('Read only', 'directories'),
                '#default_value' => !empty($settings['read_only']),
                '#horizontal' => true,
            ),
        );
    }

    protected function _fieldRendererRenderField(Field\IField $field, array &$settings, Entity\Type\IEntity $entity, array $values, $more = 0)
    {
        $current = empty($values[0]['']) ? [] : $values[0][''];
        if (empty($current['count'])) {
            if (!empty($settings['hide_empty'])) return;
            
            $current += ['count' => 0, 'average' => 0];
        }
        
        if ($settings['read_only']
            || !$entity->isPublished()
            || $this->_application->Voting_Votes($entity->getId(), 'rating')
            || !$this->_application->Voting_CanVote($entity, 'rating')
        ) {
            return $this->_application->Voting_RenderRating($current['average'], array('count' => $settings['hide_count'] ? null : $current['count']));
        }
        
        $this->_application->getPlatform()
            ->addJsFile('jquery.barrating.min.js', 'jquery-barrating', array('jquery'), 'directories', true, true)
            ->addJsFile('voting-rating.min.js', 'drts-voting-rating', array('jquery-barrating', 'drts'), 'directories');
        
        $id = 'drts-voting-field-rating-' . ++self::$_count;
        
        $level = empty($current['level']) ? 0 : (int)$current['level'];
        $options = ['<option value=""></option>']; // required for 0-stars
        for ($i =1; $i <= 5; ++$i) {
            $options[$i] = sprintf('<option value="%1$d"%2$s>%1$d</option>', $i, $level === $i ? ' selected="selected"' : '');
        }
        $margin_x_class = $this->_application->getPlatform()->isRtl() ? DRTS_BS_PREFIX . 'mr-1' : DRTS_BS_PREFIX . 'ml-1';

        return sprintf(
            '<span class="drts-voting-rating-select" id="%1$s" data-vote-url="%3$s" data-vote-rating="%6$d"><select style="display:none;">%2$s</select></span><span class="drts-voting-rating-average %7$s">%4$04.2f</span>%5$s
',
            $id,
            implode(PHP_EOL, $options),
            $this->_application->Entity_Url(
                $entity,
                '/vote/rating',
                [Request::PARAM_CONTENT_TYPE => 'json', Request::PARAM_TOKEN => $this->_application->Form_Token_create('voting_vote_entity', 3600, true)],
                '',
                '&'
            ),
            $current['average'],
            $settings['hide_count'] ? '' : '<span class="drts-voting-rating-count ' . $margin_x_class . '">' . intval($current['count']) . '</span>',
            $level,
            $margin_x_class,
            Request::isXhr() ? 'jQuery(function($) {' : 'document.addEventListener("DOMContentLoaded", function(event) {'
        );
    }
    
    public function fieldRendererIsPreRenderable(Field\IField $field, array $settings)
    {
        return true;
    }
    
    public function fieldRendererPreRender(Field\IField $field, array $settings, array $entities)
    {
        $this->_application->Voting_Votes_load($field->Bundle->name, array_keys($entities));
    }
    
    protected function _fieldRendererReadableSettings(Field\IField $field, array $settings)
    {
        return [
            'hide_empty' => [
                'label' => __('Hide if no ratings', 'directories'),
                'value' => !empty($settings['hide_empty']),
                'is_bool' => true,
            ],
            'hide_count' => [
                'label' => __('Hide count', 'directories'),
                'value' => !empty($settings['hide_count']),
                'is_bool' => true,
            ],
            'read_only' => [
                'label' => __('Read only', 'directories'),
                'value' => !empty($settings['read_only']),
                'is_bool' => true,
            ],
        ];
    }
}