<?php
namespace SabaiApps\Directories\Component\Entity\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\Entity\Model\Bundle;
use SabaiApps\Directories\Component\Field;
use SabaiApps\Directories\Component\Entity\FieldType\IPersonalDataAuthorFieldType;

class PersonalDataHelper
{
    public function help(Application $application, $bundleName, array $fields, $email, $userId, $limit = 0, $offset = 0)
    {
        $exported = [];
        if (!$bundle = $application->Entity_Bundle($bundleName)) return $exported;

        foreach (array_keys($fields) as $personal_data_identifier) {
            $query = $application->Entity_Query($bundle->entitytype_name, $bundleName);
            if ($personal_data_identifier === 'author') {
                if (!isset($author_fields)) $author_fields = $this->authorFields($application, $bundle);

                $query->startCriteriaGroup('OR');
                foreach (array_keys($author_fields) as $field_name) {
                    $author_field = $author_fields[$field_name];
                    $application->Field_Type($author_field->getFieldType())->fieldPersonalDataQuery(
                        $query->getFieldQuery(),
                        ($property = $author_field->isPropertyField()) ? $property : $field_name,
                        $email,
                        $userId
                    );
                }
                $query->finishCriteriaGroup();
            } else {
                if ((!$identifier_field = $application->Entity_Field($bundle, $personal_data_identifier))
                    || (!$identifier_field_type = $application->Field_Type($identifier_field->getFieldType(), true))
                    || !$identifier_field_type instanceof Field\Type\IPersonalDataIdentifier
                ) continue;

                $identifier_field_type->fieldPersonalDataQuery(
                    $query->getFieldQuery(),
                    ($property = $identifier_field->isPropertyField()) ? $property : $identifier_field->getFieldName(),
                    $email,
                    $userId
                );
            }
            foreach ($query->fetch($limit, $offset) as $entity) {
                foreach ($fields[$personal_data_identifier] as $field_name) {
                    if (!$entity->getFieldValue($field_name) // no value
                        || (!$field = $application->Entity_Field($bundle, $field_name))
                    ) continue;

                    if (null !== $personal_data = $application->Field_Type($field->getFieldType())->fieldPersonalDataExport($field, $entity)) {
                        if (is_array($personal_data)) {
                            foreach ($personal_data as $key => $_personal_data) {
                                $exported[$entity->getId()][$field_name . '-' . $key] = [
                                    'name' => $field->getFieldLabel() . ' - ' . $_personal_data['name'],
                                    'value' => $_personal_data['value'],
                                ];
                            }
                        } else {
                            $exported[$entity->getId()][$field_name] = [
                                'name' => $field->getFieldLabel(),
                                'value' => $personal_data,
                            ];
                        }
                    }
                }
            }
        }

        return $exported;
    }

    public function fields(Application $application)
    {
        $fields = [];
        foreach ($application->Entity_Bundles() as $bundle) {
            foreach ($application->Entity_Field($bundle) as $field) {
                if ((!$field_type = $application->Field_Type($field->getFieldType(), true))
                    || !$field_type instanceof Field\Type\IPersonalData
                ) continue;

                if (!$field_type->fieldTypeInfo('admin_only')) {
                    // Non admin-only field must explicitly be configured as personal data.
                    if (!$field->getFieldData('_is_personal_data')
                        || (!$personal_data_identifier = $field->getFieldData('_personal_data_identifier'))
                    ) continue;
                } else {
                    // Admin-only field must also be a personal data identifier field
                    if (!$field_type instanceof Field\Type\IPersonalDataIdentifier) continue;

                    if ($field_type instanceof IPersonalDataAuthorFieldType) {
                        $personal_data_identifier = 'author';
                    } else {
                        $personal_data_identifier = $field->getFieldName();
                    }
                }

                $fields[$bundle->name][$personal_data_identifier][$field->getFieldName()] = $field->getFieldName();
            }
        }
        foreach (array_keys($fields) as $bundle_name) {
            foreach (array_keys($fields[$bundle_name]) as $personal_data_identifier) {
                if ($personal_data_identifier === 'author') continue;

                if ((!$identifier_field = $application->Entity_Field($bundle_name, $personal_data_identifier))
                    || (!$identifier_field_type = $application->Field_Type($identifier_field->getFieldType(), true))
                    || !$identifier_field_type instanceof Field\Type\IPersonalDataIdentifier
                ) {
                    // Invalid personal data identifier field
                    unset($fields[$bundle_name][$personal_data_identifier]);
                }
            }
        }

        return $fields;
    }

