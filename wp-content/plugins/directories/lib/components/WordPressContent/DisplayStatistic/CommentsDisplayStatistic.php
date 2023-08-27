<?php
namespace SabaiApps\Directories\Component\WordPressContent\DisplayStatistic;

use SabaiApps\Directories\Component\Display;
use SabaiApps\Directories\Component\Entity;

class CommentsDisplayStatistic extends Display\Statistic\AbstractStatistic
{   
    protected function _displayStatisticInfo(Entity\Model\Bundle $bundle)
    {
        return array(
            'label' => __('Comment count', 'directories'),
            'default_settings' => array(
                '_icon' =>  'far fa-comment'
            ),
        );
    }
    
    public function displayStatisticRender(Entity\Model\Bundle $bundle, Entity\Type\IEntity $entity, array $settings)
    {
        return array(
            'number' => $count = ($_count = wp_count_comments($entity->getId())) ? $_count->approved : 0,
            'format' => _n('%d comment', '%d comments', $count, 'directories'),
        );
    }
}