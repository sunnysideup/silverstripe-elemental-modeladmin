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
            $title = $sibling->Title ?: 'Untitled Block #' . $sibling->ID;
            if ($sibling->ID === $owner->ID) {
                $array[] = '<div style="padding-left: 2em;"><strong>&raquo; ' . $title . ' (this block) </strong></div>';
            } else {
                $array[] = '<div style="padding-left: 2em;">- <a href="' . $sibling->CMSEditLink(true) . '">' . $title . '</a></div>';
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
            $id = 'nav-' . rand(0, 9999999);
            $fields->push(
                LiteralField::create(
                    'MyPageTitle',
                    '
                    <section>
                        <a href="#' . $id . '" onclick="const d=this.nextElementSibling;d.style.display=d.style.display===\'none\'?\'block\':\'none\'; if(d.style.display===\'block\'){d.scrollIntoView({behavior:\'smooth\'});}return false;" style="float: right; display: block; margin-left: 1em; text-decoration: none;" title="Show / hide current location in site structure">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" role="img" aria-labelledby="title desc" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" width="24" height="24">
                        <title id="title">Where am I / Navigation</title>

                        <!-- target circle -->
                        <circle cx="12" cy="12" r="8.5"/>

                        <!-- crosshair ticks -->
                        <path d="M12 2.5v3M12 18.5v3M2.5 12h3M18.5 12h3"/>

                        <!-- compass needle (rotated diamond) -->
                        <path d="M12 4.5l3 7.5-3 7.5-3-7.5z" fill="currentColor" stroke="none" transform="rotate(30 12 12)"/>

                        <!-- center dot -->
                        <circle cx="12" cy="12" r="1.25" fill="currentColor" stroke="none"/>
                        </svg>
                        </a>
                        <div style="display: none; margin-top: 1em; clear: both;">
                            <h1>Current location</h1>
                        ' . $owner->OwnerTitleAndDescription() .
                        '</div>
                    </section>'
                )
            );
        }
    }

    public function updateCMSFields(FieldList $fields)
    {
        $this->pushOwnerTitleAndDescriptionField($fields);
    }
}
