<?php

namespace NSWDPC\Elemental\ModelAdmin\Tests;

use SilverStripe\Dev\SapphireTest;
use DNADesign\Elemental\Models\BaseElement;
use DNADesign\Elemental\Models\ElementContent;
use DNADesign\Elemental\Models\ElementalArea;

/**
 * Test moving an element
 * @author James
 */
class MoveElementExtensionTest extends SapphireTest {

    /**
     * @inheritdoc
     */
    protected $usesDatabase = true;

    public function testMoveElement() {

        // Create two ElementalAreas
        $sourceArea = ElementalArea::create();
        $sourceArea->OwnerClassName = 'Page';
        $sourceArea->write();

        $sourcePage = \Page::create();
        $sourcePage->Title = 'Source Page';
        $sourcePage->ElementalAreaID = $sourceArea->ID;
        $sourcePage->write();

        $targetArea = ElementalArea::create();
        $targetArea->OwnerClassName = 'Page';
        $targetArea->write();

        $targetPage = \Page::create();
        $targetPage->Title = 'Target Page';
        $targetPage->ElementalAreaID = $targetArea->ID;
        $targetPage->write();

        // Create a BaseElement and assign it to the source area
        $element = ElementContent::create();
        $element->Title = "Test element";
        $element->ParentID = $sourceArea->ID;
        $element->write();

        // check applicable element areas
        $areas = $element->getApplicableElementalAreas(true);
        $this->assertNull($areas->byId($sourceArea->ID));
        $this->assertNotNull($areas->byId($targetArea->ID));

        // change parent
        $element->ParentID = $targetArea->ID;
        $element->write();

        // Assertions to ensure the element has been moved
        $this->assertEquals($targetArea->ID, $element->ParentID, 'Element should be moved to the target ElementalArea');
        $this->assertNotEquals($sourceArea->ID, $element->ParentID, 'Element should no longer belong to the source ElementalArea');
    }
}
