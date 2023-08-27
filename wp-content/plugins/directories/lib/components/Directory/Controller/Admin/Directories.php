<?php
namespace SabaiApps\Directories\Component\Directory\Controller\Admin;

use SabaiApps\Directories\Context;
use SabaiApps\Directories\Component\Form;
use SabaiApps\Directories\Component\Directory\Model\Directory;

class Directories extends Form\Controller
{
    protected function _doGetFormSettings(Context $context, array &$storage)
    {
        // Load scripts for add directory modal form
        $this->Form_Scripts(array('iconpicker', 'latinise', 'file', 'select'));
        $this->getPlatform()->loadJqueryUiJs(array('effects-highlight'));

        $form = array(
            '#js_ready' => 'var params = {}; params[DRTS.params.ajax] = "#drts-modal"; $.get("' . $this->Url('/directories/add') . '", params, function (_data) {
                DRTS.cache("drts-directory-add-directory", _data);
            });',
            'directories' => array(
                '#type' => 'tableselect',
                '#header' => array(
                    'name' => __('Directory name', 'directories'),
                    'type' => __('Directory type', 'directories'),
                    'date' => _x('Created', 'date directory created', 'directories'),
                    'links' => array('label' => '', 'order' => 99),
                ),
                '#disabled' => true,
                '#multiple' => true,
                '#js_select' => true,
                '#options' => [],
                '#class' => 'drts-data-table',
                '#row_attributes' => array(
                    '@all' => array(
                        'name' => array(
                            'style' => 'width:30%;',
                        ),
                        'type' => array(
                            'style' => 'width:20%;',
                        ),
                        'date' => array(
                            'style' => 'width:20%;',
                        ),
                        'links' => array(
                            'style' => 'white-space:nowrap;text-align:' . ($this->getPlatform()->isRtl() ? 'left' : 'right') . ';',
                        ),
                    ),
                ),
            ),
        );

        $directory_types = [];
        foreach ($this->getModel('Directory', 'Directory')->fetch(0, 0, array('directory_type', 'directory_name'), array('ASC', 'ASC')) as $directory) {
            if (!$this->Directory_Types_impl($directory->type, true)
                || !$this->HasPermission('directory_admin_directory_' . $directory->name)
            ) continue;

            $icon_html = '';
            if ($icon = $directory->getIcon()) {
                $icon_html = '<i class="drts-icon ' . $this->H($icon) . '"></i> ';
            }
            $visit_link = $this->LinkTo(
                '',
                $this->MainUrl('/' . $this->getComponent('Directory')->getSlug($directory->name)),
                array('icon' => 'fas fa-external-link-alt'),
                array(
                    'rel' => 'sabaitooltip',
                    'title' => __('Visit Directory', 'directories'),
                )
            );
            $form['directories']['#options'][$directory->name] = array(
                'name' => $icon_html . '<span>' . $this->H($directory->getLabel()). ' <small>(' . $this->H($directory->name) . ')</small> ' . $visit_link . '</span>',
                'type' => $this->H($this->Directory_Types_impl($directory->type)->directoryInfo('label')),
                'date' => $this->System_Date($directory->created),
                'links' => $this->ButtonLinks(
                    $this->_getLinks($directory),
                    array('label' => true, 'right' => true, 'color' => 'outline-secondary', 'btn' => false, 'split' => true)
                ),
            );
            $directory_types[$directory->type] = $directory->type;
        }
        // Hide directory type column if only single type enabled
        if (count($directory_types) === 1) {
            unset($form['directories']['#header']['type']);
            $form['directories']['#row_attributes']['@all']['name']['style'] = 'width:40%;';
            $form['directories']['#row_attributes']['@all']['date']['style'] = 'width:25%;';
        }

        return $form;
    }

    protected function _getLinks(Directory $directory)
    {
        $links = array(
            'settings' => array(
                'link' => array(
                    $this->LinkTo(
                        '',
                        '/directories/' . $directory->name,
                        array('icon' => 'fas fa-cog'),
                        array(
                            'data-original-title' => __('Settings', 'directories'),
                            'rel' => 'sabaitooltip',
                        )
                    ),
                    $this->LinkTo(
                        __('Content Types', 'directories'),
                        '/directories/' . $directory->name . '/content_types'
                    ),
                ),
                'weight' => 50,
            ),
        );
        if ($this->getUser()->isAdministrator()) {
            $links['delete'] = array(
                'link' => $this->LinkTo(
                    '',
                    $this->Url('/directories/' . $directory->name . '/delete'),
                    array('icon' => 'fas fa-trash-alt', 'btn' => true, 'container' => 'modal', 'modalSize' => 'lg'),
                    array(
                        'class' => 'drts-bs-btn-outline-danger',
                        'title' => $title = __('Delete Directory', 'directories'),
                        'data-modal-title' => $title . ' - ' . $directory->getLabel(),
                        'rel' => 'sabaitooltip',
                    )
                ),
                'weight' => 99,
            );
        }
        $links = $this->Filter('directory_admin_directory_links', $links, array($directory));
        ksort($links['settings']['link']);

        // Sort
        uasort($links, function($a, $b) { return $a['weight'] < $b['weight'] ? -1 : 1;});
        // Remove weight
        foreach (array_keys($links) as $key) {
            $links[$key] = $links[$key]['link'];
        }

        return $links;
    }
}
