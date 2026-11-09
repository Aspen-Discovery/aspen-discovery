<?php
namespace sys;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class MockDataObject extends \DataObject {
	public $__table = 'mock_data_object';
	public $id;
	public $fileContents;

	//Don't write DataObjectHistory rows to the database while testing
	function objectHistoryEnabled(): bool {
		return false;
	}
}

class DataObjectUtilTests extends TestCase {
    private array $property = [
		'property' => 'fileContents',
		'type' => 'file',
		'readContents' => true,
		'validExtensions' => ['json'],
	];
	private array $tempFiles = [];
	private $originalErrorHandling;

	protected function setUp(): void {
		$_REQUEST = [];
		$_FILES = [];
		global $errorHandlingEnabled;
		$this->originalErrorHandling = $errorHandlingEnabled ?? null;
		//AspenError::raiseError() normally renders an error page and exits, which would stop PHPUnit
		$errorHandlingEnabled = -1;
	}

	protected function tearDown(): void {
		$_REQUEST = [];
		$_FILES = [];
		global $errorHandlingEnabled;
		$errorHandlingEnabled = $this->originalErrorHandling;
		foreach ($this->tempFiles as $tempFile) {
			@unlink($tempFile);
		}
	}

    private function createObject(): MockDataObject {
		$object = new MockDataObject();
		$object->fileContents = 'existing';
		return $object;
	}

    private function uploadFile(string $name, string $contents, int $error = UPLOAD_ERR_OK): void {
		$tmpName = tempnam(sys_get_temp_dir(), 'aspen_test_');
		file_put_contents($tmpName, $contents);
		$this->tempFiles[] = $tmpName;
		$_FILES['fileContents'] = [
			'name' => $name,
			'type' => 'application/json',
			'tmp_name' => $tmpName,
			'error' => $error,
			'size' => strlen($contents),
		];
	}

    private function process(MockDataObject $object): void {
		\DataObjectUtil::processProperty($object, $this->property, null);
	}

    public function testRemoveCheckboxClearsValue(): void {
		$object = $this->createObject();
		$_REQUEST['removefileContents'] = 'on';
		$this->uploadFile('account.json', '{"new":true}');
		$this->process($object);
		$this->assertSame('', $object->fileContents);
	}

    public function testMissingFileKeepsExistingValue(): void {
		$object = $this->createObject();
		$this->process($object);
		$this->assertSame('existing', $object->fileContents);
	}

    public function testNoFileChosenKeepsExistingValue(): void {
		$object = $this->createObject();
		$this->uploadFile('', '', UPLOAD_ERR_NO_FILE);
		$this->process($object);
		$this->assertSame('existing', $object->fileContents);
	}

	public function testUploadErrorKeepsExistingValue(): void {
		$object = $this->createObject();
		$this->uploadFile('account.json', '{"new":true}', UPLOAD_ERR_INI_SIZE);
		$this->process($object);
		$this->assertSame('existing', $object->fileContents);
	}

	public function testInvalidExtensionKeepsExistingValue(): void {
		$object = $this->createObject();
		$this->uploadFile('account.txt', '{"new":true}');
		$this->process($object);
		$this->assertSame('existing', $object->fileContents);
	}

	public function testUnreadableFileKeepsExistingValue(): void {
		$object = $this->createObject();
		$_FILES['fileContents'] = [
			'name' => 'account.json',
			'type' => 'application/json',
			'tmp_name' => sys_get_temp_dir() . '/aspen_test_does_not_exist',
			'error' => UPLOAD_ERR_OK,
			'size' => 0,
		];
		$this->process($object);
		$this->assertSame('existing', $object->fileContents);
	}

	public function testValidFileStoresTrimmedContents(): void {
		$object = $this->createObject();
		$this->uploadFile('Account.JSON', "  {\"type\":\"service_account\"}\n");
		$this->process($object);
		$this->assertSame('{"type":"service_account"}', $object->fileContents);
	}
}