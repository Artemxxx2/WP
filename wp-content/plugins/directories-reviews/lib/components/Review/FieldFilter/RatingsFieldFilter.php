<?php
namespace SabaiApps\Directories\Component\Review\FieldFilter;

use SabaiApps\Directories\Component\Field;
use SabaiApps\Directories\Component\Voting;

class RatingsFieldFilter extends Voting\FieldFilter\RatingFieldFilter
{
    protected function _fieldFilterInfo()
    {
        $info = parent::_fieldFilterInfo();
        $info['default_settings']['criteria'] = null;
        return $info;
    }

    public function fieldFilterSupports(Field\IField $field)
    {
        return $field->getFieldName() === 'review_ratings';
    }

    public function fieldFilterSettingsForm(Field\IField $field, array $settings, array $parents = [])
    {
        $form = parent::fieldFilterSettingsForm($field, $settings, $parents) + [
            'criteria' => [
                '#type' => 'hidden',
                '#default_value' => null,
            ],
        ];
        if ($review_bundle = $this->_application->Entity_Bundle('review_review', $field->Bundle->component, $field->Bundle->group)) {
            $criteria = $this->_application->Review_Criteria($review_bundle, true);
            if (count($criteria) > 1) {
                $form['criteria'] = [
                    '#type' => 'select',
                    '#title' => __('Rating criteria', 'directories-reviews'),
                    '#options' => [],
                    '#default_value' => $settings['criteria'],
                ];
                foreach ($criteria as $slug => $label) {
                    $form['criteria']['#options'][$slug] = $label;
                }
            }
        }
        return $form;
    }

    protected function _getVoteName(array $settings)
    {
        return empty($settings['criteria']) ? '_all' : $settings['criteria'];
    }
}