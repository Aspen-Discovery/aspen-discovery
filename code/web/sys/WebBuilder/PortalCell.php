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
	public $markdown;
	public $sourceInfo;

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
			'markdown' => 'Text/Images',
			'basic_page' => 'Basic Page',
			'basic_page_teaser' => 'Basic Page Teaser',
			'collection_spotlight' => 'Collection Spotlight',
			'custom_form' => 'Form',
			'image' => 'Image',
			'video' => 'Video',
			'vimeo_video' => 'Vimeo Video',
			'youtube_video' => 'YouTube Video',
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
			'sourceType' => ['property'=>'sourceType', 'type'=>'enum', 'values'=>$sourceOptions, 'label'=>'Source Type', 'description'=>'Source type for the content of cell', 'onchange' => 'return AspenDiscovery.WebBuilder.getPortalCellValuesForSource();'],
			'sourceId' => ['property'=>'sourceId', 'type'=>'enum', 'values'=>[], 'label'=>'Source Id', 'description'=>'Source for the content of cell'],
			'markdown' => ['property' => 'markdown', 'type' => 'markdown', 'label' => 'Contents', 'description' => 'Contents of the cell'],
			'sourceInfo' => ['property' => 'sourceInfo', 'type' => 'text', 'label' => 'Source Info', 'description' => 'Additional information for the source'],
		];
	}

	function getContents(){
		global $interface;
		global $configArray;
		if ($this->sourceType == 'markdown') {
			require_once ROOT_DIR . '/sys/Parsedown/AspenParsedown.php';
			$parsedown = AspenParsedown::instance();
			$parsedown->setBreaksEnabled(true);
			return $parsedown->parse($this->markdown);
		}elseif ($this->sourceType == 'collection_spotlight') {
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
		}elseif ($this->sourceType == 'custom_form'){
			require_once ROOT_DIR . '/sys/WebBuilder/CustomForm.php';
			$customForm = new CustomForm();
			$customForm->id = $this->sourceId;
			if ($customForm->find(true)){
				return $customForm->getFormattedFields();
			}
		}elseif ($this->sourceType == 'video'){
			require_once ROOT_DIR . '/sys/File/FileUpload.php';
			$fileUpload = new FileUpload();
			$fileUpload->id = $this->sourceId;
			if ($fileUpload->find(true)){
				$fileSize = filesize($fileUpload->fullPath);
				$interface->assign('fileSize', StringUtils::formatBytes($fileSize));
				$interface->assign('videoPath', $configArray['Site']['url'] . '/Files/' . $this->sourceId . '/Contents');
				return $interface->fetch('Files/embeddedVideo.tpl');
			}
		}elseif ($this->sourceType == 'vimeo_video'){
			$sourceInfo = $this->sourceInfo;
			if (preg_match('~https://vimeo\.com/(.*?)/.*~', $sourceInfo, $matches)){
				$sourceInfo = $matches[1];
			}elseif (preg_match('~https://player\.vimeo\.com/video/(.*)~', $sourceInfo, $matches)){
				$sourceInfo = $matches[1];
			}
			$interface->assign('vimeoId', $sourceInfo);
			return $interface->fetch('WebBuilder/vimeoVideo.tpl');
		}elseif ($this->sourceType == 'youtube_video'){
			$sourceInfo = $this->sourceInfo;
			if (preg_match('~https://youtu\.be/(.*)~', $sourceInfo, $matches)){
				$sourceInfo = $matches[1];
			}elseif (preg_match('~https://www\.youtube\.com/watch?v=(.*?)&feature=youtu.be~', $sourceInfo, $matches)){
				$sourceInfo = $matches[1];
			}
			$interface->assign('youtubeId', $sourceInfo);
			return $interface->fetch('WebBuilder/youtubeVideo.tpl');
		}elseif ($this->sourceType == 'image'){
			require_once ROOT_DIR . '/sys/File/ImageUpload.php';
			$imageUpload = new ImageUpload();
			$imageUpload->id = $this->sourceId;
			if ($imageUpload->find(true)) {
				$size = '';
				if ($this->widthMd <= 2) {
					$size .= '&size=small';
				}elseif ($this->widthMd <= 4){
					$size .= '&size=medium';
//				}elseif ($this->widthMd <= 8){
//					$size .= '&size=large';
//				}else{
//					$size .= '&size=x-large';
				}
				return "<img src='/WebBuilder/ViewImage?id={$imageUpload->id}{$size}' class='img-responsive' onclick=\"AspenDiscovery.WebBuilder.showImageInPopup('{$imageUpload->title}', '{$imageUpload->id}')\" alt='{$imageUpload->title}'>";
			}
		}
		return 'Could not load contents for the cell';
	}

	function getPortalRow(){
		$portalRow = new PortalRow();
		$portalRow->id = $this->portalRowId;
		if ($portalRow->find(true)){
			return $portalRow;
		}else{
			return null;
		}
	}

	/** @noinspection PhpUnused */
	public function isLastCell(){
		$myRow = new PortalRow();
		$myRow->id = $this->portalRowId;
		if ($myRow->find(true)){
			return count($myRow->getCells()) -1 == $this->weight;
		}
		return false;
	}
}