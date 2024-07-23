<?php

namespace NSWDPC\Elemental\Extensions\ModelAdmin;

use DNADesign\Elemental\Models\ElementalArea;
use NSWDPC\Elemental\ElementalAdmin;
use SilverStripe\Control\Controller;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Config\Config;
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
        // these fields are only exposed in the ModelAdmin
        if (get_class(Controller::curr()) != ElementalAdmin::class) {
            return;
        }

        $areas = $this->owner->getApplicableElementalAreas(false);
        if (!$areas || $areas->count() == 0) {
            return;
        }

        $field = DropdownField::create(
            'ParentID',
            _t('ElementalModelAdmin.MOVE_TO_AREA', 'Move this block'),
            $areas->map('ID', 'OwnerTitleAndDescription')
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
    public function getAreaOwnerClasses() : array
    {
        $classes = [];
        if ($list = DB::Query("SELECT \"OwnerClassName\" FROM \"ElementalArea\" GROUP BY \"OwnerClassName\"")) {
            foreach ($list as $record) {
                $classes[] = $record['OwnerClassName'];
            }
        }
        return $classes;
    }

    /**
     * Get all possible element relations
     * @return array
     */
    public function getElementalAreaRelations() : array
    {
        $relations = [];
        $relations[] = "ElementalArea";// default

        $classes = $this->owner->config()->get('supported_parent_classes');
        if (empty($classes) || !is_array($classes)) {
            $classes = [\Page::class];
        }

        $sourceClasses = ClassInfo::subclassesFor(ElementalArea::class, true);

        // filter relations that are an ElementalArea or subclass
        $filter = function ($var) use ($sourceClasses) {
            return in_array($var, $sourceClasses);
        };

        foreach ($classes as $class) {
            $has_one = Config::inst()->get($class, 'has_one');
            if (!is_array($has_one) || empty($has_one)) {
                // ignore
                continue;
            }
            $result = array_filter($has_one, $filter);
            $relations = array_merge($relations, array_keys($result));
        }

        $relations = array_unique($relations);

        $suffix = function (&$value) {
            $value .= "ID";
        };
        array_walk($relations, $suffix);

        return $relations;
    }

    /**
     * Retrieve all applicable elemental areas
     * @param bool exclude_current if true, the current element's parent will be excluded from the returned list
     * @return \SilverStripe\ORM\DataList|null
     */
    public function getApplicableElementalAreas(bool $exclude_current = true)
    {

        // get available classes
        $classes = $this->owner->getAreaOwnerClasses();

        $relationColumns = $this->owner->getElementalAreaRelations();

        $area_ids = [];
        foreach ($classes as $class) {
            try {
                $inst = Injector::inst()->get($class);
                /**
                 * if the class does not exist - or not a valid instance
                 * avoid returning the IDs for these areas
                 * this could occur if a module was removed
                 */
                if (!$inst || !($inst instanceof DataObject)) {
                    continue;
                }

                $list = $class::get()->setQueriedColumns($relationColumns);
                foreach ($list as $record) {
                    //e.g record = \Page
                    foreach ($relationColumns as $relationColumn) {
                        if (!empty($record->{$relationColumn})) {
                            $area_ids[] = $record->{$relationColumn};
                        }
                    }
                }
            } catch (\Exception $e) {
                //noop - ignore DB errors and the like
            }
        }

        // grab a uniq list of ElementArea.ID values
        $area_ids = array_unique($area_ids);
        if (!empty($area_ids)) {
            $list = ElementalArea::get()
                        ->filter(['ID' => $area_ids])
                        ->sort('LastEdited DESC');
            if ($exclude_current) {
                // exclude the current element's area
                $list = $list->exclude([ 'ID' => $this->owner->ParentID ]);
            }
            return $list;
        } else {
            return null;
        }
    }
}
