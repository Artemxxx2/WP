<?php
namespace SabaiApps\Directories\Component\Entity\Type;

use SabaiApps\Framework\User\AbstractIdentity;

interface IEntity extends \Serializable
{
    public function getId();
    public function getType();
    public function getTimestamp();
    public function getModified();
    public function getBundleName();
    public function getBundleType();
    public function getTitle();
    public function getSlug();
    public function getAuthor();
    public function getAuthorId();
    public function getFieldValue($name);
    public function getSingleFieldValue($name, $key = null);
    public function initFields(array $values, array $types, $markLoaded = true);
    public function isFieldsLoaded();
    public function getFieldValues($withProperty = false);
    public function getContent();
    public function getParent();
    public function getParentId();
    public function setParent(IEntity $parent);
    public function isDraft();
    public function isPending();
    public function isPublished();
    public function isPrivate();
    public function isScheduled();
    public function getStatus();
    public function isTaxonomyTerm();
}
