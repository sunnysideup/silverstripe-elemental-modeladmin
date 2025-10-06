<?php

namespace NSWDPC\Elemental\ModelAdmin\Controllers;

use DNADesign\Elemental\Models\BaseElement;
use DNADesign\ElementalVirtual\Model\ElementVirtual;
use SilverStripe\Admin\ModelAdmin;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldDataColumns;
use SilverStripe\Forms\GridField\GridFieldDeleteAction;
use SilverStripe\Forms\GridField\GridFieldPaginator;
use SilverStripe\Forms\GridField\GridFieldAddNewButton;
use SilverStripe\Forms\GridField\GridFieldImportButton;
use SilverStripe\Forms\GridField\GridFieldPrintButton;
use SilverStripe\Forms\GridField\GridFieldExportButton;
use SilverStripe\Forms\GridField\GridFieldFilterHeader;
use SilverStripe\Core\Injector\Injector;
use Symbiote\GridFieldExtensions\GridFieldOrderableRows;
use SilverStripe\Core\Config\Config;
use SilverStripe\Forms\Form;
use SilverStripe\ORM\DataObject;

/**
 * An Elemental model administration area
 *
 */
class ElementModelAdmin extends ModelAdmin
{
    /**
     * @var array
     */
    private static $managed_models = [
        BaseElement::class,
    ];

    /**
     * @var array
     */
    private static $excluded_managed_models = [];

    /**
     * @var string
     */
    private static $default_sort = "LastEdited DESC";

    /**
     * @var string
     */
    private static $menu_title = 'Blocks';

    /**
     * @var string
     */
    private static $url_segment = 'blocks-admin';

    /**
     * Get the list of applicable elements, exclude ElementVirtual if available
     * @return DataList
     */
    public function getList()
    {
        $list = parent::getList();
        $list = $list->exclude(['ClassName:not' => $this->modelClass]);
        if ($sort = $this->config()->get('default_sort')) {
            $list = $list->sort($sort);
        } else {
            $list = $list->sort("LastEdited DESC");
        }

        if (class_exists(ElementVirtual::class)) {
            $list = $list->exclude(["ClassName" => ElementVirtual::class]);
        }

        return $list;
    }

    public function getManagedModels()
    {
        $list = array_merge(
            Config::inst()->get(static::class, 'managed_models'),
            array_values(ClassInfo::subclassesFor(BaseElement::class, false))
        );
        Config::modify()->set(static::class, 'managed_models', $list);
        $excluded = Config::inst()->get(static::class, 'excluded_managed_models');
        $list = parent::getManagedModels();
        foreach ($list as $key => $values) {
            $remove = false;
            if (in_array($values['dataClass'], $excluded)) {
                $remove = true;
            }
            if (class_exists(ElementVirtual::class) && $values['dataClass'] === ElementVirtual::class) {
                $remove = true;
            }
            // $obj = Injector::inst()->get($values['dataClass']);
            // if (! $obj->canCreate()) {
            //     $remove = true;
            // }

            $count = DataObject::singleton($values['dataClass'])->get()->filter(['ClassName' => $values['dataClass']])->count();
            if ($count < 1) {
                $remove = true;
            } else {
                $list[$key]['title'] .= ' (' . $count . ')';
            }
            if ($remove) {
                unset($list[$key]);
                continue;
            }
            $meaninglessWords = ['Columns', 'Column', 'Blocks', 'Block', 'Elemental', 'Element'];
            $before = $list[$key]['title'];
            $list[$key]['title'] = trim(str_replace($meaninglessWords, '', $list[$key]['title']));
            if (!$list[$key]['title']) {
                $list[$key]['title'] = $before;
            }
        }
        if (empty($list)) {
            $list = [
                [
                    'title' => 'No blocks available',
                    'dataClass' => BaseElement::class
                ]
            ];
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
        if (!$gf) {
            foreach ($form->Fields() as $field) {
                if ($field instanceof GridField) {
                    $gf = $field;
                    break;
                }
            }
        }
        if ($gf) {

            $paging = $gf->getConfig()->getComponentByType(GridFieldPaginator::class);
            if ($paging) {
                $paging->setItemsPerPage(10);
            }

            $dc = $gf->getConfig()->getComponentByType(GridFieldDataColumns::class);
            if ($dc) {
                $obj = Injector::inst()->get($this->modelClass);
                $summaryFields = $obj->SummaryFields();
                $displayFields = [
                    'Title' => _t('ElementalModelAdmin.TITLE', 'Title'),
                    'Parent.OwnerTitleAndDescription' => _t('ElementalModelAdmin.CONTEXT', 'Context'),
                ];
                $displayFields = array_merge($displayFields, $summaryFields);
                if (class_exists(ElementVirtual::class)) {
                    // This field is provided by ElementVirtual component
                    $displayFields['AvailableGlobally.Nice'] = _t('ElementalModelAdmin.GLOBAL', 'Global');
                }
                $dc->setDisplayFields($displayFields);
            }

            $gf->getConfig()
                ->removeComponentsByType([
                    GridFieldOrderableRows::class, // no ordering allowed
                    GridFieldDeleteAction::class, // do not allow delete
                    GridFieldAddNewButton::class, // do not allow adding new elements
                    GridFieldImportButton::class,
                    GridFieldExportButton::class,
                    GridFieldPrintButton::class
                ]);

            // Apply the block type filter header, added in ElementSearchExtension
            $this->applyBlockTypeFilter($gf);
        }
        return $form;
    }

    /**
     * Apply a block type filter to the search context
     * @param $gf GridField with a filter header
     * @return void
     */
    protected function applyBlockTypeFilter(GridField &$gf)
    {
        $gfConfig = $gf->getConfig();
        // add field with search context callback
        /** @var GridFieldFilterHeader $filterHeader */
        $filterHeader = $gfConfig->getComponentByType(GridFieldFilterHeader::class);
        $searchContext = $filterHeader->getSearchContext($gf);
        $fields = $searchContext->getFields();
        if ($fields) {
            $sourceBlockTypes = ClassInfo::subclassesFor(BaseElement::class, false);
            $filterSource = [];
            foreach ($sourceBlockTypes as $k => $className) {
                $inst = Injector::inst()->get($className);
                $filterSource[$className] = $inst->getType();
            }
            asort($filterSource);
            $fields->push(
                DropdownField::create(
                    'ClassName',
                    _t(
                        'ElementalModelAdmin.BLOCK_TYPE',
                        'Content block type'
                    ),
                    $filterSource
                )->setEmptyString(
                    _t(
                        'ElementalModelAdmin.BLOCK_TYPE_SELECT',
                        'Filter by a content block type'
                    )
                )
            );
        }
    }
}
