<?php

class StorageManager {
	private static $instance = null;
	private $baseDataPath;
	
	// Main categories
	const CATEGORY_IMAGES = 'images';
	const CATEGORY_FILES = 'files';
	
	// Standard sizes
	const SIZE_ORIGINAL = 'original';
	const SIZE_FULL = 'full';  // For web_builder backward compatibility
	const SIZE_X_LARGE = 'x-large';
	const SIZE_LARGE = 'large';
	const SIZE_MEDIUM = 'medium';
	const SIZE_SMALL = 'small';
	const SIZE_THUMBNAIL = 'thumbnail';
	
	// Image subcategories
	const COVERS = 'covers';
	const THEMES = 'themes';
	const WEB_BUILDER = 'web_builder';
	const WEB_RESOURCES = 'web_resources';
	const PLACARDS = 'placards';
	const EVENTS = 'events';
	const REWARDS = 'rewards';
	const SERIES = 'series';
	const CALENDARS = 'calendars';
	const GENEALOGY = 'genealogy';
	const LEGACY = 'legacy';
	
	// Theme subcategories
	const THEME_LOGOS = 'logos';
	const THEME_FAVICONS = 'favicons';
	const THEME_BACKGROUNDS = 'backgrounds';
	const THEME_CATEGORY_ICONS = 'category_icons';
	
	// Cover subcategories
	const COVER_LISTS = 'lists';
	const COVER_SERIES = 'series';
	const COVER_SERIES_MEMBER = 'seriesMember';

	// Genealogy subcategories
	const GENEALOGY_PERSON = 'person';
	const GENEALOGY_OBITUARY = 'obituary';
	
	// File subcategories
	const WEB_BUILDER_PDFS = 'pdfs';
	const DOCUMENTS = 'documents';
	
	private function __construct() {
		global $configArray;
		$sitename = $configArray['Site']['siteName'] ?? 'default';
		$this->baseDataPath = "/data/aspen-discovery/{$sitename}";
		
		$this->initializeDirectories();
	}
	
	public static function getInstance() {
		if (self::$instance === null) {
			self::$instance = new self();
		}
		return self::$instance;
	}
	
	/**
	 * Get the full path for user data storage
	 */
	public function getUserDataPath(...$pathComponents) {
		return $this->baseDataPath . '/' . implode('/', $pathComponents);
	}
	
