<?php
namespace SabaiApps\Directories\Component\Entity\SystemTool;

use SabaiApps\Directories\Component\System\Tool\AbstractTool;
use SabaiApps\Directories\Component\Form;

class GenerateTranslationsSystemTool extends AbstractTool
{
    protected $_perpage = 10;

    protected function _systemToolInfo()
    {
        return [
            'label' => __('Generate translations', 'directories'),
            'description' => __('This tool will generate translations for each content item and copy field values from the original content item.', 'directories'),
            'weight' => 80,
        ];
    }

    public function systemToolSettingsForm(array $parents = [])
    {
        $langs = $this->_application->getPlatform()->getLanguages();
        $form = [
            '#element_validate' => [function(Form\Form $form, &$value, $element) {
                if (!empty($value['bundle'])) $value['bundle'] = array_filter($value['bundle']);
                if (!empty($value['target'])) {
                    $value['target'] = array_filter($value['target']);
                    if (isset($value['target'][$value['source']])) {
                        unset($value['target'][$value['source']]);
                    }
                    $value['actions'] = array_filter($value['actions']);
                }
            }],
            'source' => [
                '#title' => __('Source language', 'directories'),
                '#type' => 'select',
                '#options' => array_combine($langs, $langs),
                '#horizontal' => true,
                '#default_value' => $this->_application->getPlatform()->getCurrentLanguage(),
            ],
            'target' => [
                '#title' => __('Target language', 'directories'),
                '#horizontal' => true,
                '#horizontal_label_padding' => false,
            ],
            'bundle' => [
                '#title' => __('Content type', 'directories'),
                '#horizontal' => true,
                '#horizontal_label_padding' => false,
            ],
            'actions' => [
                'generate' => [
                    '#type' => 'checkbox',
                    '#title' => __('Generate translations', 'directories'),
                    '#horizontal' => true,
                    '#default_value' => true,
                ],
                'copy' => [
                    '#type' => 'checkbox',
                    '#title' => __('Copy field values', 'directories'),
                    '#horizontal' => true,
                    '#default_value' => true,
                ],
                'overwrite' => [
                    '#type' => 'checkbox',
                    '#title' => __('Overwrite field values of existing translations', 'directories'),
                    '#horizontal' => true,
                    '#default_value' => false,
                    '#states' => [
                        'visible' => [
                            sprintf('[name="%s"]', $this->_application->Form_FieldName(array_merge($parents, ['actions', 'copy']))) => ['type' => 'checked', 'value' => true],
                        ],
                    ],
                ],
            ],
            'limit' => [
                '#type' => 'number',
                '#title' => __('Limit to X records (0 for all records)', 'directories'),
                '#default_value' => 0,
                '#min_value' => 0,
                '#integer' => true,
                '#horizontal' => true,
            ],
            'offset' => [
                '#type' => 'number',
                '#title' => __('Skip X records', 'directories'),
                '#default_value' => 0,
                '#min_value' => 0,
                '#integer' => true,
                '#horizontal' => true,
                '#element_validate' => [function(Form\Form $form, &$value, $element) {
                    if (!empty($value)) {
                        if ($value % 10) {
                            $form->setError(__('The number must be divisible by 10.', 'directories'), $element);
                        }
                    }
                }],
            ],
        ];
        foreach ($langs as $lang) {
            $form['target'][$lang] = [
                '#type' => 'checkbox',
                '#title' => $lang,
                '#horizontal' => 4,
                '#states' => [
                    'invisible' => [
                        sprintf('select[name="%s"]', $this->_application->Form_FieldName(array_merge($parents, ['source']))) => ['value' => $lang],
                    ],
                ],
                '#default_value' => true,
            ];
        }
        foreach ($this->_application->Entity_Bundles() as $bundle) {
            if (empty($bundle->info['public'])
                || !empty($bundle->info['is_user'])
                || !$this->_application->getPlatform()->isTranslatable($bundle->entitytype_name, $bundle->name)
            ) continue;

            $form['bundle'][$bundle->name] = [
                '#type' => 'checkbox',
                '#title' => $bundle->getGroupLabel() . ' - ' . $bundle->getLabel(),
                '#horizontal' => 4,
                '#default_value' => true,
            ];
        }

        return $form;
    }

