<?php

use PHPUnit\Framework\TestCase;

require_once ROOT_DIR . '/sys/Theming/Theme.php';

class ThemeTests extends TestCase {
	private function createThemeWithLogo(?string $logoName): Theme {
		$theme = new Theme();
		$theme->logoName = $logoName;
		return $theme;
	}

	public function testGetFirstThemeValueReturnsFirstNonEmptyValue(): void {
		$themes = [
			$this->createThemeWithLogo('child_logo.png'),
			$this->createThemeWithLogo('parent_logo.png'),
		];

		$this->assertSame('child_logo.png', Theme::getFirstThemeValue($themes, 'logoName'));
	}

	public function testGetFirstThemeValueSkipsEmptyValues(): void {
		$themes = [
			$this->createThemeWithLogo(null),
			$this->createThemeWithLogo(''),
			$this->createThemeWithLogo('parent_logo.png'),
		];

		$this->assertSame('parent_logo.png', Theme::getFirstThemeValue($themes, 'logoName'));
	}

	public function testGetFirstThemeValueReturnsNullWhenNoThemeHasValue(): void {
		$themes = [
			$this->createThemeWithLogo(null),
			$this->createThemeWithLogo(''),
		];

		$this->assertNull(Theme::getFirstThemeValue($themes, 'logoName'));
	}
}
