<?php

namespace NSWDPC\Elemental\Extensions\ModelAdmin;

use SilverStripe\Core\Extension;

/**
 * This extension is applied to {@link DNADesign\Elemental\Models\ElementalAre}
 * It provides a method to retrive the area's owner title and a short description
 * This is used by {@link NSWDPC\Elemental\Extensions\ModelAdmin\MoveElementExtension}
 * to provide some context for an editor selecting the target elemental area
 * @author James
 */
class ElementalAreaOwnerExtension extends Extension
{
    /**
     * If the owner 'page' exists, provide a short title with context
     * @return string
     */
    public function OwnerTitleAndDescription() : string {
        $title = '';
        if($owner = $this->owner->getOwnerPage()) {
            $title = $owner->Title . " (" . $owner->i18n_singular_name() . " #{$owner->ID})";
        }
        return $title;
    }
}
