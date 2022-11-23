const fs = require('fs');
fs.readFile('ORIGINAL_app.config.js', 'utf8', function (err,data) {
	if (err) {
		return console.log(err);
	} else {
		console.log("✅ Found original app.config.js")
		fs.rename('ORIGINAL_app.config.js', 'app.config.js', () => {
			if (err) {
				return console.log(err);
			} else {
				console.log("✅ Restored original app.config.js")
			}
		});
	}
});