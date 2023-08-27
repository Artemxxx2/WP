<?php
namespace SabaiApps\Directories\Component\Entity\Controller;

use SabaiApps\Directories\Controller;
use SabaiApps\Directories\Context;

class ListTaxonomyTerms extends Controller
{
    protected function _doExecute(Context $context)
    {
        $list = [];
        if (($bundle_name = $context->getRequest()->asStr('bundle'))
            && ($bundle_names = explode(',', $bundle_name))
        ) {
            $parent_id = $context->getRequest()->has('parent') ? $context->getRequest()->asInt('parent', 0) : null;
            if (isset($parent_id)) {
                $depth = 1;
                $terms_index = $parent_id;
            } else {
                $depth = $context->getRequest()->asInt('depth', 0);
                $terms_index = 0;
            }
            $hide_empty = $context->getRequest()->asBool('hide_empty', false);
            $hide_count = $context->getRequest()->asBool('hide_count', false);
            $no_url = $context->getRequest()->asBool('no_url', false);
            $no_depth = $context->getRequest()->asBool('no_depth', false);
            $all_count_only = $context->getRequest()->asBool('all_count_only', false);
            foreach ($bundle_names as $bundle_name) {
                $terms = $this->Entity_TaxonomyTerms($bundle_name, null, $parent_id);
                if (isset($terms[$terms_index])) {
                    $this->_listTerms($list, $terms, $terms_index, $depth, $hide_empty, $hide_count, $no_url, $no_depth, $all_count_only);
                    if ($parent_id) break; // found branch
                }
            }
            $list = array_values($list);
        }
        $context->addTemplate('system_list')->setAttributes(array('list' => $list));
    }

    protected function _listTerms(&$list, $terms, $parent, $depth, $hideEmpty, $hideCount, $noUrl, $noDepth, $allCountOnly, $level = 1)
    {
        foreach (array_keys($terms[$parent]) as $term_id) {
            if ($hideEmpty && empty($terms[$parent][$term_id]['count'])) continue;

            $list[$term_id] = $terms[$parent][$term_id];
            if ($noUrl) unset($list[$term_id]['url']);
            if ($noDepth) unset($list[$term_id]['depth']);
            if ($hideCount) {
                unset($list[$term_id]['count']);
            } else {
                if ($allCountOnly) {
                    $list[$term_id]['count'] = empty($list[$term_id]['count']['_all']) ? 0 : $list[$term_id]['count']['_all'];
                }
            }
            if ($depth && $level === $depth) continue;

            if (isset($terms[$term_id])) {
                $this->_listTerms($list, $terms, $term_id, $depth, $hideEmpty, $hideCount, $noUrl, $noDepth, $allCountOnly, ++$level);
            }
        }
    }
}
