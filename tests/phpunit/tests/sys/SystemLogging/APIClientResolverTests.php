<?php

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../../../../code/web/sys/SystemLogging/APIClientResolver.php';

class APIClientResolverTests extends TestCase {
	public static function userAgentProvider(): array {
		return [
			'lida android' => ['Aspen LiDA/1.2.3 (Android)', true],
			'lida embedded in a longer agent' => ['Mozilla/5.0 Aspen LiDA', true],
			'lowercase does not match' => ['aspen lida', false],
			'unrelated agent' => ['Mozilla/5.0 (Macintosh)', false],
			'empty agent' => ['', false],
			'missing agent' => [null, false],
		];
	}

	#[DataProvider('userAgentProvider')]
	public function testIsLiDAUserAgent(?string $userAgent, bool $expected): void {
		$this->assertSame($expected, APIClientResolver::isLiDAUserAgent($userAgent));
	}

	public function testClientNameWithoutOAuthOrLiDA(): void {
		$this->assertSame('Other', APIClientResolver::getClientName());
	}
}
