<?php

class ColorUtils
{
	static function colorRgbToHsl( $r, $g, $b ) {
		$r /= 255;
		$g /= 255;
		$b /= 255;
		$max = max( $r, $g, $b );
		$min = min( $r, $g, $b );
		$h = 0;
		$s = 0;
		$l = ( $max + $min ) / 2;
		$d = $max - $min;
		if( $d == 0 ){
			$h = $s = 0; // achromatic
		} else {
			$s = $d / ( 1 - abs( 2 * $l - 1 ) );
			switch( $max ){
				case $r:
					$h = 60 * fmod( ( ( $g - $b ) / $d ), 6 );
					if ($b > $g) {
						$h += 360;
					}
					break;
				case $g:
					$h = 60 * ( ( $b - $r ) / $d + 2 );
					break;
				case $b:
					$h = 60 * ( ( $r - $g ) / $d + 4 );
					break;
			}
		}
		return array( round( $h, 2 ), round( $s, 2 ), round( $l, 2 ) );
	}

	static function colorHSLToRGB($h, $s, $l){
		if ($h < 0) $h = 0;
		if ($h > 360) $h = 360;
		if ($s < 0) $s = 0;
		if ($s > 100) $s = 100;
		if ($l < 0) $l = 0;
		if ($l > 360) $l = 360;

		if ($h > 1) $h /= 360;
		if ($s > 1) $s /= 100;
		if ($l > 1) $l /= 100;
		$r = $l;
		$g = $l;
		$b = $l;
		$v = ($l <= 0.5) ? ($l * (1.0 + $s)) : ($l + $s - $l * $s);
		if ($v > 0){
			$m = $l + $l - $v;
			$sv = ($v - $m ) / $v;
			$h *= 6.0;
			$sextant = floor($h);
			$fract = $h - $sextant;
			$vsf = $v * $sv * $fract;
			$mid1 = $m + $vsf;
			$mid2 = $v - $vsf;

			switch ($sextant)
			{
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
		return array('r' => round($r * 255.0), 'g' => round($g * 255.0), 'b' => round($b * 255.0));
	}


	/**
	 * @param string $color - The original color in #rrggbb format
	 * @param float  $percentLightening - The amount to lighten > 1 will lighten, < 1 will darken
	 *
	 * @return string
	 */
	public static function lightenColor($color, float $percentLightening)
	{
		$r = hexdec(substr($color, 1, 2));
		$g = hexdec(substr($color, 3, 2));
		$b = hexdec(substr($color, 5, 2));
		$hsl = ColorUtils::colorRgbToHsl($r, $g, $b);
		$rgb = ColorUtils::colorHSLToRGB($hsl[0], $hsl[1], $hsl[2] * $percentLightening);
		return "#" . dechex($rgb['r']) . dechex($rgb['g']) . dechex($rgb['b']);
	}
}