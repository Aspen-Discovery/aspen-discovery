module.exports = () => {
	let config = process.env.APP_ENV;
	if(config === "" || typeof config === 'undefined') {
		return require("./app-configs/app.lida.json");
	}
	return require("./app-configs/app." + config + ".json");
};