<?php

require_once ROOT_DIR . '/RecordDrivers/RecordInterface.php';
require_once ROOT_DIR . '/RecordDrivers/GroupedWorkSubDriver.php';
require_once ROOT_DIR . '/sys/Omeka/OmekaTitle.php';
require_once ROOT_DIR . '/sys/Omeka/OmekaSetting.php';

class OmekaRecordDriver extends GroupedWorkSubDriver {
	protected ?string $id = null;
	private ?OmekaTitle $omekaTitle;
	private ?stdClass $omekaRawMetadata = null;
	private ?OmekaSetting $omekaSetting = null;
	private bool $valid;

	public function __construct($recordId, $groupedWork = null) {
		$this->id = $recordId;

		$this->omekaTitle = new OmekaTitle();
		$this->omekaTitle->id = $recordId;
		$this->valid = is_numeric($recordId) && $this->omekaTitle->find(true);
		if (!$this->valid) {
			$this->omekaTitle = null;
			return;
		}
		$this->omekaRawMetadata = json_decode($this->omekaTitle->rawResponse);
		parent::__construct($groupedWork);
	}

	public function getIdWithSource(): string {
		return 'omeka:' . $this->id;
	}

	public function loadGroupedWork(): void {
		if ($this->groupedWork == null) {
			require_once ROOT_DIR . '/sys/Grouping/GroupedWorkPrimaryIdentifier.php';
			require_once ROOT_DIR . '/sys/Grouping/GroupedWork.php';
			$groupedWork = new GroupedWork();
			$query = "SELECT grouped_work.* FROM grouped_work INNER JOIN grouped_work_primary_identifiers ON grouped_work.id = grouped_work_id WHERE type='omeka' AND identifier = '" . $this->getUniqueID() . "'";
			$groupedWork->query($query);

			if ($groupedWork->getNumResults() == 1) {
				$groupedWork->fetch();
				$this->groupedWork = clone $groupedWork;
			}
		}
	}

	public function getModule(): string {
		return 'Omeka';
	}

	private function getSetting(): ?OmekaSetting {
		if ($this->omekaSetting == null) {
			$setting = new OmekaSetting();
			$setting->id = $this->omekaTitle->settingId;
			if ($setting->find(true)) {
				$this->omekaSetting = $setting;
			}
		}
		return $this->omekaSetting;
	}

	public function getPublicUrl(): ?string {
		$setting = $this->getSetting();
		if ($setting == null) {
			return null;
		}
		return rtrim($setting->baseUrl, '/') . '/s/' . $setting->siteSlug . '/item/' . $this->omekaTitle->omekaId;
	}

	private function getFirstLiteralValue(string $property): ?string {
		$values = $this->omekaRawMetadata->{$property} ?? null;
		if (empty($values) || !is_array($values)) {
			return null;
		}
		foreach ($values as $value) {
			if (!empty($value->{'@value'})) {
				return $value->{'@value'};
			}
		}
		return null;
	}

	private function getAllLiteralValues(string $property): array {
		$literalValues = [];
		$values = $this->omekaRawMetadata->{$property} ?? null;
		if (empty($values) || !is_array($values)) {
			return $literalValues;
		}
		foreach ($values as $value) {
			if (!empty($value->{'@value'})) {
				$literalValues[] = $value->{'@value'};
			}
		}
		return $literalValues;
	}

	public function getStaffView(): string {
		global $interface;
		$groupedWorkDriver = $this->getGroupedWorkDriver();
		$hasValidGroupedWork = $groupedWorkDriver != null && $groupedWorkDriver->isValid();
		$interface->assign('hasValidGroupedWork', $hasValidGroupedWork);
		if ($hasValidGroupedWork) {
			$groupedWorkDriver->assignGroupedWorkStaffView();

			require_once ROOT_DIR . '/sys/Grouping/NonGroupedRecord.php';
			$nonGroupedRecord = new NonGroupedRecord();
			$nonGroupedRecord->source = $this->getRecordType();
			$nonGroupedRecord->recordId = $this->id;
			if ($nonGroupedRecord->find(true)) {
				$interface->assign('isUngrouped', true);
				$interface->assign('ungroupingId', $nonGroupedRecord->id);
			} else {
				$interface->assign('isUngrouped', false);
			}
		}

		$interface->assign('bookcoverInfo', $this->getBookcoverInfo());
		$interface->assign('omekaExtract', $this->omekaRawMetadata);

		return 'RecordDrivers/Omeka/staff-view.tpl';
	}

