<?php
namespace SabaiApps\Directories\Component\Entity\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Field\Type\IHumanReadable;

class TokensHelper
{
    public function help(Application $application, Entity\Model\Bundle $bundle, $includeFields = false)
    {
        $tokens = [
            '%id%',
            '%title%',
            '%slug%',
            '%author_id%',
            '%author_name%',
            '%timestamp%',
            '%content%',
            '%current_user_id%',
            '%current_user_name%',
            '%current_user_display_name%',
            '%permalink_url%',
            '%date_published%',
            '%date_modified%',
        ];
        if (!empty($bundle->info['parent'])) {
            $tokens[] = '%parent_title%';
            $tokens[] = '%parent_permalink_url%';
        }
        if ($includeFields) {
            $fields = $application->Entity_Field_options(
                $bundle->name,
                [
                    'interface' => 'Field\Type\IHumanReadable',
                    'return_disabled' => false,
                    'exclude_property' => true,
                    'name_prefix' => 'field_'
                ]
            );
            foreach (array_keys($fields) as $field_name) {
                $tokens[] = '%' . $field_name . '%';
            }
        }
        return $application->Filter('entity_tokens', $tokens, [$bundle]);
    }

    public function replace(Application $application, $text, Entity\Type\IEntity $entity, $includeFields = false, $escapeFields = true, $encodeFields = false)
    {
        if (strpos($text, '%') === false
            || !preg_match_all('#%(.+?)%#', $text, $matches)
        ) return $text;

        $replacements = [];
        foreach ($matches[1] as $key) {
            switch ($key) {
                case 'id':
                    $value = $entity->getId();
                    break;
                case 'title':
                    $value = $application->Entity_Title($entity);
                    break;
                case 'slug':
                    $value = $entity->getSlug();
                    break;
                case 'author_id':
                    $value = $entity->getAuthorId();
                    break;
                case 'author_name':
                    $value = ($author = $application->Entity_Author($entity)) ? $author->username : __('Guest', 'drts');
                    break;
                case 'timestamp':
                    $value = $entity->getTimestamp();
                    break;
                case 'content':
                    $value = ('' !== $content = $entity->getContent()) ? $application->Summarize($content, 300) : '';
                    break;
                case 'current_user_id':
                    $value = $application->getUser()->id;
                    break;
                case 'current_user_name':
                    $value = $application->getUser()->username;
                    break;
                case 'current_user_display_name':
                    $value = $application->getUser()->name;
                    break;
                case 'permalink_url':
                    $value = $application->Entity_PermalinkUrl($entity);
                    break;
                case 'date_published':
                    $value = $application->System_Date($entity->getTimestamp());
                    break;
                case 'date_modified':
                    $value = $application->System_Date($entity->getModified());
                    break;
                case 'parent_title':
                    $value = ($parent_entity = $application->Entity_ParentEntity($entity)) ? $parent_entity->getTitle() : '';
                    break;
                case 'parent_permalink_url':
                    $value = ($parent_entity = $application->Entity_ParentEntity($entity)) ? $application->Entity_PermalinkUrl($parent_entity) : '';
                    break;
                default:
                    continue 2;
            }
            $replacements['%' . $key . '%'] = $value;
        }
        if ($includeFields
            && preg_match_all('#%field_(.+?)%#', $text, $matches)
        ) {
            foreach ($matches[1] as $field_name) {
                $match = $field_name;
                $key = null;
                if ($pos = strpos($field_name, '__')) {
                    $key = substr($field_name, $pos + 2);
                    $field_name = substr($field_name, 0, $pos);
                }
                $tag = '%field_' . $match . '%';
                if (($field = $application->Entity_Field($entity, $field_name))
                    && ($field_type = $application->Field_Type($field->getFieldType(), true))
                    && $field_type instanceof IHumanReadable
                ) {
                    $replacements[$tag] = $field_type->fieldHumanReadableText($field, $entity, null, $key);
                    if ($escapeFields) {
                        $replacements[$tag] = $application->H($replacements[$tag]);
                    } elseif ($encodeFields) {
                        $replacements[$tag] = rawurlencode($replacements[$tag]);
                    }
                } else {
                    $replacements[$tag] = '';
                }
            }
        }

        return strtr($text, $application->Filter('entity_tokens_replace', $replacements, [$entity]));
    }
}
