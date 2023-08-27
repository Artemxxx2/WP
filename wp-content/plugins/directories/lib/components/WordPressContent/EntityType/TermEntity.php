<?php
namespace SabaiApps\Directories\Component\WordPressContent\EntityType;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Platform\WordPress\Platform;

class TermEntity extends Entity\Type\AbstractEntity
{
    public function __construct($term)
    {
        parent::__construct(
            $term->taxonomy,
            null,
            array(
                'term_id' => $term->term_id,
                // WP stores term name escaped, so we need to decode it here
                'term_title' => htmlspecialchars_decode($term->name, ENT_QUOTES),
                'term_slug' => $term->slug,
                'term_parent' => $term->parent,
                'term_content' => $term->description,
            )
        );
    }

    public function getType()
    {
        return 'term';
    }

    public function getBundleType()
    {
        if (!isset($this->_bundleType)) {
            $this->_bundleType = Platform::getInstance()->getApplication()->Entity_Bundle($this->_bundleName)->type;
        }
        return $this->_bundleType;
    }

    public function getTimestamp()
    {
        return time();
    }

    public function getId()
    {
        return $this->_properties['term_id'];
    }

    public function getTitle()
    {
        return $this->_properties['term_title'];
    }

    public function getSlug()
    {
        return $this->_properties['term_slug'];
    }

    public function getParent()
    {

    }

    public function getParentId()
    {
        return $this->_properties['term_parent'];
    }

    public function setParent(Entity\Type\IEntity $parent)
    {
        $this->_properties['term_parent'] = $parent->getId();
    }

    public function getContent()
    {
        return $this->_properties['term_content'];
    }

    public function isTaxonomyTerm()
    {
        return true;
    }

    public function term()
    {
        if ($term = get_term($this->_properties['term_id'], $this->getBundleName())) {
            if (is_wp_error($term)) {
                $term = null;
            }
        }
        return $term;
    }
}
