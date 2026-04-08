<?php

namespace NSWDPC\Elemental\ModelAdmin\Extensions;

use SilverStripe\Core\Extension;

/**
 * This extension is applied to {@link DNADesign\Elemental\Models\ElementalArea}
 * It provides a method to retrive the area's owner title and a short description
 * This is used by {@link NSWDPC\Elemental\Extensions\ModelAdmin\MoveElementExtension}
 * to provide some context for an editor selecting the target elemental area
 * @author James
 * @extends \SilverStripe\Core\Extension<(\DNADesign\Elemental\Models\ElementalArea & static)>
 */
class ElementalAreaOwnerExtension extends Extension
{
    /**
     * If the owner 'page' exists, provide a short title with context
     */
    public function OwnerTitleAndDescription(): string
    {
        $title = '';

        // if the ElementalArea has a ContextTitle .. use that
        if ($this->getOwner()->hasMethod('ContextTitle')) {
            $areaTitle = $this->getOwner()->ContextTitle();
        } else {
            $areaTitle = _t('ElementalModelAdmin.MAIN_CONTENT', 'Main content');
        }

        if ($ownerPage = $this->getOwner()->getOwnerPage()) {
            $title = $ownerPage->i18n_singular_name() . " - " . $ownerPage->Title . " (#{$ownerPage->ID})";
        } else {
            // unknown owner, or maybe no longer exists
            // TODO: maybe use OwnerClassName?
            $title = _t('elementadmin.UNKNOWN_OWNER', 'unknown parent record') . " (record #" . $this->getOwner()->ID . ")";
        }

        return $title . " - {$areaTitle} - #{$this->getOwner()->ID}";
    }
}
