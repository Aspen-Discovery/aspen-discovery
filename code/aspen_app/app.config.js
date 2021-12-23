export default {
	name: "Aspen LiDA",
	slug: "aspen-lida",
	owner: "bywater-solutions",
	privacy: "public",
	version: "21.15.01",
	orientation: "portrait",
	icon: "./assets/LiDA_Icon1024.png",
	splash: {
		image: "./assets/android/PNG/xxxhdpi/LiDA_xxxhdpi_432px_Icon.png",
		resizeMode: "contain",
		backgroundColor: "#333D42"
	},
	updates: {
		"fallbackToCacheTimeout": 0
	},
	assetBundlePatterns: [
		"**/*"
	],
	ios: {
		buildNumber: "19",
		bundleIdentifier: "org.aspendiscovery.mobile",
		supportsTablet: true,
		icon: "./assets/LiDA_Icon1024.png",
		infoPlist: {
			NSLocationWhenInUseUsageDescription: "This app uses your location to find nearby libraries to make logging in easier",
			LSApplicationQueriesSchemes: [
				"comgooglemaps",
				"citymapper",
				"uber",
				"lyft",
				"waze"
			],
			CFBundleAllowMixedLocalizations: true
		}
	},
	android: {
		package: "org.aspendiscovery.mobile",
		versionCode: 19,
		permissions: [
			"ACCESS_COARSE_LOCATION",
			"ACCESS_FINE_LOCATION",
			"FOREGROUND_SERVICE"
		],
		adaptiveIcon: {
			foregroundImage: "./assets/android/png/xxxhdpi/LiDA_xxxhdpi_432px_Icon.png",
			backgroundImage: "./assets/android/png/xxxhdpi/LiDA_xxxhdpi_432px_Background.png"
		},
		icon: "./assets/LiDA_Icon512.png"
	},
	web: {
		"favicon": "./assets/LiDA_Icon512.png"
	}
};