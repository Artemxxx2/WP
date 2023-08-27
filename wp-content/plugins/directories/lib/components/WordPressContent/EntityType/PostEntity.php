<?php
namespace SabaiApps\Directories\Component\WordPressContent\EntityType;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Framework\User\AbstractIdentity;
use SabaiApps\Directories\Platform\WordPress\Platform;

class PostEntity extends Entity\Type\AbstractEntity
{
    protected $_prefix, $_author;

    public function __construct($post)
    {
        $this->_prefix = $this->getType() . '_';
        parent::__construct(
            $post->post_type,
            null,
            array(
                $this->_prefix . 'author' => (int)$post->post_author,
                $this->_prefix . 'published' => strtotime('0000-00-00 00:00:00' === $post->post_date_gmt ? get_gmt_from_date($post->post_date) : $post->post_date_gmt),
                $this->_prefix . 'modified' => strtotime('0000-00-00 00:00:00' === $post->post_modified_gmt ? get_gmt_from_date($post->post_modified) : $post->post_modified_gmt),
                $this->_prefix . 'id' => $post->ID,
                $this->_prefix . 'title' => $post->post_title,
                $this->_prefix . 'status' => $post->post_status,
                $this->_prefix . 'slug' => $post->post_name,
                $this->_prefix . 'content' => $post->post_content,
                $this->_prefix . 'parent' => $post->post_parent,
            )
        );
    }

    public function getType()
    {
        return 'post';
    }

    public function getBundleType()
    {
        if (!isset($this->_bundleType)) {
            $this->_bundleType = Platform::getInstance()->getApplication()->Entity_Bundle($this->_bundleName)->type;
        }
        return $this->_bundleType;
    }

    public function getAuthorId()
    {
        return $this->_properties[$this->_prefix . 'author'];
    }

    public function getAuthor()
    {
        return $this->_author;
    }

    public function setAuthor(AbstractIdentity $author)
    {
        $this->_properties[$this->_prefix . 'author'] = (int)$author->id;
        $this->_author = $author;
    }

    public function getTimestamp()
    {
        return $this->_properties[$this->_prefix . 'published'];
    }

    public function getModified()
    {
        return $this->_properties[$this->_prefix . 'modified'];
    }

    public function getId()
    {
        return $this->_properties[$this->_prefix . 'id'];
    }

    public function getTitle()
    {
        return $this->_properties[$this->_prefix . 'title'];
    }

    public function getStatus()
    {
        return $this->_properties[$this->_prefix . 'status'];
    }

    public function setStatus($status)
    {
        $this->_properties[$this->_prefix . 'status'] = $status;
    }

    public function getSlug()
    {
        return $this->_properties[$this->_prefix . 'slug'];
    }

    public function getParent()
    {

    }

    public function getParentId()
    {
        return $this->_properties[$this->_prefix . 'parent'];
    }

    public function setParent(Entity\Type\IEntity $parent)
    {
        $this->_properties[$this->_prefix . 'parent'] = $parent->getId();
    }

    public function getContent()
    {
        return $this->_properties[$this->_prefix . 'content'];
    }

    public function isPublished()
    {
        return $this->_properties[$this->_prefix . 'status'] == 'publish';
    }

    public function isDraft()
    {
        return $this->_properties[$this->_prefix . 'status'] == 'draft';
    }

    public function isPending()
    {
        return $this->_properties[$this->_prefix . 'status'] == 'pending';
    }

    public function isPrivate()
    {
        return $this->_properties[$this->_prefix . 'status'] == 'private';
    }

    public function isScheduled()
    {
        return $this->_properties[$this->_prefix . 'status'] == 'future';
    }

    public function isTaxonomyTerm()
    {
        return false;
    }

    public function post()
    {
        return get_post($this->_properties[$this->_prefix . 'id']);
    }

    protected function _onSerialize()
    {
        $ret = parent::_onSerialize();
        $ret[] = $this->_prefix;
        return $ret;
    }

    protected function _onUnserialize($values)
    {
        parent::_onUnserialize($values);
        $this->_prefix = $values[3];
    }
}
