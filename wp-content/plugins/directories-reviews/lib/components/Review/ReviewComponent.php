<?php
namespace SabaiApps\Directories\Component\Review;

use SabaiApps\Directories\Component\AbstractComponent;
use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Field;
use SabaiApps\Directories\Component\CSV;
use SabaiApps\Directories\Component\System;
use SabaiApps\Directories\Application;

class ReviewComponent extends AbstractComponent implements
    Entity\IBundleTypes,
    Field\ITypes,
    Field\IWidgets,
    Field\IRenderers,
    Field\IFilters,
    CSV\IExporters,
    CSV\IImporters,
    System\ITools
{
    const VERSION = '1.3.108', PACKAGE = 'directories-reviews';
    
    public static function interfaces()
    {
        return [
            'Faker\IGenerators',
            'WordPressContent\INotifications',
            'Payment\IFeatures',
        ];
    }
    
    public static function description()
    {
        return 'Allows users to submit reviews.';
    }
    
    public function onCoreComponentsLoaded()
    {
        $this->_application->setHelper('Review_Criteria', function (Application $application, Entity\Model\Bundle $bundle, $includeAll = false, $includeAllIfEmpty = false, $translate = true) {
            $criteria = $includeAll ? ['_all' => __('Overall rating', 'directories-reviews')] : [];
            if (empty($bundle->info['review_criteria'])) {
                if (empty($criteria) && $includeAllIfEmpty) {
                    $criteria = ['_all' => __('Overall rating', 'directories-reviews')];
                }
                return $criteria;
            }
            $_criteria = $bundle->info['review_criteria'];
            if ($translate) {
                foreach (array_keys($_criteria) as $slug) {
                    $criteria[$slug] = $application->System_TranslateString($_criteria[$slug], $bundle->name . '_review_criteria_' . $slug, 'review_criteria');
                }
            }
            return $criteria;
        });
    }

    public function fieldGetTypeNames()
    {
        return array('review_rating');
    }
    
    public function fieldGetType($name)
    {
        return new FieldType\RatingFieldType($this->_application, $name);
    }
    
    public function fieldGetWidgetNames()
    {
        return array('review_rating');
    }
    
    public function fieldGetWidget($name)
    {
        return new FieldWidget\RatingFieldWidget($this->_application, $name);
    } 
    
    public function fieldGetRendererNames()
    {
        return array('review_rating', 'review_ratings');
    }
    
    public function fieldGetRenderer($name)
    {
        switch ($name) {
            case 'review_rating':
                return new FieldRenderer\RatingFieldRenderer($this->_application, $name);
            case 'review_ratings':
                return new FieldRenderer\RatingsFieldRenderer($this->_application, $name);
        }
    }
    
    public function fieldGetFilterNames()
    {
        return array('review_rating', 'review_ratings');
    }
    
    public function fieldGetFilter($name)
    {
        switch ($name) {
            case 'review_rating':
                return new FieldFilter\RatingFieldFilter($this->_application, $name);
            case 'review_ratings':
                return new FieldFilter\RatingsFieldFilter($this->_application, $name);
        }
    }
    
    protected function _isBundleReviewable($bundleType)
    {
        $info = $this->_application->Entity_BundleTypeInfo($bundleType);
        return !empty($info['review_enable'])
            && empty($info['is_taxonomy'])
            && empty($info['parent']);
    }
    
    public function entityGetBundleTypeNames()
    {        
        return array('review_review');
    }
    
    public function entityGetBundleType($name)
    {
        return new EntityBundleType\ReviewEntityBundleType($this->_application, $name);
    }
    
    public function onDirectoryContentTypeSettingsFormFilter(&$form, $directoryType, $contentType, $info, $settings, $parents, $submitValues)
    {
        if (!isset($info['review_enable'])
            || !$info['review_enable']
            || !empty($info['parent'])
            || !empty($info['is_taxonomy'])
        ) return;
        
        $form['review_enable'] = array(
            '#type' => 'checkbox',
            '#title' => __('Enable reviews', 'directories-reviews'),
            '#default_value' => !empty($settings['review_enable']) || is_null($settings),
            '#horizontal' => true,
            '#weight' => 40,
        );
    }
    
    public function onDirectoryContentTypeInfoFilter(&$info, $contentType, $settings = null)
    {        
        if (!isset($info['review_enable'])) return;
        
        if (!$info['review_enable']
            || !empty($info['is_taxonomy'])
            || !empty($info['parent'])
        ) {
            unset($info['review_enable']);
        }
        
        if (isset($settings['review_enable']) && !$settings['review_enable']) {
            $info['review_enable'] = false;
        }
    }
    
    public function onEntityBundlesInfoFilter(&$bundles, $componentName, $group)
    {
        foreach (array_keys($bundles) as $bundle_type) {
            $info =& $bundles[$bundle_type];
            
            if (empty($info['review_enable'])
                || !empty($info['is_taxonomy'])
                || !empty($info['parent'])
            ) continue;

            // Add review_review bundle
            if (!isset($bundles['review_review'])) { // may already set if updating or importing
                $bundles['review_review'] = [];
            }
            $bundles['review_review']['parent'] = $bundle_type; // must be bundle type for Entity component to create parent field
            $bundles['review_review'] += $this->entityGetBundleType('review_review')->entityBundleTypeInfo();
            $bundles['review_review']['properties']['parent']['label'] = $info['label_singular'];
            
            return; // there should be only one bundle with review enabled in a group
        }
        
        // No bundle with reviews enabled found, so make sure the review_review bundle is not assigned
        unset($bundles['review_review']);
    }
    
    public function onEntityBundleInfoFilter(&$info, $componentName, $group)
    {
        if (empty($info['review_enable'])
            || !empty($info['is_taxonomy'])
            || !empty($info['parent'])
        ) return;
            
        // Add a field to reviewable bundle that holds overall review ratings 
        $info['fields']['review_ratings'] = array(
            'label' => __('Review Rating', 'directories-reviews'),
            'type' => 'voting_vote',
            'settings' => [],
            'max_num_items' => 0,
        );
    }
    
    public function onEntityBundleInfoKeysFilter(&$keys)
    {
        $keys[] = 'review_enable';
    }

    public function onEntityBundleInfoUserKeysFilter(&$keys)
    {
        $keys[] = 'review_criteria';
    }
    
    public function onEntityCreateEntitySuccess($bundle, $entity, $values, $extraArgs)
    {
        if ($bundle->type !== 'review_review'
            || !$entity->isPublished()
            || (!$parent_entity = $this->_application->Entity_ParentEntity($entity, false))
        ) return;
        
        $this->_castVote($bundle, $parent_entity, $entity);
    }
    
    public function onEntityUpdateEntitySuccess($bundle, $entity, $oldEntity, $values, $extraArgs)
    {
        if ($bundle->type !== 'review_review'
             || (!$parent_entity = $this->_application->Entity_ParentEntity($entity, false))
        ) return;
        
        if ($entity->isPublished()) {
            if (isset($values['review_rating']) // rating changed
                || isset($values['status']) // review was just published
            ) {
                $this->_castVote($bundle, $parent_entity, $entity, true);
            }
        } else {
            if ($oldEntity->isPublished()) {
                $this->_castVote($bundle, $parent_entity, $entity, false, true);
            }
        }
    }

    public function onEntityFieldValuesCopied($bundle, $entity, $values, $isNew)
    {
        if ($bundle->type !== 'review_review'
            || (!$parent_entity = $this->_application->Entity_ParentEntity($entity, false))
            || (!$isNew && !isset($values['review_rating'])) // rating not changed
        ) return;

        $this->_castVote($bundle, $parent_entity, $entity, !$isNew);
    }
    
    protected function _castVote($bundle, $entity, $review, $isEdit = false, $isDelete = false)
    {
        if (!$rating = $review->getSingleFieldValue('review_rating')) return;

        foreach (array_keys($rating) as $rating_name) {
            $rating[$rating_name] = $rating[$rating_name]['value'];
        }

        // Cast vote
        $this->_application->Voting_CastVote(
            $entity,
            'review_ratings',
            $rating,
            array(
                'reference_id' => $review->getId(),
                'user_id' => $review->getAuthorId(),
                'edit' => $isEdit,
                'delete' => $isDelete,
                'allow_empty' => true,
            )
        );
    }
    
    public function fakerGetGeneratorNames()
    {
        return array('review_rating');
    }
    
    public function fakerGetGenerator($name)
    {
        return new FakerGenerator\ReviewFakerGenerator($this->_application, $name);
    }
    
    public function onEntitySchemaorgJsonldFilter(&$json, $entity, $type, $properties)
    {
        if (isset($json['aggregateRating'])
            || !$this->_isBundleReviewable($entity->getBundleType())
            || (!$ratings = $entity->getSingleFieldValue('review_ratings'))
            || empty($ratings['_all']['count'])
            || in_array($type, ['Website'])
        ) return;
        
        $json['aggregateRating'] = [
            '@type' => 'AggregateRating',
            'ratingValue' => $ratings['_all']['average'],
            'reviewCount' => $ratings['_all']['count'],
            'bestRating' => 5,
            'worstRating' => 0,
        ];
    }

    public function systemGetToolNames()
    {
        return ['review_recalculate'];
    }

    public function systemGetTool($name)
    {
        return new SystemTool\RecalculateReviewRatingsSystemTool($this->_application, $name);
    }

    public function csvGetImporterNames()
    {
        return ['review_rating'];
    }

    public function csvGetImporter($name)
    {
        return new CSVImporter\ReviewCSVImporter($this->_application, $name);
    }

    public function csvGetExporterNames()
    {
        return ['review_rating'];
    }

    public function csvGetExporter($name)
    {
        return new CSVExporter\ReviewCSVExporter($this->_application, $name);
    }

    public function wpGetNotificationNames()
    {
        return ['review_published'];
    }

    public function wpGetNotification($name)
    {
        return new WordPressNotification\ReviewWordPressNotification($this->_application, $name);
    }

    public function onEntitySortsFilter(&$sorts, $bundleName)
    {
        if (isset($sorts['review_ratings'])) {
            // This is required since label is fetched from field label that is not editable (no widget).
            $sorts['review_ratings']['label'] = __('Review Rating', 'directories-reviews');
        }
    }

    public function paymentGetFeatureNames()
    {
        return ['review_reviews'];
    }

    public function paymentGetFeature($name)
    {
        return new PaymentFeature\ReviewsPaymentFeature($this->_application, $name);
    }

    public function onViewDisplayElementEntitiesRenderFilter(&$ret, $name, $bundle, $entity)
    {
        if ($ret // do nothing if already false
            && $name === 'view_child_entities_review_review'
            && $bundle->type === 'review_review'
            && !$this->isReviewsEnabled($entity)
        ) {
            $ret = false;
        }
    }

    public function onFrontendsubmitDisplayButtonAddEntityLinkFilter(&$ret, $name, $entity)
    {
        if ($ret // do nothing if already false
            && $name === 'frontendsubmit_add_review_review'
            && !$this->isReviewsEnabled($entity)
        ) {
            $ret = false;
        }
    }

    public function isReviewsEnabled($entity)
    {
        if ((!$bundle = $this->_application->Entity_Bundle($entity))
            || empty($bundle->info['review_enable'])
        ) return false;

        return empty($bundle->info['payment_enable'])
            || !$this->_application->isComponentLoaded('Payment')
            || $this->_application->Payment_Plan_hasFeature($entity, 'review_reviews');
    }
}