	/**
	 * Initialize all required directories based on declared sizes
	 */
	private function initializeDirectories() {
		$directories = [
			// Covers - uses large, medium, small
			$this->getUserDataPath(self::CATEGORY_IMAGES, self::COVERS, self::SIZE_ORIGINAL),
			$this->getUserDataPath(self::CATEGORY_IMAGES, self::COVERS, self::SIZE_ORIGINAL, self::COVER_LISTS),
			$this->getUserDataPath(self::CATEGORY_IMAGES, self::COVERS, self::SIZE_ORIGINAL, self::COVER_SERIES),
			$this->getUserDataPath(self::CATEGORY_IMAGES, self::COVERS, self::SIZE_ORIGINAL, self::COVER_SERIES_MEMBER),
			$this->getUserDataPath(self::CATEGORY_IMAGES, self::COVERS, self::SIZE_LARGE),
			$this->getUserDataPath(self::CATEGORY_IMAGES, self::COVERS, self::SIZE_MEDIUM),
			$this->getUserDataPath(self::CATEGORY_IMAGES, self::COVERS, self::SIZE_SMALL),
			
			// Theme logos - uses original + thumbnail
			$this->getUserDataPath(self::CATEGORY_IMAGES, self::THEMES, self::THEME_LOGOS, self::SIZE_ORIGINAL),
			$this->getUserDataPath(self::CATEGORY_IMAGES, self::THEMES, self::THEME_LOGOS, self::SIZE_THUMBNAIL),
			
			// Theme favicons - uses original only
			$this->getUserDataPath(self::CATEGORY_IMAGES, self::THEMES, self::THEME_FAVICONS, self::SIZE_ORIGINAL),
			
			// Theme backgrounds - uses original + thumbnail
			$this->getUserDataPath(self::CATEGORY_IMAGES, self::THEMES, self::THEME_BACKGROUNDS, self::SIZE_ORIGINAL),
			$this->getUserDataPath(self::CATEGORY_IMAGES, self::THEMES, self::THEME_BACKGROUNDS, self::SIZE_THUMBNAIL),
			
			// Theme category icons - uses original + thumbnail
			$this->getUserDataPath(self::CATEGORY_IMAGES, self::THEMES, self::THEME_CATEGORY_ICONS, self::SIZE_ORIGINAL),
			$this->getUserDataPath(self::CATEGORY_IMAGES, self::THEMES, self::THEME_CATEGORY_ICONS, self::SIZE_THUMBNAIL),
			
			// Web builder images - uses all sizes (full instead of original for backward compatibility)
			$this->getUserDataPath(self::CATEGORY_IMAGES, self::WEB_BUILDER, self::SIZE_FULL),
			$this->getUserDataPath(self::CATEGORY_IMAGES, self::WEB_BUILDER, self::SIZE_X_LARGE),
			$this->getUserDataPath(self::CATEGORY_IMAGES, self::WEB_BUILDER, self::SIZE_LARGE),
			$this->getUserDataPath(self::CATEGORY_IMAGES, self::WEB_BUILDER, self::SIZE_MEDIUM),
			$this->getUserDataPath(self::CATEGORY_IMAGES, self::WEB_BUILDER, self::SIZE_SMALL),
			
			// Web resource logos - uses original + thumbnail
			$this->getUserDataPath(self::CATEGORY_IMAGES, self::WEB_RESOURCES, self::THEME_LOGOS, self::SIZE_ORIGINAL),
			$this->getUserDataPath(self::CATEGORY_IMAGES, self::WEB_RESOURCES, self::THEME_LOGOS, self::SIZE_THUMBNAIL),
			
			// Placards - uses original only
			$this->getUserDataPath(self::CATEGORY_IMAGES, self::PLACARDS, self::SIZE_ORIGINAL),
			
			// Events - uses original only
			$this->getUserDataPath(self::CATEGORY_IMAGES, self::EVENTS, self::SIZE_ORIGINAL),
			
			// Rewards - uses full size (like web_builder)
			$this->getUserDataPath(self::CATEGORY_IMAGES, self::REWARDS, self::SIZE_FULL),
			
			// Series - uses original + thumbnail
			$this->getUserDataPath(self::CATEGORY_IMAGES, self::SERIES, self::SIZE_ORIGINAL),
			$this->getUserDataPath(self::CATEGORY_IMAGES, self::SERIES, self::SIZE_THUMBNAIL),
			
			// Calendars - uses original + thumbnail
			$this->getUserDataPath(self::CATEGORY_IMAGES, self::CALENDARS, self::SIZE_ORIGINAL),
			$this->getUserDataPath(self::CATEGORY_IMAGES, self::CALENDARS, self::SIZE_THUMBNAIL),
			
			// Genealogy - uses original + thumbnail
			$this->getUserDataPath(self::CATEGORY_IMAGES, self::GENEALOGY, self::GENEALOGY_PERSON, self::SIZE_ORIGINAL),
			$this->getUserDataPath(self::CATEGORY_IMAGES, self::GENEALOGY, self::GENEALOGY_PERSON, self::SIZE_THUMBNAIL),
			$this->getUserDataPath(self::CATEGORY_IMAGES, self::GENEALOGY, self::GENEALOGY_OBITUARY, self::SIZE_ORIGINAL),
			$this->getUserDataPath(self::CATEGORY_IMAGES, self::GENEALOGY, self::GENEALOGY_OBITUARY, self::SIZE_THUMBNAIL),
			
			// Legacy directories for migration
			$this->getUserDataPath(self::CATEGORY_IMAGES, self::LEGACY, self::SIZE_ORIGINAL),
			$this->getUserDataPath(self::CATEGORY_IMAGES, self::LEGACY, self::SIZE_THUMBNAIL),
			
			// File directories
			$this->getUserDataPath(self::CATEGORY_FILES, self::WEB_BUILDER, self::WEB_BUILDER_PDFS),
			$this->getUserDataPath(self::CATEGORY_FILES, self::DOCUMENTS),
			$this->getUserDataPath(self::CATEGORY_FILES, self::LEGACY),
		];
		
		foreach ($directories as $directory) {
			$this->ensureDirectoryExists($directory);
		}
	}
	
	/**
	 * Ensure a directory exists with proper permissions
	 */
	public function ensureDirectoryExists($path, $owner = 'www-data', $permissions = 0755) {
		if (!is_dir($path)) {
			if (!mkdir($path, $permissions, true)) {
				global $logger;
				if ($logger) {
					$logger->log("Failed to create directory: $path", Logger::LOG_ERROR);
				}
				return false;
			}
			
			// Set proper ownership if possible
			try {
				chgrp($path, $owner);
				chmod($path, $permissions);
			} catch (Exception $e) {
				// Ignore permission errors in development
			}
		}
		return true;
	}
	
