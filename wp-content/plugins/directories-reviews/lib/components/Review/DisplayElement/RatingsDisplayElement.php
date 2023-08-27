<?php
namespace SabaiApps\Directories\Component\Review\DisplayElement;

use SabaiApps\Directories\Component\Display;
use SabaiApps\Directories\Component\Entity;

class RatingsDisplayElement extends Display\Element\AbstractElement
{
    public function _displayElementInfo(Entity\Model\Bundle $bundle)
    {
        return array(
            'type' => 'content',
            'label' => __('Review Ratings', 'directories-reviews'),
            'description' => __('Shows aggregated review ratings', 'directories-reviews'),
            'default_settings' => array(
                'format' => 'overall',
                'show_review_count' => true,
                'color' => 'warning',
                'bar_height' => 12,
                'decimals' => 1,
                'inline' => false,
            ),
            'icon' => 'far fa-star',
            'designable' => ['margin', 'font'],
        );
    }
    
    public function _displayElementSupports(Entity\Model\Bundle $bundle, Display\Model\Display $display)
    {
        return $display->type === 'entity';
    }
    
    public function displayElementSettingsForm(Entity\Model\Bundle $bundle, array $settings, Display\Model\Display $display, array $parents = [], $tab = null, $isEdit = false, array $submitValues = [])
    {
        $form = array(
            'format' => array(
                '#type' => 'select',
                '#title' => __('Display format', 'directories-reviews'),
                '#default_value' => $settings['format'],
                '#horizontal' => true,
                '#options' => array(
                    'overall' => __('Overall rating', 'directories-reviews'),
                    'bars' => __('Rating bars (by rating criteria)', 'directories-reviews'),
                    'bars_stars' => __('Rating bars (by number of stars)', 'directories-reviews')
                ),
            ),
            'show_review_count' => array(
                '#title' => __('Show review count', 'directories-reviews'),
                '#type' => 'checkbox',
                '#default_value' => !empty($settings['show_review_count']),
                '#horizontal' => true,
                '#states' => array(
                    'visible' => array(
                        sprintf('select[name="%s"]', $this->_application->Form_FieldName(array_merge($parents, array('format')))) => array('value' => 'overall'),
                    ),
                ),
            ),
            'show_review_count_label' => array(
                '#title' => __('Show review count with label', 'directories-reviews'),
                '#type' => 'checkbox',
                '#default_value' => !empty($settings['show_review_count_label']),
                '#horizontal' => true,
                '#states' => array(
                    'visible' => array(
                        sprintf('select[name="%s"]', $this->_application->Form_FieldName(array_merge($parents, array('format')))) => array('value' => 'overall'),
                        sprintf('input[name="%s"]', $this->_application->Form_FieldName(array_merge($parents, array('show_review_count')))) => array('type' => 'checked', 'value' => true),
                    ),
                ),
            ),
            'color' => $this->_application->System_Util_colorSettingsForm($settings['color'], array_merge($parents, ['color'])),
            'inline' => array(
                '#title' => __('Render labels inline', 'directories-reviews'),
                '#type' => 'checkbox',
                '#default_value' => !empty($settings['inline']),
                '#states' => array(
                    'invisible' => array(
                        sprintf('select[name="%s"]', $this->_application->Form_FieldName(array_merge($parents, array('format')))) => array('value' => 'overall'),
                    ),
                ),
                '#horizontal' => true,
            ),
        );
        $form['bar_height'] = $this->_application->Voting_RenderRating_barHeightForm($settings['bar_height']) + array(
            '#states' => array(
                'invisible' => array(
                    sprintf('select[name="%s"]', $this->_application->Form_FieldName(array_merge($parents, array('format')))) => array('value' => 'overall'),
                ),
            ),
        );
        return $form;
    }
    
    public function displayElementRender(Entity\Model\Bundle $bundle, array $element, $var)
    {
        $ratings = $var->getSingleFieldValue('review_ratings');
        if (empty($ratings['_all']['count'])) return;
        
        $settings = $element['settings'];
        switch ($settings['format']) {
            case 'overall':
                $count = null;
                if (!empty($settings['show_review_count'])) {
                    if (empty($settings['show_review_count_label'])) {
                        $count = $ratings['_all']['count'];
                    } else {
                        if ($review_bundle = $this->_application->Entity_Bundle('review_review', $bundle->component, $bundle->group)) {
                            $count = sprintf(
                                _n($review_bundle->getLabel('count'), $review_bundle->getLabel('count2'), $ratings['_all']['count'], 'directories-reviews'),
                                $ratings['_all']['count']
                            );
                        }
                    }
                }
                return $this->_application->Voting_RenderRating($ratings['_all']['average'], array('count' => $count, 'color' => $settings['color']));
            case 'bars':
                if (!$review_bundle = $this->_application->Entity_Bundle('review_review', $bundle->component, $bundle->group)) return;
                
                $html = [];
                $criteria = $this->_application->Review_Criteria($review_bundle);
                $options = array(
                    'color' => $settings['color'],
                    'height' => $settings['bar_height'],
                    'decimals' => $settings['decimals'],
                    'inline' => $settings['inline'],
                );
                foreach (array_keys($criteria) as $slug) {
                    $html[] = $this->_application->Voting_RenderRating_bar(isset($ratings[$slug]['average']) ? $ratings[$slug]['average'] : null, $criteria[$slug], $options);
                }
                return implode(PHP_EOL, $html);
            case 'bars_stars':
                $options = array(
                    'color' => $settings['color'],
                    'height' => $settings['bar_height'],
                    'decimals' => $settings['decimals'],
                    'inline' => $settings['inline'],
                );
                return $this->_application->Voting_RenderRating_barsByLevel($bundle->name, $var->getId(), 'review_ratings', '_all', $options);
        }
    }
}