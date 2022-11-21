const fs = require('fs');

fs.copyFile("app.config.js", "ORIGINAL_app.config.js", (err) => {
	if (err) {
		return console.log(err);
	} else {
		console.log("✅ Copied original config file.")
	}
});