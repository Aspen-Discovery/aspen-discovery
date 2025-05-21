<?php


class PortalCell extends DataObject {
	public $__table = 'web_builder_portal_cell';
	public $id;
	public $portalRowId;
	public $weight;
	public $customImage;
	public $hideDescription;

	public $title;

	public /** @noinspection PhpUnused */
		$widthTiny;
	public /** @noinspection PhpUnused */
		$widthXs;
	public /** @noinspection PhpUnused */
		$widthSm;
	public /** @noinspection PhpUnused */
		$widthMd;
	public /** @noinspection PhpUnused */
		$widthLg;

	public /** @noinspection PhpUnused */
		$verticalAlignment;
	public /** @noinspection PhpUnused */
		$horizontalJustification;

	public $sourceType;
	public $sourceId;
	public $markdown;
	public $sourceInfo;
	public $frameHeight;
	public $makeCellAccordion;
	public $imageURL;
	public $pdfView;
	public $imgAction;
	public $imgAlt;
	public $colorScheme;
	public $invertColor;
	public $staticLocationId;

	public function getUniquenessFields(): array {
		return [
			'portalRowId',
			'weight',
		];
	}

	public function getNumericColumnNames(): array {
		return ['frameHeight', 'invertColor', 'staticLocationId'];
	}

