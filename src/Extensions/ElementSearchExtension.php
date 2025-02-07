<?php

namespace NSWDPC\Elemental\ModelAdmin\Extensions;

use DNADesign\Elemental\Models\BaseElement;
use SilverStripe\Core\Extension;
use SilverStripe\Forms\DropdownField;
use SilverStripe\ORM\DataExtension;

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
}
