<?php
namespace SabaiApps\Directories\Component\Voting\Controller;

use SabaiApps\Directories\Component\Form;
use SabaiApps\Directories\Context;
use SabaiApps\Directories\Component\Voting\Model\Vote;

abstract class AbstractVotes extends Form\Controller
{    
    protected function _doGetFormSettings(Context $context, array &$formStorage)
    {   
        // Init form
        $form = array(
            'votes' => array(
                '#type' => 'tableselect',
                '#header' => $this->_getHeaders($context),
                '#multiple' => true,
                '#js_select' => true,
                '#options' => [],
                '#class' => 'drts-data-table',
            )
        );
        // Set submit buttons
        $this->_submitButtons = $this->_getSubmitButtons($context);
        
        // Ajax options
        $this->_ajaxSubmit = true;
        $this->_ajaxOnSuccess = 'function (result, target, trigger) {' . $this->_getAjaxOnSuccessJs($context) . '}';
        $this->_ajaxOnSuccessRedirect = false;
        
        // Init variables
        $sortable_headers = $this->_getSortableHeaders($context);
        $timestamp_headers = $this->_getTimestampHeaders($context);
        $sort = $context->getRequest()->asStr('sort', $this->_getDefaultHeader($context), $sortable_headers);
        $order = $context->getRequest()->asStr('order', 'DESC', array('ASC', 'DESC'));
        
        // Set sortable headers
        $this->_makeTableSortable($context, $form['votes'], $sortable_headers, $timestamp_headers, $sort, $order, $this->_getSortableHeaderParams($context));
        
        // Add rows
        foreach ($this->_getVotes($context, 100, 0, $sort, $order) as $vote) {     
            if ($row = $this->_getVoteRow($context, $vote, $form['votes']['#header'])) {
                $form['votes']['#options'][$vote->id] = $row;
            }
        }

        $form['sort'] = array(
            '#type' => 'hidden',
            '#value' => $sort,
        );
        $form['order'] = array(
            '#type' => 'hidden',
            '#value' => $order,
        );

        return $form;
    }
    
    protected function _getAjaxOnSuccessJs(Context $context)
    {
        return 'var table, options;
if (!result.deleted) return;
table = $(".drts-voting-dashboardvotes");
options = {color:"#f8d7da"};
result.deleted.forEach(function (val) {
    if ($.effects && $.effects.effect.highlight) {
        table.find("tr[data-row-id=\'" + val + "\']").find("td").effect("highlight", options, 400).end().effect("highlight", options, 400, function () {
            var $this = $(this);
            $this.fadeTo("fast", 0, function () {
                $this.slideUp("fast", function () {
                    $this.remove();
                });
            });
        });
    } else {
        table.find("tr[data-row-id=\'" + val + "\']").fadeTo("fast", 0, function () {
            var $this = $(this);
            $this.slideUp("fast", function () {
                $this.remove();
            });
        });
    }
});';
    }

    public function submitForm(Form\Form $form, Context $context)
    {
        $attr = [];
        if (!empty($form->values['votes'])) {
            switch ($form->values['_action']) {
                case 'delete':
                    $attr['deleted'] = [];
                    foreach ($votes = $this->getModel('Vote', 'Voting')
                        ->id_in($form->values['votes'])
                        ->fieldName_is('voting_' . $this->_getVotingType($context))
                        ->fetch()
                        ->with('Entity')
                    as $vote) {
                        $entity = $vote->Entity;
                        $vote->markRemoved()->commit();
                        if ($entity) $this->Voting_Votes_recalculate($entity, 'voting_' . $this->_getVotingType($context));
                        $attr['deleted'][] = $vote->id;
                    }                
                    break;
            }
        }
        
        $context->setSuccess($this->_getSuccessUrl($context), $attr);
    }
    
    protected function _getSuccessUrl(Context $context)
    {
        return $context->getRoute();
    }
    
    protected function _getSubmitButtons(Context $context)
    {
        return array(
            '_action' => array(
                '#type' => 'select',
                '#options' => array(
                    '' => __('Bulk Actions', 'directories'),
                    'delete' => __('Delete', 'directories'),
                ),
                '#weight' => 1,
            ),
            'apply' => array(
                '#btn_label' => __('Apply', 'directories'),
                '#btn_size' => 'small',
                '#weight' => 10,
            ),
        );
    }
    
    abstract protected function _getHeaders(Context $context);
    abstract protected function _getDefaultHeader(Context $context);
    
    protected function _getSortableHeaders(Context $context)
    {
        return [];
    }
    
    protected function _getSortableHeaderParams(Context $context)
    {
        return [];
    }
    
    protected function _getTimestampHeaders(Context $context)
    {
        return [];
    }
    
    protected function _getVotes(Context $context, $limit, $offset, $sort, $order)
    {
        return $this->_getQuery($context)->fetch($limit, $offset, $sort, $order);
    }
    
    protected function _getQuery(Context $context)
    {
        return $this->getModel('Vote', 'Voting')->fieldName_is('voting_' . $this->_getVotingType($context));
    }
    
    abstract protected function _getVoteRow(Context $context, Vote $vote, array $tableHeaders);
    
    abstract protected function _getVotingType(Context $context);
}