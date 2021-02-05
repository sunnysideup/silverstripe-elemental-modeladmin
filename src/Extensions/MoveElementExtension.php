<?php

namespace NSWDPC\Elemental\Extensions\ModelAdmin;

use DNADesign\Elemental\Models\ElementalArea;
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
 * @author James
 */
class MoveElementExtension extends DataExtension
{

    /**
     * Add a parent selection field to the CMS fields, if any exist
     */
    public function updateCMSFields(FieldList $fields)
    {
        $areas = $this->owner->getApplicableElementalAreas();
        if(!$areas || $areas->count() == 0) {
            return;
        }

        $field = DropdownField::create(
            'ParentID',
            _t('ElementalModelAdmin.MOVE_TO_AREA', 'Move to another elemental area'),
            $areas->map('ID', 'OwnerTitleAndDescription')
        )->setEmptyString('');

        $area = $this->owner->Parent();
        if($area instanceof ElementalArea) {
            $description = $area->OwnerTitleAndDescription();
            $field->setDescription(
                _t(
                    __CLASS__ . '.CURRENT_AREA',
                    'This element is currently associated with <em>{description}</em>',
                    [
                        'description' => $description
                    ]
                )
            );
        }

        $fields->addFieldToTab(
            'Root.Main',
            $field
        );
    }

    /**
     * Retrieve all available owner classes
     * @return array
     */
    public function getAreaOwnerClasses() : array {
        $classes = [];
        if($list = DB::Query("SELECT `OwnerClassName` FROM `ElementalArea` GROUP BY `OwnerClassName`")) {
            foreach($list as $record) {
                $classes[] = $record['OwnerClassName'];
            }
        }
        return $classes;
    }

    /**
     * Retrieve all applicable elemental areas
     * @param bool exclude_current if true, the current element's parent will be excluded from the returned list
     * @return \SilverStripe\ORM\DataList|null
     */
    public function getApplicableElementalAreas(bool $exclude_current = true) {

        // get available classes
        $classes = $this->owner->getAreaOwnerClasses();

        $area_ids = [];
        foreach($classes as $class) {
            try {
                $inst = Injector::inst()->get($class);
                /**
                 * if the class does not exist - or not a valid instance
                 * avoid returning the IDs for these areas
                 * this could occur if a module was removed
                 */
                if(!$inst || !($inst instanceof DataObject)) {
                    continue;
                }
                $list = $class::get()->setQueriedColumns(['ElementalAreaID']);
                foreach($list as $record) {
                    //e.g record = \Page
                    $area_ids[] = $record->ElementalAreaID;
                }
            } catch (\Exception $e) {
                //noop - ignore DB errors and the like
            }
        }

        // grab a uniq list of ElementArea.ID values
        $area_ids = array_unique($area_ids);
        if(!empty($area_ids)) {
            $list = ElementalArea::get()
                        ->filter(['ID' => $area_ids])
                        ->sort('LastEdited DESC');
            if($exclude_current) {
                // exclude the current element's area
                $list = $list->exclude([ 'ID' => $this->owner->ParentID ]);
            }
            return $list;
        } else {
            return null;
        }
    }
}