    public function systemToolInit(array $settings, array &$storage, array &$logs)
    {
        $ret = [0 => [], 1 => []];
        if (!empty($settings['bundle'])
            && !empty($settings['source'])
            && !empty($settings['target'])
            && !empty($settings['actions'])
            && in_array($settings['source'], $this->_application->getPlatform()->getLanguages())
        ) {
            foreach (array_keys($settings['bundle']) as $bundle_name) {
                if ((!$bundle = $this->_application->Entity_Bundle($bundle_name))
                    || empty($bundle->info['public'])
                    || !$this->_application->getPlatform()->isTranslatable($bundle->entitytype_name, $bundle->name)
                ) continue;

                $count = $this->_application->Entity_Query($bundle->entitytype_name, $bundle->name)
                    ->fieldIs('status', $this->_application->Entity_Status($bundle->entitytype_name, 'publish'))
                    ->count($settings['source']);
                if (!empty($settings['offset'])) {
                    $count -= $settings['offset'];
                }
                if ($count <= 0) continue;

                if (!empty($settings['limit'])
                    && $count > $settings['limit']
                ) {
                    $count = $settings['limit'];
                }
                $ret[empty($bundle->info['is_taxonomy']) ? 0 : 1][$bundle->name] = $count;
            }
        }
        return $ret[1] + $ret[0]; // generate taxonomy terms first
    }

