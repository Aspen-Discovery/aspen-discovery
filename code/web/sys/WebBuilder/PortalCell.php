<?php


class PortalCell extends DataObject
{
	public $__table = 'web_builder_portal_cell';
	public $id;
	public $portalRowId;
	public $weight;

	public /** @noinspection PhpUnused */ $widthTiny;
	public /** @noinspection PhpUnused */ $widthXs;
	public /** @noinspection PhpUnused */ $widthSm;
	public /** @noinspection PhpUnused */ $widthMd;
	public /** @noinspection PhpUnused */ $widthLg;

	public /** @noinspection PhpUnused */ $verticalAlignment;
	public /** @noinspection PhpUnused */ $horizontalJustification;

	public $sourceType;
	public $sourceId;

	static function getObjectStructure() {
		$verticalAlignmentOptions = [
			'flex-start' => 'Top of Row',
			'flex-end' => 'Bottom of Row',
			'center' => 'Middle of Row',
			'stretch' => 'Fill Row',
			'baseline' => 'Baseline'
		];
		$horizontalJustificationOptions = [
			'start' => 'Left',
			'center' => 'Center',
			'end' => 'Right'
		];
		$sourceOptions = [
			'basic_page' => 'Basic Page',
			'basic_page_teaser' => 'Basic Page Teaser',
			'collection_spotlight' => 'Collection Spotlight',
			'event_calendar' => 'Event Calendar',
			'event_spotlight' => 'Event Spotlight'
		];
		return [
			'id' => array('property' => 'id', 'type' => 'label', 'label' => 'Id', 'description' => 'The unique id within the database'),
			'portalRowId' => array('property'=>'portalRowId', 'type'=>'label', 'label'=>'Portal Row', 'description'=>'The parent row'),
			'weight' => array('property' => 'weight', 'type' => 'numeric', 'label' => 'Weight', 'weight' => 'Defines how items are sorted.  Lower weights are displayed higher.', 'required'=> true),
			'widthTiny' => ['property'=>'widthTiny', 'type'=>'integer', 'label'=>'Column Width Tiny Size', 'description'=>'The width of the column when viewed at tiny size', 'min'=>1, 'max'=>'12', 'default'=>12],
			'widthXs' => ['property'=>'widthXs', 'type'=>'integer', 'label'=>'Column Width Extra Small Size', 'description'=>'The width of the column when viewed at extra small size', 'min'=>1, 'max'=>'12', 'default'=>12],
			'widthSm' => ['property'=>'widthSm', 'type'=>'integer', 'label'=>'Column Width Small Size', 'description'=>'The width of the column when viewed at small size', 'min'=>1, 'max'=>'12', 'default'=>12],
			'widthMd' => ['property'=>'widthMd', 'type'=>'integer', 'label'=>'Column Width Medium Size', 'description'=>'The width of the column when viewed at Medium size', 'min'=>1, 'max'=>'12', 'default'=>12],
			'widthLg' => ['property'=>'widthLg', 'type'=>'integer', 'label'=>'Column Width Large Size', 'description'=>'The width of the column when viewed at Large size', 'min'=>1, 'max'=>'12', 'default'=>12],
			'verticalAlignment' => ['property'=>'verticalAlignment', 'type'=>'enum', 'values'=>$verticalAlignmentOptions, 'label'=>'Vertical Alignment', 'description'=>'Vertical alignment of the cell', 'default'=>'stretch'],
			'horizontalJustification' => ['property'=>'horizontalJustification', 'type'=>'enum', 'values'=>$horizontalJustificationOptions, 'label'=>'Horizontal Justification', 'description'=>'Horizontal Justification of the cell', 'default'=>'start'],
			'sourceType' => ['property'=>'sourceType', 'type'=>'enum', 'values'=>$sourceOptions, 'label'=>'Source Type', 'description'=>'Source type for the content of cell', 'onchange' => 'return AspenDiscovery.WebBuilder.getPortalCellValuesForSource(\'~id~\');'],
			'sourceId' => ['property'=>'sourceId', 'type'=>'enum', 'values'=>[], 'label'=>'Source Id', 'description'=>'Source for the content of cell'],
		];
	}

	function getContents(){
		global $interface;
		if ($this->sourceType == 'collection_spotlight') {
			require_once ROOT_DIR . '/sys/LocalEnrichment/CollectionSpotlight.php';
			$collectionSpotlight = new CollectionSpotlight();
			$collectionSpotlight->id = $this->sourceId;
			if ($collectionSpotlight->find(true)){
				$interface->assign('collectionSpotlight', $collectionSpotlight);
				return $interface->fetch('CollectionSpotlight/collectionSpotlightTabs.tpl');
			}
		}elseif ($this->sourceType == 'basic_page'){
			require_once ROOT_DIR . '/sys/WebBuilder/BasicPage.php';
			$basicPage = new BasicPage();
			$basicPage->id = $this->sourceId;
			if ($basicPage->find(true)){
				return $basicPage->getFormattedContents();
			}
		}elseif ($this->sourceType == 'basic_page_teaser'){
			require_once ROOT_DIR . '/sys/WebBuilder/BasicPage.php';
			$basicPage = new BasicPage();
			$basicPage->id = $this->sourceId;
			if ($basicPage->find(true)){
				return $basicPage->teaser;
			}
		}
		return 'Could not load contents for the cell';
	}
}