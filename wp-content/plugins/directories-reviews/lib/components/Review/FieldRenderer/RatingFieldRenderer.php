<?php
namespace SabaiApps\Directories\Component\Review\FieldRenderer;

use SabaiApps\Directories\Component\Entity\Type\IEntity;
use SabaiApps\Directories\Component\Field\Renderer\AbstractRenderer;
use SabaiApps\Directories\Component\Field\IField;

class RatingFieldRenderer extends AbstractRenderer
{    
    protected function _fieldRendererInfo()
    {
        return [
            'field_types' => [$this->_name],
            'default_settings' => [
                'format' => 'stars',
                'color' => ['type' => '', 'value' => null],
                'bar_height' => 12,
                'decimals' => 1,
                'inline' => false,
            ],
            'accept_multiple' => true,
            'inlineable' => true,
        ];
    }
    
    protected function _fieldRendererSettingsForm(IField $field, array $settings, array $parents = [])
    {
        return [
            'format' => [
                '#type' => 'select',
                '#title' => __('Display format', 'directories-reviews'),
                '#default_value' => $settings['format'],
                '#horizontal' => true,
                '#options' => $this->_getFormatOptions(),
            ],
            'color' => $this->_application->System_Util_colorSettingsForm($settings['color'], array_merge($parents, ['color'])),
            'decimals' => [
                '#type' => 'select',
                '#title' => __('Decimals', 'directories-reviews'),
                '#options' => [0 => __('0 (no decimals)', 'directories-reviews'), 1 => 1, 2 => 2],
                '#default_value' => $settings['decimals'],
                '#horizontal' => true,
            ],
            'inline' => [
                '#title' => __('Render bar labels inline', 'directories-reviews'),
                '#type' => 'checkbox',
                '#default_value' => !empty($settings['inline']),
                '#states' => [
                    'invisible' => [
                        sprintf('select[name="%s"]', $this->_application->Form_FieldName(array_merge($parents, ['format']))) => ['value' => 'stars'],
                    ],
                ],
                '#horizontal' => true,
            ],
            'bar_height' => $this->_application->Voting_RenderRating_barHeightForm($settings['bar_height']) + [
                '#states' => [
                    'invisible' => [
                        sprintf('select[name="%s"]', $this->_application->Form_FieldName(array_merge($parents, ['format']))) => ['value' => 'stars'],
                    ],
                ],
            ],
        ];
    }
    
    protected function _fieldRendererRenderField(IField $field, array &$settings, IEntity $entity, array $values, $more = 0)
    {
        switch ($settings['format']) {
            case 'stars':
                return $this->_renderRatingStars($field, $settings, $values);
            case 'bars':
                return $this->_renderRatingBars($field, $settings, $values);
        }
    }
    
    protected function _renderRatingStars(IField $field, array $settings, array $values)
    {
        if (null === $value = $this->_getRatingValue($field, $settings, $values)) return;
        
        return $this->_application->Voting_RenderRating($value, [
            'color' => $settings['color'],
            'decimals' => $settings['decimals'],
            'count' => $this->_getRatingCount($field, $settings, $values),
        ]);
    }
    
    protected function _renderRatingBars(IField $field, array $settings, array $values)
    {
        if (!$review_bundle = $this->_getReviewBundle($field)) return;

        if (null === $this->_getRatingValue($field, $settings, $values)) return;
        
        $options = [
            'color' => $settings['color'],
            'height' => $settings['bar_height'],
            'decimals' => $settings['decimals'],
            'inline' => $settings['inline'],
        ];
        $html = [];
        $criteria = $this->_application->Review_Criteria($review_bundle);
        foreach (array_keys($criteria) as $slug) {
            $value = $this->_getRatingValue($field, $settings, $values, $slug);

            $html[] = '<div class="drts-col-12 drts-col-md-6 drts-col-xl-4">'
                . $this->_application->Voting_RenderRating_bar($value, $criteria[$slug], $options)
                . '</div>';
        }
        return empty($html) ? '' : '<div class="drts-row drts-gutter-md">' . implode(PHP_EOL, $html) . '</div>';
    }
    
    protected function _getReviewBundle(IField $field)
    {
        return $field->Bundle;
    }
    
    protected function _getRatingValue(IField $field, array $settings, array $values, $slug = null)
    {
        if (!isset($slug)) $slug = '_all';
        return isset($values[0][$slug]['value']) ? $values[0][$slug]['value'] : null;
    }
    
    protected function _getRatingCount(IField $field, array $settings, array $values, $slug = null)
    {
        return; // do not show count
    }
    
    protected function _getFormatOptions()
    {
        return [
            'stars' => __('Rating stars', 'directories-reviews'),
            'bars' => __('Rating bars', 'directories-reviews')
        ];
    }
    
    protected function _fieldRendererReadableSettings(IField $field, array $settings)
    {
        $ret = [
            'format' => [
                'label' => __('Display format', 'directories-reviews'),
                'value' => $this->_getFormatOptions()[$settings['format']],
            ],
            'color' => [
                'label' => __('Color', 'directories-reviews'),
                'value' => empty($settings['color']['type']) ? __('Default', 'directories-reviews') : $settings['color']['value'],
            ],
            'decimals' => [
                'label' => __('Decimals', 'directories-reviews'),
                'value' => $settings['decimals'],
            ],
        ];
        if (strpos($settings['format'], 'bar') === 0) {
            $ret['inline'] = [
                'label' => __('Render bar labels inline', 'directories-reviews'),
                'value' => !empty($settings['inline']),
                'is_bool' => true,
            ];
            $ret['bar_height'] = [
                'label' => __('Bar height', 'directories-reviews'),
                'value' => $settings['bar_height'] . 'px',
            ];
        }
        return $ret;
    }
}