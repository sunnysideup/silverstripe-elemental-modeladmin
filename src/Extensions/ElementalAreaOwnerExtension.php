<?php

namespace NSWDPC\Elemental\Extensions\ModelAdmin;

use SilverStripe\Core\Extension;

/**
 * This extension is applied to {@link DNADesign\Elemental\Models\ElementalArea}
 * It provides a method to retrive the area's owner title and a short description
 * This is used by {@link NSWDPC\Elemental\Extensions\ModelAdmin\MoveElementExtension}
 * to provide some context for an editor selecting the target elemental area
 * @author James
 */
class ElementalAreaOwnerExtension extends Extension
{

    private static $casting = [
        'OwnerTitleAndDescription' => 'Varchar',
    ];

    /**
     * If the owner 'page' exists, provide a short title with context
     * @return string
     */
    public function OwnerTitleAndDescription() : string
    {
        return $this->getOwnerTitleAndDescription();
    }
    public function getOwnerTitleAndDescription() : string
    {
        $title = '';

        // if the ElementalArea has a ContextTitle .. use that
        if($this->owner->hasMethod('ContextTitle')) {
            $areaTitle = $this->owner->ContextTitle();
        } else {
            $areaTitle = '';
        }

        if($ownerPage = $this->owner->getOwnerPage()) {
            $title =  $ownerPage->Title . ' ('.str_replace("\n", '', trim(strip_tags($ownerPage->Breadcrumbs()))).')';
        } else {
            // unknown owner, or maybe no longer exists
            // TODO: maybe use OwnerClassName?
            $title = _t('elementadmin.UNKNOWN_OWNER', 'unknown parent record');
        }

        $title .= " &raquo; {$areaTitle}";

        return $title;
    }
}