	public function getTitle(): string {
		return $this->omekaTitle->title;
	}

	public function getShortTitle(): string {
		return $this->omekaTitle->title;
	}

	public function getSubtitle(): string {
		return '';
	}

	public function getAuthor(): string {
		return $this->getFirstLiteralValue('dcterms:creator') ?? '';
	}

	public function getPrimaryAuthor(): string {
		return $this->getAuthor();
	}

	function getContributors(): array {
		return array_merge($this->getAllLiteralValues('dcterms:creator'), $this->getAllLiteralValues('dcterms:contributor'));
	}

	public function getDescription() {
		return $this->getFirstLiteralValue('dcterms:description') ?? '';
	}

	public function getTableOfContents(): array {
		return [];
	}

	public function getUniqueID(): string {
		return $this->id;
	}

	public function getMoreDetailsOptions(): array {
		global $interface;

		$moreDetailsOptions = $this->getBaseMoreDetailsOptions(false);

		$groupedWorkDriver = $this->getGroupedWorkDriver();
		if ($groupedWorkDriver != null) {
			$relatedRecords = $groupedWorkDriver->getRelatedRecords();
			if (count($relatedRecords) > 1) {
				$interface->assign('relatedManifestations', $groupedWorkDriver->getRelatedManifestations());
				$interface->assign('workId', $groupedWorkDriver->getPermanentId());
				$moreDetailsOptions['otherEditions'] = [
					'label' => 'Other Editions and Formats',
					'body' => $interface->fetch('GroupedWork/relatedManifestations.tpl'),
					'hideByDefault' => false,
				];
			}
		}

		$moreDetailsOptions['moreDetails'] = [
			'label' => 'More Details',
			'body' => $interface->fetch('Omeka/view-more-details.tpl'),
		];
		$this->loadSubjects();
		$moreDetailsOptions['subjects'] = [
			'label' => 'Subjects',
			'body' => $interface->fetch('RecordDrivers/Omeka/view-subjects.tpl'),
		];
		$moreDetailsOptions['citations'] = [
			'label' => 'Citations',
			'body' => $interface->fetch('Record/cite.tpl'),
		];

		if ($interface->getVariable('showStaffView')) {
			$moreDetailsOptions['staff'] = [
				'label' => 'Staff View',
				'onShow' => "AspenDiscovery.Omeka.getStaffView('$this->id');",
				'body' => '<div id="staffViewPlaceHolder">Loading Staff View.</div>',
			];
		}

		return $this->filterAndSortMoreDetailsOptions($moreDetailsOptions);
	}

	public function getISBNs(): array {
		return [];
	}

	public function getOCLCNumber(): string {
		return '';
	}

	public function getISSNs(): array {
		return [];
	}

	protected ?array $_actions = null;

	public function getRecordActions($relatedRecord, $variationId, $isAvailable, $isHoldable, $volumeData = null): array {
		if ($this->_actions === null) {
			$this->_actions = [];
			$publicUrl = $this->getPublicUrl();
			if ($publicUrl != null) {
				$setting = $this->getSetting();
				$this->_actions[] = [
					'title' => translate([
						'text' => 'View Online',
						'isPublicFacing' => true,
					]),
					'url' => $publicUrl,
					'requireLogin' => false,
					'type' => 'access_online',
					'alt' => 'Available online from ' . $setting->name,
					'target' => '_blank',
				];
			}
		}
		return $this->_actions;
	}

	function getEditions(): array {
		return [];
	}

	private function getFormatFromMediaType(): string {
		return self::getFormatForMediaType($this->omekaTitle->mediaType);
	}

	public static function getFormatForMediaType(?string $mediaType): string {
		if (empty($mediaType)) {
			return 'Web Content';
		}
		if (str_starts_with($mediaType, 'image/')) {
			return 'Photo';
		}
		if ($mediaType == 'application/pdf') {
			return 'PDF';
		}
		if (str_starts_with($mediaType, 'audio/')) {
			return 'eAudio';
		}
		if (str_starts_with($mediaType, 'video/')) {
			return 'eVideo';
		}
		return 'Web Content';
	}

