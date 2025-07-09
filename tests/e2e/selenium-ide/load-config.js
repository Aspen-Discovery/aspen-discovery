// This script loads the site-specific test config files and injects the variables into the Selenium IDE test
var fs = require('fs');
var path = require('path');

// Function to process a single site
function processSite(siteName) {
	console.log('Processing site:', siteName);

	// Path to the site-specific config file in conf subdirectory
	var configPath = path.join(__dirname, 'sites', siteName, 'conf', siteName + '-test-config.json');

	// Load the configuration file
	var config;
	try {
		var configData = fs.readFileSync(configPath, 'utf8');
		config = JSON.parse(configData);
		console.log('Configuration loaded successfully from:', configPath);
	} catch (error) {
		console.error('Error loading configuration file for site ' + siteName + ':', error.message);
		console.error('Please make sure ' + siteName + '-test-config.json exists in the tests/e2e/selenium-ide/sites/' + siteName + ' directory');
		return null;
	}

	// Create the site directory if it doesn't exist
	var siteDir = path.join(__dirname, 'sites', siteName);

	try {
		if (!fs.existsSync(siteDir)) {
			fs.mkdirSync(siteDir, {recursive: true});
			console.log('Created site directory:', siteDir);
		}
	} catch (error) {
		console.error('Error creating site directory:', error.message);
		return null;
	}

	// Find all .side files in the current directory
	var sideFilesPath = path.join(__dirname, 'test-templates');
	var sideFiles = fs.readdirSync(sideFilesPath)
		.filter(function (file) {
			return file.endsWith('.side');
		});

	if (sideFiles.length === 0) {
		console.error('No .side files found in the directory.');
		return null;
	}

	// For each .side file, process and output to the site directory
	sideFiles.forEach(function (sideFile) {
		var sideFilePath = path.join(sideFilesPath, sideFile);
		var processedFilePath = path.join(siteDir, sideFile.replace('.side', '.' + siteName + '.side'));
		try {
			var sideFileContent = fs.readFileSync(sideFilePath, 'utf8');

			// Generalized variable replacement: replace any ${varName} with config[varName], supporting nested properties like ${credentials.username}
			function flattenConfig(obj, prefix = '', res = {}) {
				for (const key in obj) {
					if (Object.prototype.hasOwnProperty.call(obj, key)) {
						const value = obj[key];
						const newKey = prefix ? `${prefix}.${key}` : key;
						if (typeof value === 'object' && value !== null) {
							flattenConfig(value, newKey, res);
						} else {
							res[newKey] = value;
						}
					}
				}
				return res;
			}

			var flatConfig = flattenConfig(config);
			var processedContent = sideFileContent.replace(/\${([\w.]+)}/g, function (match, varName) {
				if (flatConfig.hasOwnProperty(varName)) {
					return flatConfig[varName];
				} else {
					// If variable not found, leave as-is or replace with empty string
					return match;
				}
			});
			fs.writeFileSync(processedFilePath, processedContent, 'utf8');
			console.log('Processed Selenium IDE test file created successfully at:', processedFilePath);
		} catch (error) {
			console.error('Error processing', sideFile, 'for site', siteName + ':', error.message);
		}
	});
	return config;
}

// Get all site directories
var sitesDir = path.join(__dirname, 'sites');
var processedSites = [];

// Process all sites
if (fs.existsSync(sitesDir)) {
	var siteDirs = fs.readdirSync(sitesDir, {withFileTypes: true})
		.filter(function (dirent) {
			return dirent.isDirectory();
		})
		.map(function (dirent) {
			return dirent.name;
		});

	for (var i = 0; i < siteDirs.length; i++) {
		var siteName = siteDirs[i];
		var config = processSite(siteName);
		if (config) {
			processedSites.push(siteName);
		}
	}
}

if (processedSites.length === 0) {
	console.error('No site-specific config files were processed. Please make sure at least one config file exists.');
	process.exit(1);
}

console.log('Successfully processed ' + processedSites.length + ' site(s):', processedSites.join(', '));
console.log('Please open the processed file in Selenium IDE instead of the original file.');

// Export the entire defaultConfig object for generalized variable support
var defaultSite = processedSites[0];
var defaultConfigPath = path.join(__dirname, 'sites', defaultSite, 'conf', defaultSite + '-test-config.json');

try {
	var defaultConfigData = fs.readFileSync(defaultConfigPath, 'utf8');
	var defaultConfig = JSON.parse(defaultConfigData);

	module.exports = defaultConfig;
} catch (error) {
	console.error('Error loading default configuration for export:', error.message);
	process.exit(1);
}
