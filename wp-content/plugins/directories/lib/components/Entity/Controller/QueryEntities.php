<?php
namespace SabaiApps\Directories\Component\Entity\Controller;

use SabaiApps\Directories\Controller;
use SabaiApps\Directories\Context;

class QueryEntities extends Controller
{
    protected function _doExecute(Context $context)
    {
        $entity_type = $this->Entity_BundleTypeInfo($context->bundle_type, 'entity_type');
        $bundle = $context->getRequest()->asStr('bundle');
        $num = $context->getRequest()->asInt('num', 5);
        $no_url = $context->getRequest()->asBool('no_url', false);
        if ($num > 200 || $num <= 0) $num = 5;
        $q = trim($context->getRequest()->asStr('query'));
        $user_id = $context->getRequest()->asInt('user_id');
        if ($q
            || $user_id
            || (false === $list = $this->getPlatform()->getCache($cache_id = $this->_getCacheId($entity_type, $bundle, $num, $no_url)))
        ) {
            $list = [];
            if ($bundle) {
                $query = $this->Entity_Query($entity_type, explode(',', $bundle));
            } else {
                $query = $this->Entity_Query($entity_type)->fieldIs('bundle_type', $context->bundle_type);
            }
            $query->fieldIs('status', $this->Entity_Status($entity_type, 'publish'));

            if ($q) {
                $query->fieldContains('title', $q);
                $load_fields = false;
            }
            if ($user_id) {
                $query->fieldIs('author', $user_id);
                $load_fields = false;
            }
            $order = $context->getRequest()->asStr('order', 'DESC');
            if ($sort = $context->getRequest()->asStr('sort')) {
                $query->sortByField($sort, $order);
            } else {
                $query->sortById($order);
            }
            if (!isset($load_fields)) {
                $load_fields = $this->Entity_BundleTypeInfo($context->bundle_type, 'entity_image') ? true : false;
            }
            try {
                foreach ($query->fetch($num, 0, null, $load_fields) as $entity) {
                    $_entity = [
                        'id' => $entity->getId(),
                        'slug' => $entity->getSlug(),
                        'title' => $this->Entity_Title($entity),
                        'icon_src' => $this->Entity_Image($entity, 'icon'),
                    ];
                    if (!$no_url) {
                        $_entity['url'] = (string)$this->Entity_PermalinkUrl($entity);
                    }
                    if ($icon_src = $this->Entity_Image($entity, 'icon')) {
                        $_entity['icon_src'] = $icon_src;
                    }
                    $list[] = $_entity;
                }
            } catch (\Exception $e) {
                $context->setError($e->getMessage());
                return;
            }
            if (isset($cache_id)) $this->getPlatform()->setCache($list, $cache_id, 864000); // cache 10 days
        }
        $context->addTemplate('system_list')->setAttributes(['list' => $list]);
    }

    protected function _getCacheId($entityType, $bundleName, $num, $noUrl)
    {
        $id = 'drts-entity-list-' . $entityType . '-' . $bundleName . '-' . $num;
        if ($noUrl) $id .= '-' . (int)$noUrl;
        return $id;
    }
}
