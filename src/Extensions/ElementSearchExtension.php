<?php

namespace NSWDPC\Elemental\ModelAdmin\Extensions;

use DNADesign\Elemental\Models\BaseElement;
use SilverStripe\Core\Extension;
use SilverStripe\Forms\DropdownField;
use DNADesign\Elemental\Controllers\ElementalAreaController;
use SilverStripe\Control\Controller;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\LiteralField;

/**
 * A hackish way of getting the title to appear first for searches
 *
 * @property BaseElement|ElementSearchExtension $owner
 */
class ElementSearchExtension extends Extension
{
    public function updateSearchableFields(&$fields)
    {
        $orderedFields = [];
        if (isset($fields['Title'])) {
            $orderedFields['Title'] = $fields['Title'];
        }
        foreach ($fields as $k => $v) {
            if ($k === "Title") {
                continue;
            }
            if ($k === 'LastEdited') {
                continue;
            }
            $orderedFields[$k] = $fields[$k];
        }

        /**
         * Add a ClassName filter to the searchable fields
         * The source values are populated in the modeladmin GridFieldFilterHeader
         */
        $orderedFields['ClassName'] = [
            'title' => _t('ElementalModelAdmin.BLOCK_TYPE', 'Content block type'),
            'field' => DropdownField::class,
            'filter' => 'ExactMatchFilter',
        ];
        $fields = $orderedFields;
    }

    public function OwnerTitleAndDescription()
    {
        $owner = $this->getOwner();
        $page = $owner->getPage() ?: $owner;
        $parent = $owner->Parent() ?: $owner;
        $elementalArea = $owner->Parent();
        // if the ElementalArea has a ContextTitle .. use that
        if ($elementalArea && $elementalArea->hasMethod('ContextTitle')) {
            $areaTitle = $elementalArea->ContextTitle();
        } else {
            $areaTitle = _t('ElementalModelAdmin.MAIN_CONTENT_BLOCKS', 'Main content blocks');
        }
        return '<strong>Page:</strong> <a href="' . $page->CMSEditLink() . '">' . trim(trim(($parent->OwnerTitleAndDescription())), '-') . '</a>' .
            '<br />' .
            '<strong> ↳ ' . $areaTitle . ':</strong> ' . $this->SiblingList();
    }

    public function SiblingList()
    {
        $owner = $this->getOwner();
        $siblings = BaseElement::get()->filter(['ParentID' => $owner->ParentID]);
        $array = [];
        foreach ($siblings as $sibling) {
            if ($sibling->ID === $owner->ID) {
                $array[] = '<div style="padding-left: 2em;"><strong>&raquo; ' . $owner->Title . ' (this block) </strong></div>';
            } else {
                $array[] = '<div style="padding-left: 2em;">- <a href="' . $sibling->CMSEditLink(true) . '">' . ($sibling->Title ?: '#' . $owner->ID) . '</a></div>';
            }
        }

        if ($array === []) {
            $array[] = '(none)';
        }

        return implode('', $array);
    }

    protected function pushOwnerTitleAndDescriptionField($fields)
    {
        $owner = $this->getOwner();
        $curr = Controller::curr();
        if (get_class($curr) !== ElementalAreaController::class) {
            $fields->push(
                LiteralField::create(
                    'MyPageTitle',
                    '<hr style="margin-top: 150px;"/>
                    <h3 style="font-weight: bold; font-size: 16px;">Navigation Help</h3>
                    ' .
                        $owner->OwnerTitleAndDescription()
                )
            );
        }
    }

    public function updateCMSFields(FieldList $fields)
    {
        $this->pushOwnerTitleAndDescriptionField($fields);
    }
}
