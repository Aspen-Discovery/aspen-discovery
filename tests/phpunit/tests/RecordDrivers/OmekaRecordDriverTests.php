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

	public static function publicationDetailProvider(): array {
		return [
			['Colored Conventions Project', '1968', 'Colored Conventions Project, 1968'],
			[null, '1968', '1968'],
			['Colored Conventions Project', null, 'Colored Conventions Project'],
			['Smith & Sons', '1968', 'Smith &amp; Sons, 1968'],
			[null, null, ''],
		];
	}

	#[DataProvider('publicationDetailProvider')]
	public function testFormatPublicationDetail(?string $publisher, ?string $date, string $expected): void {
		$this->assertEquals($expected, OmekaRecordDriver::formatPublicationDetail($publisher, $date));
	}

	public function testDescribeClassicMediaFile(): void {
		$media = json_decode('{"id": 6957, "order": 1, "mime_type": "image/jpeg", "file_urls": {"original": "https://example.org/files/original/a.jpg", "fullsize": "https://example.org/files/fullsize/a.jpg", "square_thumbnail": "https://example.org/files/square_thumbnails/a.jpg"}, "element_texts": [{"element_set": {"name": "Dublin Core"}, "element": {"name": "Title"}, "text": "Zaenger family portrait"}, {"element_set": {"name": "Dublin Core"}, "element": {"name": "Description"}, "text": "Studio photograph"}]}');
		$mediaFile = OmekaRecordDriver::describeMediaFile($media);
		$this->assertEquals('https://example.org/files/original/a.jpg', $mediaFile['url']);
		$this->assertEquals('https://example.org/files/square_thumbnails/a.jpg', $mediaFile['thumbnailUrl']);
		$this->assertEquals('Zaenger family portrait', $mediaFile['title']);
		$this->assertEquals('Studio photograph', $mediaFile['description']);
		$this->assertEquals('Photo', $mediaFile['format']);
	}

	public function testDescribeOmekaSMediaFile(): void {
		$media = json_decode('{"o:id": 12, "o:media_type": "application/pdf", "o:original_url": "https://example.org/files/original/b.pdf", "o:thumbnail_urls": {"medium": "https://example.org/files/medium/b.jpg", "square": "https://example.org/files/square/b.jpg"}, "dcterms:title": [{"@value": "Minutes, page 2"}]}');
		$mediaFile = OmekaRecordDriver::describeMediaFile($media);
		$this->assertEquals('https://example.org/files/original/b.pdf', $mediaFile['url']);
		$this->assertEquals('https://example.org/files/square/b.jpg', $mediaFile['thumbnailUrl']);
		$this->assertEquals('Minutes, page 2', $mediaFile['title']);
		$this->assertEquals('', $mediaFile['description']);
		$this->assertEquals('PDF', $mediaFile['format']);
	}

	public function testDescribeMediaFileWithoutUrls(): void {
		$media = json_decode('{"id": 1, "mime_type": "image/jpeg", "element_texts": []}');
		$this->assertNull(OmekaRecordDriver::describeMediaFile($media));
	}
}
