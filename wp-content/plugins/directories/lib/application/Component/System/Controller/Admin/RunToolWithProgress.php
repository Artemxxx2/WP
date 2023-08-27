<?php
namespace SabaiApps\Directories\Component\System\Controller\Admin;

use SabaiApps\Directories\Context;
use SabaiApps\Directories\Component\Form;

class RunToolWithProgress extends Form\AbstractMultiStepController
{
    protected $_storageAsOption = true;

    protected function _getSteps(Context $context, array &$formStorage)
    {
        // For backward compat with <1.2.36 where there was no access callback for this route
        if (!isset($context->tool)) {
            if ((!$tool = $context->getRequest()->asStr('tool'))
                || !$this->_application->System_Tools_impl($tool)
            ) return;

            $context->tool = $tool;
        }

        return ['run' => []];
    }

    protected function _getInitMessage($tool)
    {
        if (!$msg = $this->System_Tools_impl($tool)->systemToolInfo('init_message')) {
            $msg = sprintf(
                __('Running tool (%s)...', 'directories'),
                $this->System_Tools_impl($tool)->systemToolInfo('label')
            );
        }
        return $msg;
    }

    public function _getFormForStepRun(Context $context, array &$formStorage)
    {
        $this->_initProgress($context, $this->_getInitMessage($context->tool));
        $this->_submitButtons[] = [
            '#btn_label' => __('Run Tool', 'directories'),
            '#btn_color' => 'primary',
            '#btn_size' => 'lg',
            '#attributes' => ['data-modal-title' => 'false'],
        ];

        $form = [
            '#header' => [
                [
                    'level' => 'info',
                    'message' => __('This may take a while to complete, please do not close the window or click other buttons while running the tool.', 'directories'),
                ]
            ],
            'tool' => [
                '#type' => 'hidden',
                '#default_value' => $context->tool,
            ],
            'redirect' => [
                '#type' => 'hidden',
                '#default_value' => $context->getRequest()->asStr('redirect'),
            ],
            'settings' => [
                '#tree' => true,
            ],
        ];

        $settings_form = $this->System_Tools_impl($context->tool)->systemToolSettingsForm(['settings']);
        if ($settings_form = $this->Filter('system_tool_settings_form', $settings_form, [$context->tool, ['settings']])) {
            $form['settings'] += $settings_form;
        }

        return $form;
    }

    public function _submitFormForStepRun(Context $context, Form\Form $form)
    {
        error_reporting(0);

        $logs = ['error' => [], 'warning' => [], 'info' => [], 'success' => []];
        $settings = empty($form->values['settings']) ? [] : (array)$form->values['settings'];

        // Init tasks
        if (!isset($form->storage['tasks'])
            || !isset($form->storage['tasks_in_progress'])
        ) {
            $tool_storage = [];
            $tasks = $this->System_Tools_impl($context->tool)->systemToolInit($settings, $tool_storage, $logs);
            if (!$tasks = array_filter((array)$this->Filter('system_tool_tasks', $tasks, [$context->tool, $settings, &$tool_storage, &$logs]))) {
                $form->setError(__('There is no task to run.', 'directories'));
                return;
            }

            $form->storage['tasks'] = $form->storage['tasks_in_progress'] = $tasks;
            $form->storage['tool_storage'] = $tool_storage;

            $this->logDebug($this->_getInitMessage($context->tool));
        }

        // Init current task and iteration
        if (!isset($form->storage['current_task'])
            || !isset($form->storage['current_task_iteration'])
        ) {
            $form->storage['current_task'] = current(array_keys($form->storage['tasks_in_progress']));
            $form->storage['current_task_iteration'] = 0;
        }
        $current_task = $form->storage['current_task'];
        ++$form->storage['current_task_iteration'];

        // Run task
        $result = $this->System_Tools_impl($context->tool)->systemToolRunTask(
            $current_task,
            $settings,
            $form->storage['current_task_iteration'],
            $form->storage['tasks'][$current_task],
            $form->storage['tool_storage'],
            $logs
        );
        $done = (int)$this->Filter(
            'system_tool_task_result',
            $result,
            [$context->tool, $current_task, $settings, $form->storage['current_task_iteration'], $form->storage['tasks'][$current_task], &$form->storage['tool_storage'], &$logs]
        );
        if (empty($done)) {
            $form->setError('An error occurred while executing task: ' . $current_task);
            return;
        }

        // Proceed to next task if current task done
        $done = intval($done);
        if ($done > $form->storage['tasks_in_progress'][$current_task]) {
            $done = $form->storage['tasks_in_progress'][$current_task];
        }
        $form->storage['tasks_in_progress'][$current_task] -= $done;
        if ($form->storage['tasks_in_progress'][$current_task] === 0) {
            unset(
                $form->storage['tasks_in_progress'][$current_task],
                $form->storage['current_task'],
                $form->storage['current_task_iteration']
            );
        }

        // Continue if there are still remaining tasks
        if (!empty($form->storage['tasks_in_progress'])) {
            $total = array_sum($form->storage['tasks']);
            $total_done = $total - array_sum($form->storage['tasks_in_progress']);
            if (!$message = $this->System_Tools_impl($context->tool)->systemToolInfo('progress_message')) {
                if (!$message = $this->System_Tools_impl($context->tool)->systemToolInfo('init_message')) {
                    $message = __('Running tool...', 'directories');
                }
                $message .= ' (%d/%d)';
            }
            $message = sprintf($message, $total_done, $total);
            $this->_isInProgress($context, $total_done, $total, $message, $logs);
            return;
        }

        // Store logs to storage for the _complete() method.
        $form->storage['logs'] = $logs;
    }

    protected function _complete(Context $context, array $formStorage)
    {
        $message = sprintf(
            __('The selected tool (%s) was run successfully.', 'directories'),
            $this->System_Tools_impl($context->tool)->systemToolInfo('label')
        );
        if (empty($formStorage['logs']['error'])
            && ($redirect = $context->getRequest()->asStr('redirect'))
        ) {
            $url = $this->Url($redirect, ['tab' => 'tools']);
        } else {
            $url = false; // false prevents redirection
        }
        $this->_completeProgress($context, $url, $message, $formStorage['logs']);
    }
}