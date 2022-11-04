<?php

namespace NSWDPC\Elemental\Extensions\ModelAdmin;

use SilverStripe\ORM\DataExtension;

/**
 * A hackish way of getting the title to appear first for searches
 */
class ElementSearchExtension extends DataExtension
{
    public function updateSearchableFields(&$fields)
    {
        $orderedFields = [];
        if (isset($fields['Title'])) {
            $orderedFields['Title'] = $fields['Title'];
        }
        foreach ($fields as $k=>$v) {
            if ($k == "Title") {
                continue;
            }
            $orderedFields[ $k ] = $fields[ $k ];
        }
        $fields = $orderedFields;
    }
}
