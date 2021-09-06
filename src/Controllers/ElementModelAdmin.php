<?php

namespace NSWDPC\Elemental;

use DNADesign\Elemental\Models\BaseElement;
use DNADesign\Elemental\Models\ElementContent;
use DNADesign\ElementalVirtual\Model\ElementVirtual;
use SilverStripe\Admin\ModelAdmin;
use SilverStripe\Forms\GridField\GridFieldDataColumns;
use SilverStripe\Forms\GridField\GridFieldDeleteAction;
use SilverStripe\Forms\GridField\GridFieldPaginator;
use SilverStripe\Core\Convert;
use SilverStripe\Core\Injector\Injector;
use Symbiote\GridFieldExtensions\GridFieldOrderableRows;

class ElementalAdmin extends ModelAdmin
{

    /**
     * @var array
     */
    private static $managed_models = [
        BaseElement::class
    ];

    private static $default_sort = "LastEdited DESC";

    private static $menu_title = 'Elements';
    private static $url_segment = 'elements-admin';

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
                'AvailableGlobally.Nice' => _t('ElementalModelAdmin.GLOBAL','Global'),
                'Type' =>  _t('ElementalModelAdmin.TYPE','Type'),
                'Summary' =>  _t('ElementalModelAdmin.SUMMARY','Summary')
            ];
            $dc->setDisplayFields($display_fields);
        }

        $gf->getConfig()
            ->removeComponentsByType(GridFieldOrderableRows::class)// no ordering allowed
            ->removeComponentsByType(GridFieldDeleteAction::class);// do not allow delete

        return $form;
    }
}
