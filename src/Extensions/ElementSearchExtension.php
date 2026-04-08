<?php

declare(strict_types=1);

namespace NSWDPC\Elemental\ModelAdmin\Extensions;

use SilverStripe\Forms\DropdownField;
use SilverStripe\ORM\DataExtension;

/**
 * A hackish way of getting the title to appear first for searches
 * @extends \SilverStripe\ORM\DataExtension<(\DNADesign\Elemental\Models\BaseElement & static)>
 */
class ElementSearchExtension extends DataExtension
{
    public function updateSearchableFields(array &$fields)
    {
        $orderedFields = [];
        if (isset($fields['Title'])) {
            $orderedFields['Title'] = $fields['Title'];
        }

        foreach (array_keys($fields) as $k) {
            if ($k == "Title") {
                continue;
            }

            $orderedFields[ $k ] = $fields[ $k ];
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
