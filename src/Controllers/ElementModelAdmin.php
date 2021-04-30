<?php

namespace NSWDPC\Elemental;

use DNADesign\Elemental\Models\BaseElement;
use DNADesign\Elemental\Models\ElementContent;
use SilverStripe\Admin\ModelAdmin;
use SilverStripe\Forms\GridField\GridFieldDataColumns;
use SilverStripe\Forms\GridField\GridFieldDeleteAction;
use SilverStripe\Forms\GridField\GridFieldPaginator;
use SilverStripe\Core\Convert;
use SilverStripe\Core\Injector\Injector;
use Symbiote\GridFieldExtensions\GridFieldOrderableRows;

class ElementalAdmin extends ModelAdmin
{
    private static $managed_models = [
        BaseElement::class,
        ElementContent::class
    ];

    private static $default_sort = "LastEdited DESC";

    private static $menu_title = 'Elements';
    private static $url_segment = 'elements-admin';

    public function getList()
    {
        $list = parent::getList();
        if($sort = $this->config()->get('default_sort')) {
            $list = $list->sort($sort);
        }
        if ($this->modelClass == BaseElement::class) {
            //exclude Content Blocks from BaseElement query
            $list = $list->exclude(["ClassName" => ElementContent::class ]);
        }
        $list = $list->sort("LastEdited DESC");
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
            switch ($this->modelClass) {
                case ElementContent::class:
                    $display_fields = [
                        'ID' => '#',
                        'Title' => 'Title',
                        'Parent.OwnerTitleAndDescription' => 'Context',
                        'Type' => 'Type',
                        'LastEdited.Nice' => 'Edited',
                        'Created.Nice' => 'Created',
                        'AvailableGlobally.Nice' => 'Global',
                        'Summary' =>  'Summary',
                    ];
                    break;
                case BaseElement::class:
                default:
                    $display_fields = [
                        'ID' => '#',
                        'Title' => 'Title',
                        'Parent.OwnerTitleAndDescription' => 'Context',
                        'Type' => 'Type',
                        'LastEdited.Nice' => 'Edited',
                        'Created.Nice' => 'Created',
                        'AvailableGlobally.Nice' => 'Global',
                        'Type' =>  'Type',
                        'Summary' =>  'Summary',
                    ];
                    break;
            }

            $dc->setDisplayFields($display_fields);
        }

        $gf->getConfig()
            ->removeComponentsByType(GridFieldOrderableRows::class)// no ordering allowed
            ->removeComponentsByType(GridFieldDeleteAction::class);// do not allow delete

        return $form;
    }
}