	static function getObjectStructure($context = ''): array {
		$verticalAlignmentOptions = [
			'flex-start' => 'Top of Row',
			'flex-end' => 'Bottom of Row',
			'center' => 'Middle of Row',
			'stretch' => 'Fill Row',
			'baseline' => 'Baseline',
		];
		$horizontalJustificationOptions = [
			'left' => 'Left',
			'center' => 'Center',
			'right' => 'Right',
			'justify' => 'Justified',
		];
		$sourceOptions = [
			'markdown' => 'Text/Images',
			'basic_page' => 'Basic Page',
			'basic_page_teaser' => 'Basic Page Teaser',
			'collection_spotlight' => 'Collection Spotlight',
			'custom_form' => 'Form',
			'image' => 'Image',
			'pdf' => 'PDF',
			'iframe' => 'iFrame',
			'vimeo_video' => 'Vimeo Video',
			'youtube_video' => 'YouTube Video',
			'hours_locations' => 'Library Hours and Locations',
			'web_resource' => 'Web Resource',
			'quick_poll' => 'Quick Poll',
			'custom_web_resource_page' => 'Custom Web Resource Page'
		];
		$colorOptions = [
			'default' => 'default',
			'primary' => 'primary',
			'secondary' => 'secondary',
			'tertiary' => 'tertiary',
		];
		$imgActionOptions = [
			'0' => 'None',
			'1' => 'Open a URL in a new tab',
			'2' => 'Open a URL in the same tab',
			'3' => 'Enlarge the image in a pop-up modal',
		];

		require_once ROOT_DIR . '/sys/LibraryLocation/Location.php';
		$locationOptions = [];
		$tmpLocation = new Location();
		$tmpLocation->showInLocationsAndHoursList = 1;
		$tmpLocation->orderBy('isMainBranch DESC, displayName');
		$tmpLocation->find();
		while ($tmpLocation->fetch()) {
			$locationOptions[$tmpLocation->locationId] = $tmpLocation->displayName;
		}

		return [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id within the database',
			],
			'portalRowId' => [
				'property' => 'portalRowId',
				'type' => 'label',
				'label' => 'Portal Row',
				'description' => 'The parent row',
			],
			'weight' => [
				'property' => 'weight',
				'type' => 'numeric',
				'label' => 'Weight',
				'weight' => 'Defines how items are sorted.  Lower weights are displayed higher.',
				'required' => true,
			],
			'title' => [
				'property' => 'title',
				'type' => 'text',
				'label' => 'Title',
				'description' => 'An optional title for the cell',
				'canLock' => false,
			],
			'layoutSettingsSection' => [
				'property' => 'layoutSettingsSection',
				'type' => 'section',
				'label' => 'Layout Settings',
				'hideInLists' => true,
				'properties' => [
					'widthTiny' => [
						'property' => 'widthTiny',
						'type' => 'integer',
						'label' => 'Column Width Tiny Size',
						'description' => 'The width of the column when viewed at tiny size',
						'min' => 1,
						'max' => '12',
						'default' => 12,
					],
					'widthXs' => [
						'property' => 'widthXs',
						'type' => 'integer',
						'label' => 'Column Width Extra Small Size',
						'description' => 'The width of the column when viewed at extra small size',
						'min' => 1,
						'max' => '12',
						'default' => 12,
					],
					'widthSm' => [
						'property' => 'widthSm',
						'type' => 'integer',
						'label' => 'Column Width Small Size',
						'description' => 'The width of the column when viewed at small size',
						'min' => 1,
						'max' => '12',
						'default' => 12,
					],
					'widthMd' => [
						'property' => 'widthMd',
						'type' => 'integer',
						'label' => 'Column Width Medium Size',
						'description' => 'The width of the column when viewed at Medium size',
						'min' => 1,
						'max' => '12',
						'default' => 12,
					],
					'widthLg' => [
						'property' => 'widthLg',
						'type' => 'integer',
						'label' => 'Column Width Large Size',
						'description' => 'The width of the column when viewed at Large size',
						'min' => 1,
						'max' => '12',
						'default' => 12,
					],
					'verticalAlignment' => [
						'property' => 'verticalAlignment',
						'type' => 'enum',
						'values' => $verticalAlignmentOptions,
						'label' => 'Vertical Alignment',
						'description' => 'Vertical alignment of the cell',
						'default' => 'stretch',
					],
					'horizontalJustification' => [
						'property' => 'horizontalJustification',
						'type' => 'enum',
						'values' => $horizontalJustificationOptions,
						'label' => 'Horizontal Justification',
						'description' => 'Horizontal Justification of the cell',
						'default' => 'start',
					],
					'makeCellAccordion' => [
						'property' => 'makeCellAccordion',
						'type' => 'checkbox',
						'label' => 'Make cell accordion (Title is required to use)',
						'description' => 'Make the entire cell contents an accordion box',
						'onchange' => 'return AspenDiscovery.Admin.updateMakeCellAccordion();',
					],
				],
			],
			'designSettingsSection' => [
				'property' => 'designSettingsSection',
				'type' => 'section',
				'label' => 'Design Options',
				'hideInLists' => true,
				'properties' => [
					'colorScheme' => [
						'property' => 'colorScheme',
						'type' => 'webBuilderColor',
						'label' => 'Select a Color Scheme for Cell',
						'colorOptions' => $colorOptions,
						'description' => 'Pick the colors from on theme settings',
					],
					'invertColor' => [
						'property' => 'invertColor',
						'type' => 'checkbox',
						'label' => 'Invert background and foreground colors',
						'description' => 'Changes the background to be the text color and text color to be the background',
					],
				],
			],
			'sourceType' => [
				'property' => 'sourceType',
				'type' => 'enum',
				'values' => $sourceOptions,
				'label' => 'Source Type',
				'description' => 'Source type for the content of cell',
				'onchange' => 'return AspenDiscovery.WebBuilder.getPortalCellValuesForSource();',
			],
			'sourceId' => [
				'property' => 'sourceId',
				'type' => 'enum',
				'values' => [],
				'label' => 'Source Id',
				'description' => 'Source for the content of cell',
			],
			'staticLocationId' => [
				'property' => 'staticLocationId',
				'type' => 'enum',
				'values' => ['-1' => 'Dynamic (Allow User Selection)'] + $locationOptions,
				'label' => 'Static Location',
				'description' => 'Select a location to display (static). Leave as Dynamic to allow user selection.',
				'default' => -1,
			],
			'hideDescription' => [
				'property' => 'hideDescription',
				'type' => 'checkbox',
				'label' => 'Hide description',
				'description' => 'Hide the description in the cell',
				'default' => false,
			],
			'markdown' => [
				'property' => 'markdown',
				'type' => 'markdown',
				'label' => 'Contents',
				'description' => 'Contents of the cell',
			],
			'sourceInfo' => [
				'property' => 'sourceInfo',
				'type' => 'text',
				'label' => 'Source Info',
				'description' => 'Additional information for the source',
			],
			'imgAction' => [
				'property' => 'imgAction',
				'type' => 'enum',
				'values' => $imgActionOptions,
				'label' => 'Image action',
				'description' => 'What should happen when a user clicks on the image',
				'onchange' => 'return AspenDiscovery.WebBuilder.getImageActionFields();',
			],
			'imageURL' => [
				'property' => 'imageURL',
				'type' => 'text',
				'label' => 'URL to link image to',
				'description' => 'URL to link image to',
			],
			'imgAlt' => [
				'property' => 'imgAlt',
				'type' => 'text',
				'label' => 'Image alt description',
				'description' => 'Text description of the image for ALT tag',
			],
			'frameHeight' => [
				'property' => 'frameHeight',
				'type' => 'integer',
				'label' => 'Height for iFrame',
				'description' => 'Set the height for the iFrame in pixels',
			],
			'pdfView' => [
				'property' => 'pdfView',
				'type' => 'enum',
				'values' => [
					'embedded' => 'Embedded in Cell',
					'thumbnail' => 'Thumbnail Link',
				],
				'label' => 'Display the PDF',
				'description' => 'How the page should display the PDF',
			],
			'customImage' => [
				'property' => 'customImage',
				'type' => 'image',
				'label' => 'Custom Image',
				'maxWidth' => 400,
				'hideInLists' => true,
			]
		];
	}

	function getContents($inPageEditor) {
		global $interface;
		global $activeLanguage;
		global $configArray;
		$contents = '';
		if (!empty($this->title) && $this->makeCellAccordion != '1') {
			$contents .= "<h2>{$this->title}</h2>";
		}
		if ($this->makeCellAccordion == '1') {
			$contents .= "<div class='panel customAccordionCell' id='Cell-$this->id-Panel'>";
			$contents .= "<a data-toggle='collapse' href='#Cell-$this->id-PanelBody'>";
			$contents .= "<div class='panel-heading'>";
			$contents .= "<div class='panel-title'>";
			$contents .= "$this->title";
			$contents .= "</div></div></a>";

			$contents .= "<div id='Cell-$this->id-PanelBody' class='panel-collapse collapse'>";
			$contents .= "<div class='panel-body'>";
		}
		if ($this->sourceType == 'markdown') {
			require_once ROOT_DIR . '/sys/Parsedown/AspenParsedown.php';
			$parsedown = AspenParsedown::instance();
			$parsedown->setBreaksEnabled(true);
			$contents .= $parsedown->parse($this->markdown);
		} elseif ($this->sourceType == 'collection_spotlight') {
			require_once ROOT_DIR . '/sys/LocalEnrichment/CollectionSpotlight.php';
			$collectionSpotlight = new CollectionSpotlight();
			$collectionSpotlight->id = $this->sourceId;
			if ($collectionSpotlight->find(true)) {
				$interface->assign('collectionSpotlight', $collectionSpotlight);
				$contents .= $interface->fetch('CollectionSpotlight/collectionSpotlightTabs.tpl');
			}
		} elseif ($this->sourceType == 'basic_page') {
			require_once ROOT_DIR . '/sys/WebBuilder/BasicPage.php';
			$basicPage = new BasicPage();
			$basicPage->id = $this->sourceId;
			if ($basicPage->find(true)) {
				$contents .= $basicPage->getFormattedContents();
			}
		} elseif ($this->sourceType == 'basic_page_teaser') {
			require_once ROOT_DIR . '/sys/WebBuilder/BasicPage.php';
			$basicPage = new BasicPage();
			$basicPage->id = $this->sourceId;
			if ($basicPage->find(true)) {
				$contents .= $basicPage->teaser;
			}
		} elseif ($this->sourceType == 'custom_form') {
			require_once ROOT_DIR . '/sys/WebBuilder/CustomForm.php';
			$customForm = new CustomForm();
			$customForm->id = $this->sourceId;
			if ($customForm->find(true)) {
				if ($inPageEditor) {
					$contents .= "<h2>$customForm->title</h2><div>(fields not displayed while editing the page)</div>";
				} else {
					$oldId = $interface->getVariable("id");
					$interface->assign("id", $customForm->id);
					$contents .= $customForm->getFormattedFields();
					$interface->assign("id", $oldId);
				}
			}
		} elseif ($this->sourceType == 'vimeo_video') {
			$sourceInfo = $this->sourceInfo;
			if (preg_match('~https://vimeo\.com/(.*?)/.*~', $sourceInfo, $matches)) {
				$sourceInfo = $matches[1];
			} elseif (preg_match('~https://player\.vimeo\.com/video/(.*)~', $sourceInfo, $matches)) {
				$sourceInfo = $matches[1];
			}
			$interface->assign('vimeoId', $sourceInfo);
			$contents .= $interface->fetch('WebBuilder/vimeoVideo.tpl');
		} elseif ($this->sourceType == 'youtube_video') {
			$sourceInfo = $this->sourceInfo;
			if (preg_match('~https://youtu\.be/(.*)~', $sourceInfo, $matches)) {
				$sourceInfo = $matches[1];
			} elseif (preg_match('~https://www\.youtube\.com/watch?v=(.*?)&feature=youtu.be~', $sourceInfo, $matches)) {
				$sourceInfo = $matches[1];
			}
			$interface->assign('youtubeId', $sourceInfo);
			$contents .= $interface->fetch('WebBuilder/youtubeVideo.tpl');
		} elseif ($this->sourceType == 'image') {
			require_once ROOT_DIR . '/sys/File/ImageUpload.php';
			$imageUpload = new ImageUpload();
			$imageUpload->id = $this->sourceId;
			$imageAction = $this->imgAction;
			if ($imageAction == 1 || $imageAction == 2) {
				$imageLinkURL = $this->imageURL;
			}
			$imageAlt = htmlentities($this->imgAlt ?? $imageUpload->title, ENT_QUOTES);
			if ($imageUpload->find(true)) {
				$size = '';
				if ($this->widthMd <= 2) {
					$size .= '&size=small';
				} elseif ($this->widthMd <= 4) {
					$size .= '&size=medium';
				} elseif ($this->widthMd <= 8) {
					$size .= '&size=large';
				} else {
					$size .= '&size=x-large';
				}
				if (($imageAction == "1" || $imageAction == "2") && !empty($imageLinkURL)) {
					if ($imageAction == "1") {
						$target = "_blank";
					} else {
						$target = "_self";
					}
					$contents .= "<a href='{$imageLinkURL}' target='{$target}'><img src='/WebBuilder/ViewImage?id={$imageUpload->id}{$size}' class='img-responsive' alt='{$imageAlt}'></a>";
				} elseif ($imageAction == "3") {
					$contents .= "<img src='/WebBuilder/ViewImage?id={$imageUpload->id}{$size}' class='img-responsive' onclick=\"AspenDiscovery.WebBuilder.showImageInPopup('{$imageUpload->title}', '{$imageUpload->id}')\" alt='{$imageAlt}'>";
				} else {
					$contents .= "<img src='/WebBuilder/ViewImage?id={$imageUpload->id}{$size}' class='img-responsive' alt='{$imageAlt}'>";
				}
			}
		} elseif ($this->sourceType == 'pdf') {
			require_once ROOT_DIR . '/sys/File/FileUpload.php';
			$pdf = new FileUpload();
			$pdf->type = 'web_builder_pdf';
			$pdf->id = $this->sourceId;
			if ($pdf->find(true)) {
				if ($this->pdfView == 'thumbnail') {
					$contents .= "<a href='/Files/{$pdf->id}/ViewPDF'><img src='/WebBuilder/ViewThumbnail?id={$pdf->id}' class='img-responsive img-thumbnail' alt='{$pdf->title}'></a>";
				} elseif ($this->pdfView == 'embedded') {
					$interface->assign('pdfPath', $configArray['Site']['url'] . '/Files/' . $pdf->id . '/Contents');
					$contents .= $interface->fetch('WebBuilder/pdfViewer.tpl');
				}
			}
		} elseif ($this->sourceType == 'iframe') {
			$sourceInfo = $this->sourceInfo;
			$frameHeight = $this->frameHeight;
			$interface->assign('sourceURL', $sourceInfo);
			$interface->assign('frameHeight', $frameHeight);
			$contents .= $interface->fetch('WebBuilder/iframe.tpl');
		} elseif ($this->sourceType == 'web_resource') {
			require_once ROOT_DIR . '/sys/WebBuilder/WebResource.php';
			$webResource = new WebResource();
			$webResource->id = $this->sourceId;
			if ($webResource->find(true)) {
				require_once ROOT_DIR . '/RecordDrivers/WebResourceRecordDriver.php';
				$resourceDriver = new WebResourceRecordDriver('WebResource:' . $webResource->id);
				if ($resourceDriver->isValid()) {
					if (!$this->hideDescription) {
						$interface->assign('description', $webResource->getFormattedDescription());
					}
					$interface->assign('title', $webResource->name);
					$interface->assign('url', $webResource->url);
					$interface->assign('logo', $resourceDriver->getBookcoverUrl('large'));

					$contents .= $interface->fetch('WebBuilder/resource.tpl');
				} else {
					//TODO: show a message that the resource has been deleted?
					$contents .= translate(['text' => 'This resource no longer exists', 'isPublicFacing' => true]);
				}
			}
		} elseif ($this->sourceType == 'custom_web_resource_page') {
			require_once ROOT_DIR . '/sys/WebBuilder/CustomWebResourcePage.php';
			$customWebResourcePage = new CustomWebResourcePage();
			$customWebResourcePage->id = $this->sourceId;
			if ($customWebResourcePage->find(true)) {
				if (!$this->hideDescription) {
					$interface->assign('description', $customWebResourcePage->getTextBlockTranslation('customWebResourceDescription', $activeLanguage->code));
				}
				$interface->assign('title', $customWebResourcePage->title);
				$interface->assign('url', $customWebResourcePage->urlAlias);
				if (!empty( $this->customImage)){
					$interface->assign('logo', '/files/original/' . $this->customImage);
				}else{
					$interface->assign('logo', null);
				}
				$contents .= $interface->fetch('WebBuilder/resource.tpl');
			}
		} elseif ($this->sourceType == 'quick_poll') {
			require_once ROOT_DIR . '/sys/WebBuilder/QuickPoll.php';
			$quickPoll = new QuickPoll();
			$quickPoll->id = $this->sourceId;
			if($quickPoll->find(true)) {
				if ($inPageEditor) {
					require_once ROOT_DIR . '/sys/Parsedown/AspenParsedown.php';
					$parsedown = AspenParsedown::instance();
					$parsedown->setBreaksEnabled(true);
					$contents .= "<h2>$quickPoll->title</h2>";
					$contents .= $parsedown->parse($quickPoll->introText);
					$contents .= "<div>(fields not displayed while editing the page)</div>";
				} else {
					$oldId = $interface->getVariable('id');
					$interface->assign('id', $quickPoll->id);
					$contents .= $quickPoll->getFormattedPoll();
					$interface->assign('id', $oldId);
				}
			}
		} elseif ($this->sourceType == 'hours_locations') {
			global $library;
			$libraryLocations = [];

			// Check if using static location or dynamic selection.
			$locationsToProcess = [];
			$tmpLocation = new Location();
			if (!empty($this->staticLocationId) && $this->staticLocationId != -1) {
				// Get a single specific location.
				$tmpLocation->locationId = $this->staticLocationId;
				if ($tmpLocation->find(true)) {
					$locationsToProcess[] = clone $tmpLocation;
				}
			} else {
				// Get all locations as before (dynamic selection).
				$tmpLocation->libraryId = $library->libraryId;
				$tmpLocation->showInLocationsAndHoursList = 1;
				$tmpLocation->orderBy('isMainBranch DESC, displayName');
				$tmpLocation->find();
				if ($tmpLocation->getNumResults() == 0) {
					$tmpLocation = new Location();
					$tmpLocation->showInLocationsAndHoursList = 1;
					$tmpLocation->orderBy('displayName');
					$tmpLocation->find();
				}

				while ($tmpLocation->fetch()) {
					$locationsToProcess[] = clone $tmpLocation;
				}
			}

			require_once ROOT_DIR . '/sys/Enrichment/GoogleApiSetting.php';
			$googleSettings = new GoogleApiSetting();
			if ($googleSettings->find(true)) {
				$mapsKey = $googleSettings->googleMapsKey;
			} else {
				$mapsKey = null;
			}
			require_once ROOT_DIR . '/sys/Parsedown/AspenParsedown.php';
			$parsedown = AspenParsedown::instance();
			$parsedown->setBreaksEnabled(true);
			foreach ($locationsToProcess as $locationToProcess) {
				$mapAddress = urlencode(preg_replace('/\r\n|\r|\n/', '+', $locationToProcess->address));
				$hours = $locationToProcess->getHours();
				foreach ($hours as $key => $hourObj) {
					if (!$hourObj->closed) {
						$hourString = $hourObj->open;
						[
							$hour,
							$minutes,
						] = explode(':', $hourString);
						if ($hour < 12) {
							if ($hour == 0) {
								$hour += 12;
							}
							$hourObj->open = +$hour . ":$minutes AM"; // remove leading zeros in the hour
						} elseif ($hour == 12 && $minutes == '00') {
							$hourObj->open = 'Noon';
						} elseif ($hour == 24 && $minutes == '00') {
							$hourObj->open = 'Midnight';
						} else {
							if ($hour != 12) {
								$hour -= 12;
							}
							$hourObj->open = "$hour:$minutes PM";
						}
						$hourString = $hourObj->close;
						[
							$hour,
							$minutes,
						] = explode(':', $hourString);
						if ($hour < 12) {
							if ($hour == 0) {
								$hour += 12;
							}
							$hourObj->close = "$hour:$minutes AM";
						} elseif ($hour == 12 && $minutes == '00') {
							$hourObj->close = 'Noon';
						} elseif ($hour == 24 && $minutes == '00') {
							$hourObj->close = 'Midnight';
						} else {
							if ($hour != 12) {
								$hour -= 12;
							}
							$hourObj->close = "$hour:$minutes PM";
						}
					}
					$hours[$key] = $hourObj;
				}
				$libraryLocation = [
					'id' => $locationToProcess->locationId,
					'name' => $locationToProcess->displayName,
					'address' => preg_replace('/\r\n|\r|\n/', '<br>', $locationToProcess->address),
					'phone' => $locationToProcess->phone,
					'tty' => $locationToProcess->tty,
					'email' => $locationToProcess->contactEmail,
					//'map_image' => "http://maps.googleapis.com/maps/api/staticmap?center=$mapAddress&zoom=15&size=200x200&sensor=false&markers=color:red%7C$mapAddress",
					'hours' => $hours,
					'hasValidHours' => $locationToProcess->hasValidHours(),
					'description' => $parsedown->parse($locationToProcess->description),
					'image' => $locationToProcess->locationImage ? $configArray['Site']['url'] . '/files/original/' . $locationToProcess->locationImage : null,
					'longitude' => floatval($locationToProcess->longitude),
					'latitude' => floatval($locationToProcess->latitude),
					'homeLink' => (!empty($locationToProcess->homeLink) && ($locationToProcess->homeLink != 'default')) ? $locationToProcess->homeLink : (!is_null($locationToProcess->getParentLibrary()) ? $locationToProcess->getParentLibrary()->homeLink : null),
					'hoursMessage' => Location::getLibraryHoursMessage($locationToProcess->locationId, true),
				];

				if (!empty($mapsKey)) {
					$libraryLocation['map_link'] = "http://maps.google.com/maps?f=q&hl=en&geocode=&q=$mapAddress&ie=UTF8&z=15&iwloc=addr&om=1&t=m&key=$mapsKey";
				}
				$libraryLocations[$locationToProcess->locationId] = $libraryLocation;
			}

			global $interface;
			$interface->assign('libraryLocations', $libraryLocations);
			if (!empty($this->staticLocationId) && $this->staticLocationId != -1) {
				$interface->assign('useStaticLocation', true);
			}
			$contents .= $interface->fetch('WebBuilder/libraryHoursAndLocations.tpl');
		}
		if ($this->makeCellAccordion == '1') {
			$contents .= "</div></div></div>";
		}
		if (empty($contents)) {
			return 'Could not load contents for the cell';
		} else {
			return $contents;
		}
	}

	function getPortalRow() {
		$portalRow = new PortalRow();
		$portalRow->id = $this->portalRowId;
		if ($portalRow->find(true)) {
			return $portalRow;
		} else {
			return null;
		}
	}

	/** @noinspection PhpUnused */
	public function isLastCell() {
		$myRow = new PortalRow();
		$myRow->id = $this->portalRowId;
		if ($myRow->find(true)) {
			return count($myRow->getCells()) - 1 == $this->weight;
		}
		return false;
	}

	public function delete($useWhere = false) : int {
		$ret = parent::delete($useWhere);
		if ($ret) {
			//Reorder the rows on the page to remove the gap
			$portalRow = new PortalRow();
			$portalRow->id = $this->portalRowId;
			if ($portalRow->find(true)) {
				$cells = $portalRow->getCells();
				$cellIndex = 0;
				foreach ($cells as $cell) {
					$cell->weight = $cellIndex++;
					$cell->update();
				}
			}
		}
		return $ret;
	}
}