<?php

namespace NSWDPC\Elemental;

use DNADesign\Elemental\Models\BaseElement;
use DNADesign\Elemental\Models\ElementContent;
use DNADesign\ElementalVirtual\Model\ElementVirtual;
use SilverStripe\Admin\ModelAdmin;
use SilverStripe\Forms\GridField\GridFieldDataColumns;
use SilverStripe\Forms\GridField\GridFieldDeleteAction;
use SilverStripe\Forms\GridField\GridFieldPaginator;
use SilverStripe\Forms\GridField\GridFieldAddNewButton;
use SilverStripe\Forms\GridField\GridFieldImportButton;
use SilverStripe\Forms\GridField\GridFieldPrintButton;
use SilverStripe\Forms\GridField\GridFieldExportButton;
use SilverStripe\Core\Convert;
use SilverStripe\Core\Injector\Injector;
use Symbiote\GridFieldExtensions\GridFieldOrderableRows;

/**
 * An Elemental model administration area
 * @author James
 */
class ElementalAdmin extends ModelAdmin
{

    /**
     * @var array
     */
    private static $managed_models = [
        BaseElement::class
    ];

    /**
     * @var string
     */
    private static $default_sort = "LastEdited DESC";

    /**
     * @var string
     */
    private static $menu_title = 'Elements';

    /**
     * @var string
     */
    private static $url_segment = 'elements-admin';

    /**
     * Get the list of applicable elements, exclude ElementVirtual if available
     * @return DataList
     */
    public function getList()
    {
        $list = parent::getList();
        if($sort = $this->config()->get('default_sort')) {
            $list = $list->sort($sort);
        } else {
            $list = $list->sort("LastEdited DESC");
        }

        if(class_exists(ElementVirtual::class)) {
            $list = $list->exclude(["ClassName" => ElementVirtual::class ]);
        }

        return $list;
    }

    /**
     * Return the GridField form listing elements
     * @return Form
     */
    public function getEditForm($id = null, $fields = null)
    {
        $form = parent::getEditForm($id, $fields);
        $gf = $form->Fields()->dataFieldByName($this->sanitiseClassName($this->modelClass));

        $paging = $gf->getConfig()->getComponentByType(GridFieldPaginator::class);
        if ($paging) {
            $paging->setItemsPerPage(10);
        }

        $dc = $gf->getConfig()->getComponentByType(GridFieldDataColumns::class);
        if ($dc) {
            $display_fields = [
                'ID' => _t('ElementalModelAdmin.NUM', '#'),
                'Title' => _t('ElementalModelAdmin.TITLE','Title'),
                'Parent.OwnerTitleAndDescription' => _t('ElementalModelAdmin.CONTEXT','Context'),
                'Type' => _t('ElementalModelAdmin.TYPE','Type'),
                'LastEdited.Nice' => _t('ElementalModelAdmin.EDITED','Edited'),
                'Created.Nice' => _t('ElementalModelAdmin.CREATED','Created'),
                'Type' =>  _t('ElementalModelAdmin.TYPE','Type'),
                'Summary' =>  _t('ElementalModelAdmin.SUMMARY','Summary')
            ];
            if(class_exists(ElementVirtual::class)) {
                // This field is provided by ElementVirtual component
                $display_fields['AvailableGlobally.Nice'] = _t('ElementalModelAdmin.GLOBAL','Global');
            }
            $dc->setDisplayFields($display_fields);
        }

        $gf->getConfig()
            ->removeComponentsByType([
                GridFieldOrderableRows::class,// no ordering allowed
                GridFieldDeleteAction::class,// do not allow delete
                GridFieldAddNewButton::class,// do not allow adding new elements
                GridFieldImportButton::class,
                GridFieldExportButton::class,
                GridFieldPrintButton::class
            ]);

        return $form;
    }
}
