<?php
namespace sys\Grouping;

use GroupedWorkDisplaySetting;
use PHPUnit\Framework\TestCase;
use UInterface;

class GroupedWorkDisplaySettingTests extends TestCase {
	public function __construct(string $name) {
		parent::__construct($name);
		require_once __DIR__ . '/../../../../../code/web/sys/Grouping/GroupedWorkDisplaySetting.php';
	}

	public function testAssignSearchResultsMainDetailsAssignsSelectedOptions(): void {
		$interface = new UInterface();
		$settings = new GroupedWorkDisplaySetting();
		$settings->showInSearchResultsMainDetails = [
			'showPublisher' => 'showPublisher',
			'showPublicationDate' => 'showPublicationDate',
		];

		$settings->assignSearchResultsMainDetails($interface);

		$this->assertTrue($interface->getVariable('showPublisher'));
		$this->assertTrue($interface->getVariable('showPublicationDate'));
		$this->assertNull($interface->getVariable('showEditions'));
	}

	public function testAssignSearchResultsMainDetailsAssignsNothingWithoutSelection(): void {
		$interface = new UInterface();
		$settings = new GroupedWorkDisplaySetting();
		$settings->showInSearchResultsMainDetails = [];

		$settings->assignSearchResultsMainDetails($interface);

		$this->assertNull($interface->getVariable('showPublisher'));
		$this->assertNull($interface->getVariable('showPublicationDate'));
	}
}
