<?php
namespace SabaiApps\Directories\Component\Voting\FieldWidget;

use SabaiApps\Directories\Component\Field;
use SabaiApps\Directories\Component\Entity;

class FieldWidget extends Field\Widget\AbstractWidget
{
    protected function _fieldWidgetInfo()
    {
        return [
            'field_types' => [$this->_name],
        ];
    }

    public function fieldWidgetForm(Field\IField $field, array $settings, $value = null, Entity\Type\IEntity $entity = null, array $parents = [], $language = null)
    {
        if (!isset($entity)
            || strpos($field->getFieldName(), 'voting_') !== 0
        ) return;

        $type = substr($field->getFieldName(), strlen('voting_'));
        if (!$type_impl = $this->_application->Voting_Types_impl($type, true)) return;

        $table_headers = $type_impl->votingTypeInfo('table_headers');
        $votes = $this->_application->getModel('Vote', 'Voting')
            ->fieldName_is($field->getFieldName())
            ->entityId_is($entity->getId())
            ->fetch();
        $options = [];
        foreach ($votes as $vote) {
            $options[] = $type_impl->votingTypeTableRow($vote, $table_headers);
        }
        if (empty($options)) {
            $value = $entity->getSingleFieldValue($field->getFieldName());
            if (!isset($value[''])) return;

            if (empty($value['']['count'])
                && empty($value['']['sum'])
                && empty($value['']['average'])
            ) return;
        }

        $admin_path = strtr(
            $this->_application->Entity_BundleTypeInfo($field->Bundle, 'admin_path'),
            [
                ':bundle_name' => $field->Bundle->name,
                ':directory_name' => $field->Bundle->group,
                ':bundle_group' => $field->Bundle->group,
            ]
        );
        $form = [
            '#disabled' => true, // disable so that not values are submitted
            '#group' => true,
            '#class' => 'drts-voting-' . $field->getFieldName() . '-' . $entity->getId(), // for ajax delete target
            'clear' => [
                '#type' => 'markup',
                '#weight' => 2,
                '#markup' => $this->_application->LinkTo(
                    __('Clear All', 'directories'),
                    $this->_application->AdminUrl(
                        $admin_path . '/votes/clear',
                        [
                            'entity_id' => $entity->getId(),
                            'field_name' => $field->getFieldName(),
                        ],
                        '',
                        '&'
                    ),
                    ['container' => 'modal', 'modalSize' => 'lg'],
                    [
                        'class' => DRTS_BS_PREFIX . 'btn ' . DRTS_BS_PREFIX . 'btn-link ' . DRTS_BS_PREFIX . 'btn-sm ' . DRTS_BS_PREFIX . 'text-danger',
                        'data-modal-title' => $field->getFieldLabel() . ' - ' . __('Clear All', 'directories'),
                    ]
                ),
            ],
        ];
        if (!empty($options)) {
            $form['votes'] = [
                '#type' => 'tableselect',
                '#weight' => 1,
                '#header' => $table_headers,
                '#options' => $options,
                '#disabled' => true,
                '#class' => 'drts-data-table',
                '#size' => 'sm',
            ];
        } else {
            if (isset($value['']['count'])) {
                $form['votes'] = [
                    '#type' => 'markup',
                    '#weight' => 1,
                    '#markup' => '<div>' . sprintf($type_impl->votingTypeFormat($value['']), $value['']['count']) . '</div>',
                ];
            }
        }

        return $form;
    }
}
