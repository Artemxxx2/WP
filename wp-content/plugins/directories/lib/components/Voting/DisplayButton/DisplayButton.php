<?php
namespace SabaiApps\Directories\Component\Voting\DisplayButton;

use SabaiApps\Directories\Component\Display;
use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Application;
use SabaiApps\Directories\Request;

class DisplayButton extends Display\Button\AbstractButton
{
    protected $_type, $_downVote = false;
    
    public function __construct(Application $application, $name)
    {
        parent::__construct($application, $name);
        if (substr($this->_name, -5) === '_down') {
            $this->_downVote = true;
            $this->_type = substr($this->_name, 7, -5); // remove voting_ prefix and _down suffix
        } else {
            $this->_type = substr($this->_name, 7); // remove voting_ prefix
        }
    }
    
    protected function _displayButtonInfo(Entity\Model\Bundle $bundle)
    {
        $info = $this->_application->Voting_Types_impl($this->_type)->votingTypeInfo();
        $color_key = $this->_downVote ? 'color_down' : 'color';
        return [
            'label' => $this->_downVote ? $info['label_button_down'] : $info['label_button'],
            'default_settings' => [
                '_color' => isset($info[$color_key]) ? $info[$color_key] : 'outline-secondary',
                'show_count' => false,
                'show_login' => true,
                '_icon' => $this->_downVote ? $info['icon_down'] : $info['icon'] // required for overlay button
            ],
            'labellable' => false,
            'iconable' => false,
        ];
    }
    
    public function displayButtonSettingsForm(Entity\Model\Bundle $bundle, array $settings, array $parents = [])
    {
        return [
            'show_count' => [
                '#type' => 'checkbox',
                '#title' => __('Show count', 'directories'),
                '#default_value' => !empty($settings['show_count']),
                '#horizontal' => true,
                '#states' => [
                    'visible' => [
                        sprintf('[name="%s[_hide_label]"]', $this->_application->Form_FieldName($parents)) => ['type' => 'checked', 'value' => false],
                    ],
                ],
                '#weight' => 1,
            ],
            'show_login' => [
                '#type' => 'checkbox',
                '#title' => __('Show link to login/registration for guest users without permission', 'directories'),
                '#default_value' => !isset($settings['show_login']) || !empty($settings['show_login']),
                '#horizontal' => true,
                '#weight' => 5,
            ],
        ] + $this->_application->Voting_Types_impl($this->_type)->votingTypeButtonSettingsForm($bundle, $settings, $parents);
    }
    
