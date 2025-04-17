<?php

namespace NSWDPC\Elemental\ModelAdmin\Extensions;

use DNADesign\Elemental\Models\BaseElement;
use DNADesign\Elemental\Models\ElementalArea;
use Exception;
use NSWDPC\Elemental\ModelAdmin\Controllers\ElementModelAdmin;
use Page;
use Psr\SimpleCache\CacheInterface;
use SilverStripe\Control\Controller;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Extension;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\DropdownField;
use SilverStripe\ORM\DataExtension;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;

/**
 * This extension is applied to {@link DNADesign\Elemental\Models\BaseElement}
 * If it can, it will add a dropdown field to the element
 * enabling selection of another area
 * Areas not available are filtered out - e.g those attached to classes that no longer exist
 *
 * @property BaseElement|MoveElementExtension $owner
 */
class MoveElementExtension extends Extension
{

    /**
     * Add a parent selection field to the CMS fields, if any exist
     */
    public function updateCMSFields(FieldList $fields)
    {
        // these fields are only exposed in the ModelAdmin
        if (get_class(Controller::curr()) != ElementModelAdmin::class) {
            return;
        }

        $areas = $this->owner->getApplicableElementalAreas(false);
        if (!$areas || count($areas) === 0) {
            return;
        }

        $field = DropdownField::create(
            'ParentID',
            _t('ElementalModelAdmin.MOVE_TO_AREA', 'Move this block'),
            $areas
        )->setEmptyString('')
            ->setRightTitle(
                _t(
                    'ElementalModelAdmin.EMPTY_AREA_CHANGE_INFO',
                    'Choosing an empty value will orphan this record. To re-link it, choose the relevant record from the list.'
                )
            );

        $area = $this->owner->Parent();
        if ($area instanceof ElementalArea) {
            $description = $area->OwnerTitleAndDescription();
            $field->setDescription(
                _t(
                    'ElementalModelAdmin.CURRENT_AREA',
                    'Choose a record to move this block to.'
                        . ' This block is currently associated with \'<em>{description}</em>\'',
                    [
                        'description' => htmlspecialchars($description)
                    ]
                )
            );
        }
        // Add to start of Settings tab fields
        $fields->findorMakeTab('Root.Settings')->unshift($field);
    }

    /**
     * Retrieve all available owner classes
     * @return array
     */
    public function getAreaOwnerClasses(): array
    {
        return ElementalArea::get()
            ->columnUnique('OwnerClassName');
    }

    /**
     * Get all possible element relations
     * @return array
     */
    public function getElementalAreaRelations(): array
    {
        $classes = $this->getAreaOwnerClasses();
        if (empty($classes) || !is_array($classes)) {
            $classes = [Page::class];
        }

        // Get all ElementalArea subclasses once
        $sourceClasses = ClassInfo::subclassesFor(ElementalArea::class, true);

        // Process all classes at once to avoid multiple loops
        $allRelations = [];
        foreach ($classes as $class) {
            // Use Config::forClass() for better performance
            $has_one = Config::forClass($class)->get('has_one');
            if (!is_array($has_one) || empty($has_one)) {
                continue;
            }

            // Find matching relations and add ID suffix in one step
            foreach ($has_one as $relationName => $relationClass) {
                if (in_array($relationClass, $sourceClasses)) {
                    if (! isset($allRelations[$class])) {
                        $allRelations[$class] = [];
                    }
                    $allRelations[$class][] = $relationName . "ID";
                }
            }
        }
        return $allRelations;
    }

    /**
     * Retrieve all applicable elemental areas
     * @param bool exclude_current if true, the current element's parent will be excluded from the returned list
     * @return \SilverStripe\ORM\DataList|null
     */
    public function getApplicableElementalAreas(?bool $excludeCurrent = true): array
    {

        $cache = Injector::inst()->get(CacheInterface::class . '.MoveElementExtension');

        // Check if value exists in cache
        $cacheKey = 'applicableElementalAreasList';
        if ($cache->has($cacheKey)) {
            $list = $cache->get($cacheKey);
            try {
                $list = unserialize($list);
            } catch (Exception $e) {
                // Handle unserialization error
                $list = null;
            }
        } else {
            // Calculate or fetch the expensive value
            $list = $this->getApplicableElementalAreasInner();

            // Store in cache with optional expiry (in seconds)
            $cache->set($cacheKey, serialize($list)); // Expires in 1 hour
        }
        if ($excludeCurrent) {
            unset($value[$this->owner->ParentID]);
        }
        if (empty($list)) {
            return [];
        }

        return $list;
    }
    /**
     * Retrieve all applicable elemental areas
     */
    protected function getApplicableElementalAreasInner(): array
    {

        // get available classes
        $relationColumns = $this->owner->getElementalAreaRelations();
        $ids = [];
        foreach ($relationColumns as $class => $fields) {
            $inst = Injector::inst()->get($class);
            /**
             * if the class does not exist - or not a valid instance
             * avoid returning the IDs for these areas
             * this could occur if a module was removed
             */
            if (!$inst || !($inst instanceof DataObject)) {
                continue;
            }
            foreach ($fields as $field) {
                $myIds = $class::get()
                    ->columnUnique($field);
                $myIds = array_filter($myIds);
                if (!empty($myIds)) {
                    $ids = array_merge($ids, $myIds);
                }
            }
        }

        // grab a uniq list of ElementArea.ID values
        $ids = array_unique($ids);
        if (!empty($ids)) {
            $list = ElementalArea::get()
                ->filter(['ID' => $ids])
                ->sort('LastEdited DESC');
        } else {
            $list = null;
        }
        if ($list) {
            return $list
                ->map('ID', 'OwnerTitleAndDescription')
                ->toArray();
        }
        return [];
    }
}
