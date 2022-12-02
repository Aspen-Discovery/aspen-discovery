<?php

class GraphingUtils {
	public static function getDataSeriesArray($index): array {
		switch ($index) {
			case 0:
				return [
					'borderColor' => 'rgba(255, 99, 132, 1)',
					'backgroundColor' => 'rgba(255, 99, 132, 0.2)',
					'data' => [],
				];
			case 1:
				return [
					'borderColor' => 'rgba(54, 162, 235, 1)',
					'backgroundColor' => 'rgba(54, 162, 235, 0.2)',
					'data' => [],
				];
			case 2:
				return [
					'borderColor' => 'rgba(255, 159, 64, 1)',
					'backgroundColor' => 'rgba(255, 159, 64, 0.2)',
					'data' => [],
				];
			case 3:
				return [
					'borderColor' => 'rgba(0, 255, 55, 1)',
					'backgroundColor' => 'rgba(0, 255, 55, 0.2)',
					'data' => [],
				];
			case 4:
				return [
					'borderColor' => 'rgba(154, 75, 244, 1)',
					'backgroundColor' => 'rgba(154, 75, 244, 0.2)',
					'data' => [],
				];
			case 5:
				return [
					'borderColor' => 'rgba(255, 206, 86, 1)',
					'backgroundColor' => 'rgba(255, 206, 86, 0.2)',
					'data' => [],
				];
			case 6:
				return [
					'borderColor' => 'rgba(75, 192, 192, 1)',
					'backgroundColor' => 'rgba(75, 192, 192, 0.2)',
					'data' => [],
				];
			case 7:
				return [
					'borderColor' => 'rgba(153, 102, 255, 1)',
					'backgroundColor' => 'rgba(153, 102, 255, 0.2)',
					'data' => [],
				];
			case 8:
				return [
					'borderColor' => 'rgba(165, 42, 42, 1)',
					'backgroundColor' => 'rgba(165, 42, 42, 0.2)',
					'data' => [],
				];
			case 9:
				return [
					'borderColor' => 'rgba(50, 205, 50, 1)',
					'backgroundColor' => 'rgba(50, 205, 50, 0.2)',
					'data' => [],
				];
			case 10:
				return [
					'borderColor' => 'rgba(220, 60, 20, 1)',
					'backgroundColor' => 'rgba(220, 60, 20, 0.2)',
					'data' => [],
				];
			case 11:
				return [
					'borderColor' => 'rgba(255, 165, 0, 1)',
					'backgroundColor' => 'rgba(255, 165, 0, 0.2)',
					'data' => [],
				];
		}
		$randomR = rand(0, 255);
		$randomB = rand(0, 255);
		$randomG = rand(0, 255);
		return [
			'borderColor' => "rgba($randomR, $randomB, $randomG, 1)",
			'backgroundColor' => "rgba($randomR, $randomB, $randomG, 0.2)",
			'data' => [],
		];
	}
}