    public function displayButtonLink(Entity\Model\Bundle $bundle, Entity\Type\IEntity $entity, array $settings, $displayName)
    {
        $class = $settings['_class'] . ' drts-voting-button';
        $type_info = $this->_application->Voting_Types_impl($this->_type)->votingTypeInfo();
        if ($this->_downVote) {
            $label_action = $type_info['label_action_down'];
            $label_unaction = $type_info['label_unaction_down'];
            $icon = $type_info['icon_down'];
            $active_icon = isset($type_info['icon_down_active']) ? $type_info['icon_down_active'] : $icon;
        } else {
            $label_action = $type_info['label_action'];
            $label_unaction = $type_info['label_unaction'];
            $icon = $type_info['icon'];
            $active_icon = isset($type_info['icon_active']) ? $type_info['icon_active'] : $icon;
        }

        if (!$this->_application->Voting_CanVote($entity, $this->_type, $this->_downVote)) {
            if (!$this->_application->getUser()->isAnonymous()
                || (isset($settings['show_login']) && !$settings['show_login'])
            ) return;

            return $this->_getLoginButton(
                $this->_getLabelHtml($entity, $settings, $label_action),
                $this->_getVoteUrl($entity, $settings, false),
                ['no_escape' => true, 'icon' => $icon, 'no_wrap_label' => true],
                ['class' => $settings['_class'], 'style' => $settings['_style']]
            );
        }

        if ($this->_application->getUser()->isAnonymous()
            && !empty($type_info['anonymous_use_cookie'])
        ) {
            $link = $this->_application->LinkTo(
                $this->_getLabelHtml($entity, $settings, $label_action),
                '',
                [
                    'icon' => $icon,
                    'no_escape' => true,
                    'btn' => true,
                    'no_wrap_label' => true,
                ],
                [
                    'class' => $class,
                    'style' => $settings['_style'],
                    'rel' => 'nofollow',
                    'data-label' => $label_action,
                    'data-label-active' => $label_unaction,
                    'data-voting-type' => $this->_type,
                    'data-voting-icon-active' => $active_icon,
                    'data-voting-icon' => $icon,
                    'data-voting-guest' => 1,
                    'data-entity-id' => $entity->getId(),
                    'data-entity-type' => $entity->getType(),
                ]
            );
        } else {
            $active = false;
            $value = $this->_downVote ? -1 : 1;
            if ((null !== $current_value = $this->_application->Voting_Votes($entity->getId(), $this->_type))
                && intval($current_value) === $value
            ) {
                $active = true;
                $class .= ' ' . DRTS_BS_PREFIX . 'active';
            }
            $link = $this->_application->LinkTo(
                $this->_getLabelHtml($entity, $settings, $active ? $label_unaction : $label_action),
                '',
                [
                    'container' => '',
                    'icon' => $active ? $active_icon : $icon,
                    'url' => $this->_getVoteUrl($entity, $settings),
                    'post' => true,
                    'loadingImage' => false,
                    'sendData' => 'DRTS.Voting.onSendData("' . $this->_type . '", trigger, data);',
                    'success' => 'DRTS.Voting.onSuccess("' . $this->_type . '", trigger, result);',
                    'error' => 'DRTS.Voting.onError("' . $this->_type . '", trigger, error);',
                    'no_escape' => true,
                    'btn' => true,
                    'no_wrap_label' => true,
                ],
                [
                    'class' => $class,
                    'style' => $settings['_style'],
                    'rel' => 'nofollow',
                    'data-success-label' => $active ? $label_action : $label_unaction,
                    'data-active-value' => $this->_downVote ? -1 : 1,
                    'data-voting-type' => $this->_type,
                    'data-voting-icon-active' => $active_icon,
                    'data-voting-icon' => $icon,
                ]
            );
        }

        $this->_application->Voting_Types_impl($this->_type)->votingTypeOnDisplayButtonLink($link, $entity, $settings, $displayName);

        return $link;
    }
    
    protected function _getVoteUrl(Entity\Type\IEntity $entity, array $settings, $withToken = true)
    {
        $params = ['value' => $this->_downVote ? -1 : 1];
        if ($withToken) {
            $params[Request::PARAM_TOKEN] = $this->_application->Form_Token_create('voting_vote_entity', 1800, true);
        }
        return $this->_application->Entity_Url($entity, '/vote/' . $this->_type,  $params);
    }
    
    protected function _getLabelHtml(Entity\Type\IEntity $entity, array $settings, $label)
    {
        $html = '<span class="drts-voting-vote-label">' . $this->_application->H($label) . '</span>';
        if (!empty($settings['show_count'])) {
            if ($val = $entity->getSingleFieldValue('voting_' . $this->_type, '')) {
                $num = $this->_application->Voting_Types_impl($this->_type)->votingTypeFormat($val, $this->_downVote ? 'num_down' : 'num');
            } else {
                $num = 0;
            }
            $margin_x_class = $this->_application->getPlatform()->isRtl() ? DRTS_BS_PREFIX . 'mr-2' : DRTS_BS_PREFIX . 'ml-2';
            $html .= '<span class="drts-voting-vote-num ' . $margin_x_class . '">' . $num . '</span>';
        }
        return $html;
    }
    
    public function displayButtonIsPreRenderable(Entity\Model\Bundle $bundle, array $settings)
    {
        return true;
    }
    
    public function displayButtonPreRender(Entity\Model\Bundle $bundle, array $settings, array $entities)
    {
        $this->_application->getPlatform()
            ->addJsFile('voting.min.js', 'drts-voting', array('drts'), 'directories');
        $this->_application->Voting_Votes_load($bundle->name, array_keys($entities));
    }
}
