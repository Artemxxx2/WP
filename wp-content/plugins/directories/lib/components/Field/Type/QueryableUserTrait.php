<?php
namespace SabaiApps\Directories\Component\Field\Type;

use SabaiApps\Directories\Component\Field\IField;
use SabaiApps\Directories\Component\Field\Query;
use SabaiApps\Directories\Component\Entity\Model\Bundle;

trait QueryableUserTrait
{
    public function fieldQueryableInfo(IField $field, $inAdmin = false)
    {
        if ($inAdmin) {
            $tip = __('Enter user IDs and usernames separated with commas. Prefix each with "-" to exclude.', 'directories');
        } else {
            $tip = __('Enter user IDs, usernames, "_current_" (for current post author if any), or "_current_user_" (for current user) separated with commas. Prefix each with "-" to exclude.', 'directories');
        }
        return array(
            'example' => '1,-_current_user_,john,-12',
            'tip' => $tip,
        );
    }
    
    public function fieldQueryableQuery(Query $query, $fieldName, $paramStr, Bundle $bundle)
    {
        if (!$ids = $this->_queryableParams($paramStr)) return;

        $exclude = [];
        foreach (array_keys($ids) as $k) {
            if (is_numeric($ids[$k])) {
                if ($ids[$k] === '-0') { // exclude empty author?
                    $exclude[] = 0;
                    unset($ids[$k]);
                } else {
                    $ids[$k] = (int)$ids[$k];
                    if ($ids[$k] < 0) {
                        $exclude[] = -1 * $ids[$k]; // removes "-"
                        unset($ids[$k]);
                    }
                }
                continue;
            }

            // Query current user?
            if (false !== $pos = strpos($ids[$k], '_current_user_')) {
                $current_user = $this->_application->getUser();
                $current_user_id = $current_user->isAnonymous() ? 0 : $current_user->id;
                if ($pos === 1) {
                    $exclude[] = $current_user_id;
                    unset($ids[$k]);
                } else {
                    $ids[$k] = $current_user_id;
                }
                continue;
            }

            // Query current author?
            if (in_array($ids[$k], array('_current_', '-_current_'))) {
                if ($author_id = $this->_getCurrentAuthorId()) {
                    if ($ids[$k] === '-_current_') {
                        $exclude[] = $author_id;
                        unset($ids[$k]);
                    } else {
                        $ids[$k] = $author_id;
                    }
                }
                continue;
            }

            // Query current BuddyPress user profile?
            if ($ids[$k] === '_bp_displayed_user_'
                && function_exists('bp_displayed_user_id')
                && ($bp_user_id = bp_displayed_user_id())
            ) {
                $ids[$k] = $bp_user_id;
                continue;
            }

            if (0 === $pos = strpos($ids[$k], '-')) {
                $ids[$k] = substr($ids[$k], 1);
            }
            if (!$user = $this->_application->getPlatform()->getUserIdentityFetcher()->fetchByUsername($ids[$k])) {
                unset($ids[$k]); // invalid user name
                continue;
            }
            if ($pos === 0) {
                $exclude[] = $user->id;
                unset($ids[$k]);
            } else {
                $ids[$k] = $user->id;
            }
        }
        if (!empty($ids)) {
            $query->fieldIsIn($fieldName, $ids);
        }
        if (!empty($exclude)) {
            $query->fieldIsNotIn($fieldName, $exclude);
        }
    }

    protected function _getCurrentAuthorId()
    {
        if (isset($GLOBALS['drts_entity'])) {
            return $GLOBALS['drts_entity']->getAuthorId();
        }
        if ($this->_application->getPlatform()->getName() === 'WordPress'
            && isset($GLOBALS['post'])
        ) {
            return $GLOBALS['post']->post_author;
        }
    }
}