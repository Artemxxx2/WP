<?php
namespace SabaiApps\Directories\Component\Voting\Controller;

use SabaiApps\Directories\Controller;
use SabaiApps\Directories\Context;
use SabaiApps\Directories\Exception;
use SabaiApps\Directories\Request;

class VoteEntity extends Controller
{
    protected function _doExecute(Context $context)
    {
        if (!$context->getRequest()->isPostMethod()) {
            if ($this->getUser()->isAnonymous()) {
                $context->setBadRequestError();
                return;
            }
        }

        // Check request token
        if (!$this->_checkToken($context, 'voting_vote_entity', true)) {
            if ($this->getUser()->isAnonymous()
                || (false === $value = $this->_canVote($context))
            ) return;
            
            // Probably coming from login redirect, so show a voting button with token
            $voting_type = $this->Voting_Types_impl($context->voting_type);
            $label = $voting_type->votingTypeInfo($value < 0 ? 'label_unaction' : 'label_action');
            $context->setView()->setInfo($label)->setAttributes([
                'content' => $this->LinkTo(
                    $label,
                    $this->Url($context->getRoute(), [
                        Request::PARAM_TOKEN => $this->Form_Token_create('voting_vote_entity', 1800, true),
                        'value' => $value,
                        'edit' => 1, // prevent undo'ing vote
                    ]),
                    [
                        'container' => $context->getContainer(),
                        'post' => true,
                        'redirect' => true,
                        'btn' => true,
                        'icon' => $voting_type->votingTypeInfo('icon'),
                    ],
                    [
                        'class' => DRTS_BS_PREFIX . 'btn ' . DRTS_BS_PREFIX . 'btn-primary ' . DRTS_BS_PREFIX . 'btn-lg',
                    ]
                ), 
            ]);
            return;
        }
        
        if (false === $value = $this->_canVote($context)) {
            $context->setError(__('You do not have the permission to perform this action.', 'directories'));
            return;
        }
        
        try {
            // Validate vote
            $voting_type = $this->Voting_Types_impl($context->voting_type);
            $settings = $voting_type->votingTypeInfo();
            $this->_validateVoteValue($settings, $value);
            
            // Cast vote
            $results = $this->Voting_CastVote(
                $context->entity,
                'voting_' . $context->voting_type,
                array('' => $value),
                array(
                    'allow_empty' => !empty($settings['allow_empty']),
                    'allow_multiple' => !empty($settings['allow_multiple']),
                    'edit' => $context->getRequest()->asBool('edit'),
                )
            );
        } catch (Exception\IException $e) {
            $context->setError($e->getMessage());
            return;
        }

        $result = $results[''];
        $attributes = [
            'type' => $context->voting_type,
            'entity_id' => $context->entity->getId(),
            'num' => $voting_type->votingTypeFormat($result, 'num'),
            'num_down' => $voting_type->votingTypeFormat($result, 'num_down'),
        ] + $result;
        $voting_type->votingTypeOnVoteEntity($context->entity, $attributes, $context->getRequest());
        $context->setSuccess($this->Entity_PermalinkUrl($context->entity),  $attributes);
    } 
    
    protected function _canVote(Context $context)
    {
        $value = $context->getRequest()->get('value');
        $is_down_vote = $value < 0;
        if (!$this->Voting_CanVote($context->entity, $context->voting_type, $is_down_vote)) {
            $context->setError(__('You do not have the permission to perform this action.', 'directories'));
            return false;
        }
        return $value;
    }
    
    private function _validateVoteValue(array $settings, $value)
    {
        if (empty($value)
            && empty($settings['allow_empty'])
        ) {
            throw new Exception\UnexpectedValueException('Vote value may not be empty');
        }
        if (empty($value)) return; // empty value is allowed so no further checking should be done

        if (!is_numeric($value)
            || $value > $settings['max']
            || $value < $settings['min']
            || intval(strval($value * 100)) % intval(strval($settings['step'] * 100)) !== 0 // avoid using float numbers for % operation
        ) {
            throw new Exception\UnexpectedValueException('Invalid vote value: ' . (string)$value);
        }
    }
    
    protected function _isEmptyValue(array $settings, $value)
    {
        return empty($value) && empty($settings['allow_empty']);
    }
}