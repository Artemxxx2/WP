<?php
namespace SabaiApps\Directories\Component\Voting\Type;

class RatingType extends AbstractType
{
    protected function _votingTypeInfo()
    {
        return array(
            'label' => __('Ratings', 'directories'),
            'label_field' => __('Rating', 'directories'),
            'label_action' => _x('Rate', 'action', 'directories'),
            'label_unaction' => _x('Unstar', 'action', 'directories'),
            'label_statistic' => __('Rating count', 'directories'),
            'icon' => 'far fa-star',
            'icon_active' => 'fas fa-star',
            'min' => 1,
            'max' => 5,
            'step' => 0.1,
            'allow_empty' => false,
            'allow_anonymous' => true,
            'require_permission' => true,
            'require_down_permission' => false,
            'require_own_permission' => true,
            'permission_label' => __('Rate %s', 'directories'),
            'own_permission_label' => __('Rate own %s', 'directories'),
            'entity_statistic' => true,
            'table_headers' => [
                'author' => __('User', 'directories'),
                'created' => __('Date', 'directories'),
                'value' => __('Rating', 'directories'),
            ],
        );
    }
    
    public function votingTypeFormat(array $value, $format = null)
    {
        switch ($format) {
            case 'num':
                return $value['count'];
            case 'column':
                return sprintf('%d (%04.2f)', $value['count'], $value['average']);
            default:
                return $this->_application->H(_n('%d rating', '%d ratings', $value['count'], 'directories'));
        }
    }
}