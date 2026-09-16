<?php
namespace RecordDrivers;

use OmekaRecordDriver;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class OmekaRecordDriverTests extends TestCase {
	public function __construct(string $name) {
		parent::__construct($name);
		require_once __DIR__ . '/../../../../code/web/RecordDrivers/OmekaRecordDriver.php';
	}

	public static function mediaTypeProvider(): array {
		return [
			[null, 'Web Content'],
			['', 'Web Content'],
			['image/jpeg', 'Photo'],
			['image/tiff', 'Photo'],
			['application/pdf', 'PDF'],
			['audio/mpeg', 'eAudio'],
			['video/mp4', 'eVideo'],
			['text/html', 'Web Content'],
			['application/zip', 'Web Content'],
		];
	}

	#[DataProvider('mediaTypeProvider')]
	public function testGetFormatForMediaType(?string $mediaType, string $expectedFormat): void {
		$this->assertEquals($expectedFormat, OmekaRecordDriver::getFormatForMediaType($mediaType));
	}

	public static function formatCategoryProvider(): array {
		return [
			['PDF', 'eBook'],
			['eAudio', 'Audio Books'],
			['eVideo', 'Movies'],
			['Photo', 'Other'],
			['Web Content', 'Other'],
		];
	}

	#[DataProvider('formatCategoryProvider')]
	public function testGetFormatCategoryForFormat(string $format, string $expectedCategory): void {
		$this->assertEquals($expectedCategory, OmekaRecordDriver::getFormatCategoryForFormat($format));
	}

	public static function languageValueProvider(): array {
		return [
			['eng', 'eng'],
			['en', 'eng'],
			['fr', 'fre'],
			['English', 'eng'],
			['Welsh', 'wel'],
			['Klingon', 'tlh'],
			['Not A Language', 'unk'],
		];
	}

	#[DataProvider('languageValueProvider')]
	public function testGetThreeLetterLanguageCode(string $languageValue, string $expectedCode): void {
		$this->assertEquals($expectedCode, OmekaRecordDriver::getThreeLetterLanguageCode($languageValue));
	}
}
