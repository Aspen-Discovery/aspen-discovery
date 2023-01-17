const fs = require('fs');
fs.readFile('app.config.js', 'utf8', function (err, data) {
	if (err) {
		return console.log(err);
	} else {
		console.log('✅ Found app.config.js');
		const result = data.replace('./google-services.json', process.env.GOOGLE_SERVICES_JSON);
		fs.writeFile('app.config.js', result, 'utf8', function (err) {
			if (err) {
				return console.log(err);
			}
			console.log('✅ Updated app.config.js with Google Services JSON file');
		});
	}
});