    public function systemToolRunTask($task, array $settings, $iteration, $total, array &$storage, array &$logs)
    {
        if (!$bundle = $this->_application->Entity_Bundle($task)) return false;

        $perpage = $this->_perpage;
        if (!empty($settings['limit'])) {
            $remaining = $total - (($iteration - 1) * $this->_perpage);
            if ($remaining < $this->_perpage) {
                if ($remaining <= 0) {
                    $perpage = $total;
                } else {
                    $perpage = $remaining;
                }
            }
        }
        if (!empty($settings['offset'])) {
            $iteration += $settings['offset'] / $this->_perpage;
        }
        $paginator = $this->_application->Entity_Query($bundle->entitytype_name, $bundle->name)
            ->fieldIs('status', $this->_application->Entity_Status($bundle->entitytype_name, 'publish'))
            ->sortByField('parent')
            ->sortById()
            ->paginate($perpage, 0, $settings['source'])
            ->setCurrentPage($iteration);
        if (!empty($settings['actions']['copy'])) {
            $storage_fields_key = $bundle->name . '-fields';
            if (empty($storage[$storage_fields_key])) {
                $storage[$storage_fields_key] = [];
                foreach ($this->_application->Entity_Field($bundle->name) as $field) {
                    if ((!$field_type = $this->_application->Field_Type($field->getFieldType(), true))
                        || !$field_type instanceof \SabaiApps\Directories\Component\Field\Type\ICopiable
                    ) continue;

                    $storage[$storage_fields_key][$field->getFieldName()] = $field->getFieldType();
                }
            }
            $fields_copiable = $storage[$storage_fields_key];
        }
        $target_langs = array_keys($settings['target']);
        foreach ($paginator->getElements() as $entity) {
            $properties = null;
            $translations = $this->_application->Entity_Translations($entity, false, null, false);
            $source_field_values = [];
            if (!empty($fields_copiable)) {
                $_source_field_values = $this->_application->Entity_Storage()
                    ->fetchValues($bundle->entitytype_name, [$entity->getId()], array_keys($fields_copiable));
                if (!empty($_source_field_values[$entity->getId()])) {
                    $source_field_values = $_source_field_values[$entity->getId()];
                }
            }
            foreach ($target_langs as $lang) {
                // Create new translation or fetch existing
                if (!isset($translations[$lang])) {
                    // No translation
                    if (empty($settings['actions']['generate'])) continue;

                    // Generate translation
                    if (!isset($properties)) {
                        $properties = [
                            'title' => $entity->getTitle(),
                            'status' => $entity->getStatus(),
                            'author' => $entity->getAuthorId(),
                            'date' => $entity->getTimestamp(),
                            'parent' => $entity->getParentId(),
                            'content' => $entity->getContent(),
                        ];
                        if ($entity->getParentId()) {
                            if (!$parent_entity = $this->_application->Entity_ParentEntity($entity, false)) {
                                $logs['error'][] = sprintf(
                                    'Failed fetching parent entity (ID: %d) for entity (ID: %d, Type: %s, Title: %s), skipping entity',
                                    $entity->getParentId(),
                                    $entity->getId(),
                                    $bundle->getLabel('singular'),
                                    $entity->getTitle()
                                );
                                continue 2; // skip this entity
                            }
                            if (!$parent_translation_ids = $this->_application->Entity_Translations_ids($parent_entity, null, false)) {
                                $logs['error'][] = sprintf(
                                    'Failed fetching translations for parent entity (ID: %d) of entity (ID: %d,  Type: %s, Title: %s), skipping entity',
                                    $entity->getParentId(),
                                    $entity->getId(),
                                    $bundle->getLabel('singular'),
                                    $entity->getTitle()
                                );
                                continue 2;
                            }
                        }
                    }
                    if ($entity->getParentId()) {
                        if (empty($parent_translation_ids[$lang])) {
                            $logs['error'][] = sprintf(
                                'Failed fetching translation (Language: %s) for parent entity (ID: %d) of entity (ID: %d,  Type: %s, Title: %s), skipping language',
                                $lang,
                                $entity->getParentId(),
                                $entity->getId(),
                                $bundle->getLabel('singular'),
                                $entity->getTitle()
                            );
                            continue; // skip this translation
                        }
                        $properties['parent'] = $parent_translation_ids[$lang];
                    }
                    if (!isset($is_unique_slug_required)) {
                        $is_unique_slug_required = $this->_application->Entity_Types_impl($bundle->entitytype_name)->entityTypeInfo('unique_slug');
                    }
                    if ($is_unique_slug_required) {
                        $properties['slug'] = $entity->getSlug() . '-' . $lang;
                    }
                    try {
                        $translation = $this->_application->Entity_Types_impl($bundle->entitytype_name)
                            ->entityTypeCreateEntity($bundle, $properties);
                    } catch (\Exception $e) {
                        $logs['error'][] = sprintf(
                            'Failed creating translation (Language: %s) of entity (ID: %d, Type: %s, Title: %s), Error: %s',
                            $lang,
                            $entity->getId(),
                            $bundle->getLabel('singular'),
                            $entity->getTitle(),
                            $e->getMessage()
                        );
                        continue;
                    }
                    $this->_application->getPlatform()->setTranslations($bundle->entitytype_name, $bundle->name, $settings['source'], $entity->getId(), $lang, $translation->getId());
                    $is_existing = false;

                    // Log
                    $logs['success'][] = sprintf(
                        'Translation of entity (ID: %d, Type: %s, Title: %s) created (ID: %d, Language: %s)',
                        $entity->getId(),
                        $bundle->getLabel('singular'),
                        $entity->getTitle(),
                        $translation->getId(),
                        $lang
                    );
                } else {
                    // Translation exists
                    $translation = $translations[$lang];
                    unset($translations[$lang]);
                    $is_existing = true;

                    // Log
                    $logs['info'][] = sprintf(
                        'Translation of entity (ID: %d, Type: %s, Title: %s) already exists (ID: %d, Language: %s)',
                        $entity->getId(),
                        $bundle->getLabel('singular'),
                        $entity->getTitle(),
                        $translation->getId(),
                        $lang
                    );
                }

                // Copy fields?
                if (empty($fields_copiable)
                    || empty($source_field_values)
                ) continue;

                // Do not copy fields if translation is not published
                if (!$translation->isPublished()) {
                    $logs['warning'][] = sprintf(
                        'Skipping copying field values to non-published translated entity (ID: %d, Type: %s, Title: %s, Language: %s)',
                        $translation->getId(),
                        $bundle->getLabel('singular'),
                        $translation->getTitle(),
                        $lang
                    );
                    continue;
                }

                // Get fields to copy
                $fields_to_copy = array_keys($source_field_values);
                // Overwrite existing field values?
                if ($is_existing
                    && empty($settings['actions']['overwrite'])
                ) {
                    $existing_field_values = $this->_application->Entity_Storage()
                        ->fetchValues($bundle->entitytype_name, [$translation->getId()], $fields_to_copy);
                    if (!empty($existing_field_values[$translation->getId()])) {
                        $fields_to_copy = array_diff($fields_to_copy, array_keys($existing_field_values[$translation->getId()]));
                    }
                }

                // Get field values to copy
                $field_values = [];
                foreach ($fields_to_copy as $field_name) {
                    if (!$field = $this->_application->Entity_Field($bundle->name, $field_name)) continue;

                    $field_values[$field_name] = $this->_application->Field_Type($fields_copiable[$field_name])->fieldCopyValues(
                        $field,
                        $source_field_values[$field_name],
                        $field_values,
                        $lang
                    );
                }
                if (empty($field_values)) continue;

                // Copy field values
                try {
                    $this->_application->Entity_Storage()->saveValues($translation, $field_values);
                    $this->_application->Entity_Field_load($translation, null, true);
                    $this->_application->Action('entity_field_values_copied', [$bundle, $translation, $field_values, !$is_existing]);
                    $logs['success'][] = sprintf(
                        'Field values copied to translated entity (ID: %d, Type: %s, Title: %s, Language: %s)',
                        $translation->getId(),
                        $bundle->getLabel('singular'),
                        $translation->getTitle(),
                        $lang
                    );
                } catch (\Exception $e) {
                    $logs['error'][] = sprintf(
                        'Failed copying field values to translated entity (ID: %d, Type: %s, Title: %s, Language: %s), Error: %s',
                        $translation->getId(),
                        $bundle->getLabel('singular'),
                        $translation->getTitle(),
                        $lang,
                        $e->getMessage()
                    );
                }
            }
        }

        return $paginator->getElementLimit();
    }
}