	function getFormats(): array {
		return [$this->getFormatFromMediaType()];
	}

	function getFormatCategory(): string|array|null {
		return [self::getFormatCategoryForFormat($this->getFormatFromMediaType())];
	}

	public static function getFormatCategoryForFormat(string $format): string {
		return match ($format) {
			'PDF' => 'eBook',
			'eAudio' => 'Audio Books',
			'eVideo' => 'Movies',
			default => 'Other',
		};
	}

	public function getLanguage() {
		$languageValue = $this->getFirstLiteralValue('dcterms:language');
		if ($languageValue == null) {
			return 'English';
		}
		$languageCode = self::getThreeLetterLanguageCode($languageValue);
		$translatedValue = mapValue('language', $languageCode);
		if (!empty($translatedValue)) {
			return $translatedValue;
		}
		return $languageValue;
	}

	public static function getThreeLetterLanguageCode(string $languageValue): string {
		if (strlen($languageValue) == 3) {
			return $languageValue;
		}
		$isTwoLetterCode = strlen($languageValue) == 2;
		$mapName = $isTwoLetterCode ? 'two_to_three_character_language_codes' : 'language_to_three_letter_code';
		$threeLetterCode = mapValue($mapName, $languageValue);
		if (empty($threeLetterCode)) {
			return $languageValue;
		}
		return $threeLetterCode;
	}

	public function getNumHolds(): int {
		return 0;
	}

	function getPlacesOfPublication(): array {
		return [];
	}

	function getPublishers(): array {
		return $this->getAllLiteralValues('dcterms:publisher');
	}

	function getPublicationDates(): array {
		$publicationDates = [];
		$dateValue = $this->getFirstLiteralValue('dcterms:date');
		if ($dateValue != null && preg_match('/(\d{4})/', $dateValue, $matches)) {
			$publicationDates[] = $matches[1];
		}
		return $publicationDates;
	}

	public function getRecordType(): string {
		return 'omeka';
	}

	function getRelatedRecord(): ?Grouping_Record {
		$id = 'omeka:' . $this->id;
		return $this->getGroupedWorkDriver()->getRelatedRecord($id);
	}

	public function getSemanticData(): ?array {
		$relatedRecord = $this->getRelatedRecord();
		if ($relatedRecord == null) {
			return null;
		}
		require_once ROOT_DIR . '/RecordDrivers/LDRecordOffer.php';
		$linkedDataRecord = new LDRecordOffer($relatedRecord);
		$semanticData [] = [
			'@context' => 'http://schema.org',
			'@type' => $linkedDataRecord->getWorkType(),
			'name' => $this->getTitle(),
			'creator' => $this->getPrimaryAuthor(),
			'isAccessibleForFree' => true,
			'image' => $this->getBookcoverUrl('medium'),
			"offers" => $linkedDataRecord->getOffers(),
		];

		global $interface;
		$interface->assign('og_title', $this->getTitle());
		$interface->assign('og_description', $this->getDescription());
		$interface->assign('og_type', $this->getGroupedWorkDriver()->getOGType());
		$interface->assign('og_image', $this->getBookcoverUrl('medium'));
		$interface->assign('og_url', $this->getAbsoluteUrl());
		return $semanticData;
	}

	function isValid(): bool {
		return $this->valid;
	}

	function loadSubjects(): void {
		global $interface;
		$interface->assign('subjects', $this->getAllLiteralValues('dcterms:subject'));
	}

	function getStatusSummary(): array {
		$statusSummary = [];
		$relatedRecord = $this->getRelatedRecord();
		if ($relatedRecord == null) {
			$statusSummary['status'] = 'Unavailable';
			$statusSummary['available'] = false;
			$statusSummary['class'] = 'unavailable';
		} else {
			$statusSummary['status'] = 'Available Online';
			$statusSummary['available'] = true;
			$statusSummary['class'] = 'available';
		}
		return $statusSummary;
	}

	function getOmekaBookcoverUrl(): ?string {
		if (!empty($this->omekaTitle->thumbnailUrl)) {
			return $this->omekaTitle->thumbnailUrl;
		}
		return null;
	}
}
