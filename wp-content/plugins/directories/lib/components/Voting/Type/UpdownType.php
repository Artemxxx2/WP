<?php
namespace SabaiApps\Directories\Component\Voting\Type;

class UpdownType extends AbstractType
{
    protected function _votingTypeInfo()
    {
        return array(
            'label' => __('Votes', 'directories'),
            'label_action' => __('Upvote', 'directories'),
            'label_unaction' => __('Upvote', 'directories'),
            'label_action_down' => __('Downvote', 'directories'),
            'label_unaction_down' => __('Downvote', 'directories'),
            'label_button' => __('Vote up button', 'directories'),
            'label_button_down' => __('Vote down button', 'directories'),
            'label_statistic' => __('Upvote count', 'directories'),
            'label_statistic_down' => __('Downvote count', 'directories'),
            'icon' => 'far fa-thumbs-up',
            'icon_down' => 'far fa-thumbs-down',
            'icon_active' => 'fas fa-thumbs-up',
            'icon_down_active' => 'fas fa-thumbs-down',
            'color' => 'success',
            'color_down' => 'danger',
            'colorable' => false,
            'min' => -1,
            'max' => 1,
            'step' => 1,
            'allow_empty' => false,
            'allow_anonymous' => true,
            'require_permission' => true,
            'require_down_permission' => true,
            'require_own_permission' => true,
            'permission_label' => __('Vote up %s', 'directories'),
            'own_permission_label' => __('Vote up own %s', 'directories'),
            'down_permission_label' => __('Vote down %s', 'directories'),
            'entity_button' => array('voting_updown', 'voting_updown_down'),
            'entity_statistic' => array('voting_updown', 'voting_updown_down'),
            'table_headers' => [
                'author' => __('User', 'directories'),
                'created' => __('Date', 'directories'),
                'value' => '',
            ],
        );
    }
    
    public function votingTypeFormat(array $value, $format = null)
    {
        switch ($format) {
            case 'num':
                return ($value['count'] + $value['sum']) / 2;
            case 'num_down':
                return ($value['count'] - $value['sum']) / 2;
            case 'column':
                return sprintf('%d (%d)', $value['count'], $value['sum']);
            case 'format_down':
                return $this->_application->H(_n('%d vote', '%d votes', ($value['count'] - $value['sum']) / 2, 'directories'));
            default:
                return $this->_application->H(_n('%d vote', '%d votes', ($value['count'] + $value['sum']) / 2, 'directories'));
        }
    }
    
    public function votingTypeTableRow(\SabaiApps\Directories\Component\Voting\Model\Vote $vote, array $tableHeaders)
    {
        $row = parent::votingTypeTableRow($vote, $tableHeaders);
        $row['value'] = '<i class="' . $this->votingTypeInfo($vote->value < 0 ? 'icon_down' : 'icon') . '"></i> ' . $vote->value;
        return $row;
    }
}