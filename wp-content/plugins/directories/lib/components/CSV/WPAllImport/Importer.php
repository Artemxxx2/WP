<?php
namespace SabaiApps\Directories\Component\CSV\WPAllImport;

use SabaiApps\Directories\Application;

class Importer
{
    protected $_addons = [], $_fields = [], $_application;

    // Post meta key for temporarily saving imported attachment IDs.
    // Use double underscores instead of single to prevent from trying to load from drts fields.
    const IMPORTED_ATTACHMENTS_META_KEY = '__drts_imported_attachments';

    public function __construct(Application $application, array $postTypes)
    {
        $this->_application = $application;
        $this->_init($postTypes);
    }

    protected function _init(array $postTypes)
    {
        if (!$importers = $this->_application->CSV_Importers(true)) return;

        require_once __DIR__ . '/rapid-addon.php';

        foreach ($postTypes as $post_type) {
            if ((!$bundle = $this->_application->Entity_Bundle($post_type))
                || !empty($bundle->info['parent'])
            ) continue;

            $addon = new \RapidAddon($bundle->getLabel('singular'), 'drts_' . $post_type);
            $addon->disable_default_images();
            $fields = $this->_application->Entity_Field($bundle);
            $importable_fields = [];
            foreach (array_keys($fields) as $field_name) {
                $field = $fields[$field_name];
                if (!isset($importers[$field->getFieldType()])) continue;

                if ((!$importer = $this->_application->CSV_Importers_impl($importers[$field->getFieldType()], true))
                    || !$importer instanceof \SabaiApps\Directories\Component\CSV\Importer\IWpAllImportImporter
                ) {
                    unset($importers[$field->getFieldType()]);
                    continue;
                }

                if (!$importer->csvWpAllImportImporterAddField($addon, $field)) continue;

                $importable_fields[] = $field_name;
            }
            if (empty($importable_fields)) continue;

            $addon->set_import_function([$this, 'import']);
            $addon->run(['post_types' => [$post_type]]);
            $this->_addons[$post_type] = $addon;
            $this->_fields[$post_type] = $importable_fields;
        }

        add_action('pmxi_saved_post', [$this, 'pmxiSavedPostAction'], 10, 3);
    }

    public function import($postId, $data, $options, $article)
    {
        if ((!$importers = $this->_application->CSV_Importers(true))
            || (!$post_type = get_post_type($postId))
            || !isset($this->_addons[$post_type])
            || empty($this->_fields[$post_type])
            || (!$bundle = $this->_application->Entity_Bundle($post_type))
            || (!$fields = $this->_application->Entity_Field($bundle))
            || (!$fields = array_intersect_key($fields, array_flip($this->_fields[$post_type])))
            || (!$entity = $this->_application->Entity_Entity('post', $postId))
        ) return;

        $addon = $this->_addons[$post_type];
        $addon->_save_post_callbacks = [];
        $values = [];
        foreach (array_keys($fields) as $field_name) {
            $field = $fields[$field_name];
            if (!$addon->can_update_meta('_drts_' . $field_name, $options)) {
                $addon->log('[drts] Skipping field `' . $field->getFieldLabel() . '`');
                continue;
            }

            if (!$importer = $this->_application->CSV_Importers_impl($importers[$field->getFieldType()], true)) {
                $addon->log('[drts] Importer not found for field `' . $field->getFieldLabel() . '`');
                continue;
            }

            $addon->log('[drts] Importing field `' . $field->getFieldLabel() . '` ...');
            if (null !== $value = $importer->csvWpAllImportImporterDoImport($this->_addons[$post_type], $fields[$field_name], $data, $options, $article)) {
                $values[$field_name] = $value;
            }
        }
        if (!empty($values)) {
            $addon->log('[drts] Saving field values for post ...');
            $this->_application->Entity_Save($entity, $values);
        } else {
            $addon->log('[drts] No values to save for post');
        }
    }

    public function pmxiSavedPostAction($postId, $xml, $isUpdate)
    {
        if ((!$post_type = get_post_type($postId))
            || !isset($this->_addons[$post_type])
        ) return;

        $addon = $this->_addons[$post_type];
        $values = [];

        if ($attachment_ids = get_post_meta($postId, self::IMPORTED_ATTACHMENTS_META_KEY, true)) {
            $addon->log('[drts] Assigning attachment IDs ...');
            foreach (array_keys($attachment_ids) as $field_name) {
                $values[$field_name] = array_values($attachment_ids[$field_name]);
            }
        }
        delete_post_meta($postId, self::IMPORTED_ATTACHMENTS_META_KEY);

        if (($bundle = $this->_application->Entity_Bundle($post_type))
            && !empty($bundle->info['taxonomies'])
        ) {
            // For some reason wp_get_object_terms() returns empty, so query DB directly
            global $wpdb;
            $results = $wpdb->get_results('SELECT tt.term_id, tt.taxonomy'
                . ' FROM ' . $wpdb->term_relationships . ' tr'
                . ' INNER JOIN ' . $wpdb->term_taxonomy . ' tt ON tt.term_taxonomy_id = tr.term_taxonomy_id'
                . ' WHERE tr.object_id = ' . (int)$postId);
            if ($results) {
                $term_ids = [];
                foreach (array_keys($results) as $k) {
                    $term_id = $results[$k]->term_id;
                    $term_ids[$results[$k]->taxonomy][$term_id] = $term_id;
                }
                if (!empty($term_ids)) {
                    $addon->log('[drts] Assigning taxonomy term IDs ...');
                    foreach ($bundle->info['taxonomies'] as $bundle_type => $taxonomy) {
                        if (empty($term_ids[$taxonomy])) continue;

                        $values[$bundle_type] = array_values($term_ids[$taxonomy]);
                    }
                }
            }
        }

        if (!empty($values)
            && ($entity = $this->_application->Entity_Entity('post', $postId))
        ) {
            if (!empty($this->_addons[$post_type]->_save_post_callbacks)) {
                foreach ($this->_addons[$post_type]->_save_post_callbacks as $callback) {
                    $this->_application->CallUserFuncArray($callback, [$entity, &$values]);
                }
            }

            $this->_application->Entity_Save($entity, $values);
        }
    }
}