<?php

class ColorUtils {
	static function colorRgbToHsl($r, $g, $b) {
		$r /= 255;
		$g /= 255;
		$b /= 255;
		$max = max($r, $g, $b);
		$min = min($r, $g, $b);
		$h = 0;
		$s = 0;
		$l = ($max + $min) / 2;
		$d = $max - $min;
		if ($d == 0) {
			$h = $s = 0; // achromatic
		} else {
			$s = $d / (1 - abs(2 * $l - 1));
			switch ($max) {
				case $r:
					$h = 60 * fmod((($g - $b) / $d), 6);
					if ($b > $g) {
						$h += 360;
					}
					break;
				case $g:
					$h = 60 * (($b - $r) / $d + 2);
					break;
				case $b:
					$h = 60 * (($r - $g) / $d + 4);
					break;
			}
		}
		return [
			round($h, 2),
			round($s, 2),
			round($l, 2),
		];
	}

	static function colorHSLToRGB($h, $s, $l) {
		if ($h < 0) {
			$h = 0;
		}
		if ($h > 360) {
			$h = 360;
		}
		if ($s < 0) {
			$s = 0;
		}
		if ($s > 100) {
			$s = 100;
		}
		if ($l < 0) {
			$l = 0;
		}
		if ($l > 360) {
			$l = 360;
		}

		if ($h > 1) {
			$h /= 360;
		}
		if ($s > 1) {
			$s /= 100;
		}
		if ($l > 1) {
			$l /= 100;
		}
		$r = $l;
		$g = $l;
		$b = $l;
		$v = ($l <= 0.5) ? ($l * (1.0 + $s)) : ($l + $s - $l * $s);
		if ($v > 0) {
			$m = $l + $l - $v;
			$sv = ($v - $m) / $v;
			$h *= 6.0;
			$sextant = floor($h);
			$fract = $h - $sextant;
			$vsf = $v * $sv * $fract;
			$mid1 = $m + $vsf;
			$mid2 = $v - $vsf;

			switch ($sextant) {
				case 0:
					$r = $v;
					$g = $mid1;
					$b = $m;
					break;
				case 1:
					$r = $mid2;
					$g = $v;
					$b = $m;
					break;
				case 2:
					$r = $m;
					$g = $v;
					$b = $mid1;
					break;
				case 3:
					$r = $m;
					$g = $mid2;
					$b = $v;
					break;
				case 4:
					$r = $mid1;
					$g = $m;
					$b = $v;
					break;
				case 5:
					$r = $v;
					$g = $m;
					$b = $mid2;
					break;
			}
		}
		return [
			'r' => round($r * 255.0),
			'g' => round($g * 255.0),
			'b' => round($b * 255.0),
		];
	}


	/**
	 * @param string $color - The original color in #rrggbb format
	 * @param float $percentLightening - The amount to lighten > 1 will lighten, < 1 will darken
	 *
	 * @return string
	 */
	public static function lightenColor($color, float $percentLightening) {
		$r = hexdec(substr($color, 1, 2));
		$g = hexdec(substr($color, 3, 2));
		$b = hexdec(substr($color, 5, 2));
		$hsl = ColorUtils::colorRgbToHsl($r, $g, $b);
		$rgb = ColorUtils::colorHSLToRGB($hsl[0], $hsl[1], $hsl[2] * $percentLightening);
		return "#" . str_pad(dechex($rgb['r']), 2, '0', STR_PAD_LEFT) . str_pad(dechex($rgb['g']), 2, '0', STR_PAD_LEFT) . str_pad(dechex($rgb['b']), 2, '0', STR_PAD_LEFT);
	}

	/**
	 * Calculates the color contrast between two colors specified in RGB format i.e. #FFFFFF
	 * The resulting values should be between 1 and 21.
	 *
	 * @param $color1
	 * @param $color2
	 *
	 * @return float
	 */
	public static function calculateColorContrast($color1, $color2) {
		$luminance1 = ColorUtils::getLuminanceForColor($color1);
		$luminance2 = ColorUtils::getLuminanceForColor($color2);

		if ($luminance1 > $luminance2) {
			$contrastRatio = (($luminance1 + 0.05) / ($luminance2 + 0.05));
		} else {
			$contrastRatio = (($luminance2 + 0.05) / ($luminance1 + 0.05));
		}
		return round($contrastRatio, 2);
	}

	/**
	 * Calculates the relative luminance for a color which is a number from 0 for black to 1 for white
	 * @param $color
	 * @return float
	 */
	public static function getLuminanceForColor($color) {
		$r = self::getLuminanceComponent($color, 1, 2);
		$g = self::getLuminanceComponent($color, 3, 2);
		$b = self::getLuminanceComponent($color, 5, 2);
		return (0.2126 * $r) + (0.7152 * $g) + (0.0722 * $b);
	}

	/**
	 * @param $color
	 * @return float
	 */
	private static function getLuminanceComponent($color, $start, $length) {
		$component = (float)hexdec(substr($color, $start, $length)) / (float)255;
		if ($component <= 0.03928) {
			$luminanceVal = $component / 12.92;
		} else {
			$luminanceVal = pow(($component + 0.055) / 1.055, 2.4);
		}
		return $luminanceVal;
	}

}