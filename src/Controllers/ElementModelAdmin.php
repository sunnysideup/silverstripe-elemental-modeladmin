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

use SilverStripe\Core\ClassInfo;

use SilverStripe\Core\Config\Config;
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
     * @var array
     */
    private static $excluded_managed_models = [
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

    public function getManagedModels()
    {
        $list =
            Config::inst()->get(static::class, 'managed_models')
            + array_values(ClassInfo::subclassesFor(BaseElement::class, false));
        Config::modify()->set(static::class, 'managed_models', $list);
        $excluded = Config::inst()->get(static::class, 'excluded_managed_models');
        $list = parent::getManagedModels();
        foreach($list as $key => $values) {
            $remove = false;
            if(in_array($values['dataClass'], $excluded)) {
                $remove = true;
            }
            if(class_exists(ElementVirtual::class) && $values['dataClass'] === ElementVirtual::class) {
                $remove = true;
            }
            $obj = Injector::inst()->get( $values['dataClass']);
            if(! $obj->canCreate()) {
                $remove = true;
            }
            if($remove) {
                unset($list[$key]);
                continue;
            }
            $list[$key]['title'] = trim(str_ireplace(['Blocks', 'Block'], '', $values['title']));
            if(!($list[$key]['title'] )) {
                $list[$key]['title']  = 'Blocks';
            }
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
                'Title' => _t('ElementalModelAdmin.TITLE','Title'),
                'Parent.OwnerTitleAndDescription' => _t('ElementalModelAdmin.CONTEXT','Context'),
                'Type' => _t('ElementalModelAdmin.TYPE','Type'),
                'Created.Ago' => _t('ElementalModelAdmin.CREATED','Created'),
                'LastEdited.Ago' => _t('ElementalModelAdmin.EDITED','Edited'),
                'AvailableGlobally.Nice' => _t('ElementalModelAdmin.GLOBAL','Global'),
                'Summary' =>  _t('ElementalModelAdmin.SUMMARY','Summary')
            ];
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
