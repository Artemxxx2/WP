<?php
namespace SabaiApps\Directories\Component\Entity\FieldRenderer;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Field;

class AuthorFieldRenderer extends Field\Renderer\AbstractRenderer
{    
    protected function _fieldRendererInfo()
    {
        return [
            'field_types' => [$this->_name],
            'default_settings' => [
                'format' => 'link_thumb_s',
            ],
            'inlineable' => true,
        ];
    }
    
    protected function _fieldRendererSettingsForm(Field\IField $field, array $settings, array $parents = [])
    {
        return [
            'format' => [
                '#title' => __('Display format', 'directories'),
                '#type' => 'select',
                '#options' => $this->_application->UserIdentityHtml(),
                '#default_value' => $settings['format'],
                '#horizontal' => true,
            ],
        ];
    }

    protected function _fieldRendererRenderField(Field\IField $field, array &$settings, Entity\Type\IEntity $entity, array $values, $more = 0)
    {
        return $this->_application->UserIdentityHtml($this->_application->Entity_Author($entity), $settings['format']);
    }
    
    public function fieldRendererIsPreRenderable(Field\IField $field, array $settings)
    {
        return true;
    }
    
    public function fieldRendererPreRender(Field\IField $field, array $settings, array $entities)
    {
        $author_ids = $no_author_entity_ids = [];
        foreach (array_keys($entities) as $entity_id) {
            if ($entities[$entity_id]->getAuthor()) continue; // author already set
            
            if ($author_id = $entities[$entity_id]->getAuthorId()) {
                $author_ids[$entity_id] = $author_id;
            } else {
                $no_author_entity_ids[] = $entity_id;
            }
        }
        // Set identity for each entity
        if (!empty($author_ids)) {
            $identities = $this->_application->UserIdentity(array_unique($author_ids));
            foreach ($author_ids as $entity_id => $author_id) {
                $author = $this->_application->Filter('entity_author', $identities[$author_id], array($entities[$entity_id]));
                $entities[$entity_id]->setAuthor($author);
            }
        }
        // Set anonymous identity for entities without a valid author ID
        if (!empty($no_author_entity_ids)) {
            $anon_identity = $this->_application->getPlatform()->getUserIdentityFetcher()->getAnonymous();
            foreach ($no_author_entity_ids as $entity_id) {
                $author = $this->_application->Filter('entity_author', clone $anon_identity, array($entities[$entity_id]));
                $entities[$entity_id]->setAuthor($author);
            }
        }
    }
    
    protected function _fieldRendererReadableSettings(Field\IField $field, array $settings)
    {
        return [
            'format' => [
                'label' => __('Display format', 'directories'),
                'value' => $this->_application->UserIdentityHtml()[$settings['format']],
            ],
        ];
    }
}