    public function identifierFieldOptions(Application $application, Bundle $bundle)
    {
        return [
            'author' => __('Author ID', 'directories'),
        ] + $application->Entity_Field_options(
            $bundle,
            [
                'interface' => 'Field\Type\IPersonalDataIdentifier',
                'prefix' => __('Field - ', 'directories'),
                'interface_exclude' => 'Entity\FieldType\IPersonalDataAuthorFieldType',
                'exclude_disabled' => true,
            ]
        );
    }

    public function authorFields(Application $application, Bundle $bundle)
    {
        $ret = [];
        foreach ($application->Entity_Field($bundle) as $field_name => $field) {
            if ((!$field_type = $application->Field_Type($field->getFieldType(), true))
                || (!$field_type instanceof IPersonalDataAuthorFieldType)
            ) continue;

            $ret[$field_name] = $field;
        }
        return $ret;
    }

    public function erase(Application $application, $bundleName, array $fields, $email, $userId, $limit = 0, $offset = 0)
    {
        $results = ['deleted' => 0, 'retained' => 0, 'messages' => []];
        if (!$bundle = $application->Entity_Bundle($bundleName)) return $results;

        foreach (array_keys($fields) as $personal_data_identifier) {
            $query = $application->Entity_Query($bundle->entitytype_name, $bundleName);
            $identifier_fields = [];
            if ($personal_data_identifier === 'author') {
                if (!isset($author_fields)) $author_fields = $this->authorFields($application, $bundle);

                $query->startCriteriaGroup('OR');
                foreach (array_keys($author_fields) as $field_name) {
                    $author_field = $author_fields[$field_name];
                    $application->Field_Type($author_field->getFieldType())->fieldPersonalDataQuery(
                        $query->getFieldQuery(),
                        ($property = $author_field->isPropertyField()) ? $property : $field_name,
                        $email,
                        $userId
                    );
                    $identifier_fields[$field_name] = $author_field;
                }
                $query->finishCriteriaGroup();

            } else {
                if ((!$identifier_field = $application->Entity_Field($bundle, $personal_data_identifier))
                    || (!$identifier_field_type = $application->Field_Type($identifier_field->getFieldType(), true))
                    || !$identifier_field_type instanceof Field\Type\IPersonalDataIdentifier
                ) continue;

                $identifier_field_name = ($property = $identifier_field->isPropertyField()) ? $property : $identifier_field->getFieldName();
                $identifier_field_type->fieldPersonalDataQuery($query->getFieldQuery(), $identifier_field_name, $email, $userId);
                $identifier_fields[$identifier_field->getFieldName()] = $identifier_field;
            }
            foreach ($query->fetch($limit, $offset) as $entity) {
                $values = [];

                // Erase personal data from fields
                foreach ($fields[$personal_data_identifier] as $field_name) {
                    if (!$entity->getFieldValue($field_name) // no value
                        || (!$field = $application->Entity_Field($bundle, $field_name))
                    ) continue;

                    if (null !== $result = $application->Field_Type($field->getFieldType())->fieldPersonalDataErase($field, $entity)) {
                        $values[$field_name] = $result === true ? false : $result; // delete if true
                        ++$results['deleted'];
                    } else {
                        ++$results['retained'];
				        $results['messages'][] = sprintf(
                            __('%s contains personal data but could not be erased.', 'directories'),
                            $bundle->getLabel('singular') . ' (ID: ' . $entity->getId() . ')'
                        );
                    }
                }

                if (!empty($values)) {
                    // Anonymize identifier fields if have not already been erased
                    foreach (array_keys($identifier_fields) as $identifier_field_name) {
                        $identifier_field = $identifier_fields[$identifier_field_name];
                        if (!isset($values[$identifier_field_name])) {
                            $result = $application->Field_Type($identifier_field->getFieldType())->fieldPersonalDataAnonymize($identifier_field, $entity);
                            $values[$identifier_field_name] = $result === true ? false : $result; // delete if true
                        }
                    }
                    // Save entity
                    $application->Entity_Save($entity, $values);
                }
            }
        }

        return $results;
    }
}
