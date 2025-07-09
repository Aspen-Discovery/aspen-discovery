// This script runs the load-config.js script to prepare the Selenium IDE test file
// by replacing variables with values from site-specific configuration files

console.log('Preparing Selenium IDE test file...');
require('./load-config.js');
console.log('Done! Please open the processed file in Selenium IDE.');