	/**
	 * Store a file in the specified location
	 */
	public function storeFile($sourcePath, $destinationPath, $owner = 'www-data', $permissions = 0644) {
		$destinationDir = dirname($destinationPath);
		if (!$this->ensureDirectoryExists($destinationDir, $owner)) {
			return false;
		}
		
		$result = copy($sourcePath, $destinationPath);
		
		if ($result) {
			try {
				chgrp($destinationPath, $owner);
				chmod($destinationPath, $permissions);
			} catch (Exception $e) {
				// Ignore permission errors
			}
		}
		
		return $result;
	}
	
	/**
	 * Get the appropriate storage path for a specific image type and size
	 * Ensures the directory exists before returning the path
	 */
	public function getImagePath($category, $subcategory = null, $size = self::SIZE_ORIGINAL) {
		$path = '';
		switch ($category) {
			case 'theme':
				$path = $this->getUserDataPath(self::CATEGORY_IMAGES, self::THEMES, $subcategory, $size);
				break;
			case 'cover':
				$subcategoryPath = $subcategory ? $subcategory : '';
				$path = $this->getUserDataPath(self::CATEGORY_IMAGES, self::COVERS, $subcategoryPath, $size);
				break;
			case 'web_builder':
				$path = $this->getUserDataPath(self::CATEGORY_IMAGES, self::WEB_BUILDER, $size);
				break;
			case 'web_resource':
				$path = $this->getUserDataPath(self::CATEGORY_IMAGES, self::WEB_RESOURCES, self::THEME_LOGOS, $size);
				break;
			case 'placard':
				$path = $this->getUserDataPath(self::CATEGORY_IMAGES, self::PLACARDS, $size);
				break;
			case 'event':
				$path = $this->getUserDataPath(self::CATEGORY_IMAGES, self::EVENTS, $size);
				break;
			case 'reward':
				$path = $this->getUserDataPath(self::CATEGORY_IMAGES, self::REWARDS, $size);
				break;
			case 'series':
				$path = $this->getUserDataPath(self::CATEGORY_IMAGES, self::SERIES, $size);
				break;
			case 'calendar':
				$path = $this->getUserDataPath(self::CATEGORY_IMAGES, self::CALENDARS, $size);
				break;
			case 'genealogy':
				$path = $this->getUserDataPath(self::CATEGORY_IMAGES, self::GENEALOGY, $subcategory, $size);
				break;
			default:
				$path = $this->getUserDataPath(self::CATEGORY_IMAGES, self::LEGACY, $size);
				break;
		}
		
		// Ensure the directory exists before returning the path
		$this->ensureDirectoryExists($path);
		return $path;
	}
	
	/**
	 * Get the web-accessible URL for an image
	 */
	public function getImageUrl($filename, $category, $subcategory = null, $size = self::SIZE_ORIGINAL) {
		global $configArray;
		$baseUrl = $configArray['Site']['url'];
		
		switch ($category) {
			case 'theme':
				$subcategoryPath = $subcategory ? "{$subcategory}/" : '';
				return "{$baseUrl}/images/themes/{$subcategoryPath}{$size}/{$filename}";
			case 'cover':
				$subcategoryPath = $subcategory ? "{$subcategory}/" : '';
				return "{$baseUrl}/images/covers/{$subcategoryPath}{$size}/{$filename}";
			case 'web_builder':
				return "{$baseUrl}/images/web_builder/{$size}/{$filename}";
			case 'web_resource':
				return "{$baseUrl}/images/web_resources/logos/{$size}/{$filename}";
			case 'placard':
				return "{$baseUrl}/images/placards/{$size}/{$filename}";
			case 'event':
				return "{$baseUrl}/images/events/{$size}/{$filename}";
			case 'reward':
				return "{$baseUrl}/images/rewards/{$size}/{$filename}";
			case 'series':
				return "{$baseUrl}/images/series/{$size}/{$filename}";
			case 'calendar':
				return "{$baseUrl}/images/calendars/{$size}/{$filename}";
			case 'genealogy':
				$subcategoryPath = $subcategory ? "{$subcategory}/" : '';
				return "{$baseUrl}/images/genealogy/{$subcategoryPath}{$size}/{$filename}";
			default:
				return "{$baseUrl}/images/legacy/{$size}/{$filename}";
		}
	}
}
