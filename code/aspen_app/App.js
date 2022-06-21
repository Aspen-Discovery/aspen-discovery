import React, {Component} from "react";
import Constants from 'expo-constants';
import {NativeBaseProvider, StatusBar} from "native-base";
import {SSRProvider} from "@react-aria/ssr";
import * as Sentry from 'sentry-expo';
import App from "./src/components/navigation";
import {createTheme, saveTheme} from "./src/themes/theme";

import { LogBox } from 'react-native';
LogBox.ignoreLogs(['Warning: ...']); // Ignore log notification by message
LogBox.ignoreAllLogs();//Ignore all log notifications

if (!__DEV__) {
	Sentry.init({
		dsn: Constants.manifest.extra.sentryDSN,
		enableInExpoDevelopment: true,
		enableAutoSessionTracking: false,
		sessionTrackingIntervalMillis: 10000,
		debug: true, // If `true`, Sentry will try to print out useful debugging information if something goes wrong with sending the event. Set it to `false` in production
	});
}


export default class AppContainer extends Component {
	constructor(props) {
		super(props);
		this.state = {
			themeSet: false,
			themeSetSession: 0,
		};
		this.aspenTheme = null;
	}

	componentDidMount = async () => {
		await createTheme().then(async response => {
			if(this.state.themeSetSession !== Constants.sessionId) {
				this.aspenTheme = response;
				this.setState({ themeSet: true, themeSetSession: Constants.sessionId })
				this.aspenTheme.colors.primary['baseContrast'] === "#000000" ? this.setState({ statusBar: "dark-content" }) : this.setState({ statusBar: "light-content" })
				console.log("Theme set from createTheme in App.js");
				await saveTheme();
			} else {
				console.log("Theme previously saved.")
			}
		});
		console.log(this.state.themeSetSession)
	}

	render() {
		if(this.state.themeSet) {
			return (
				<SSRProvider>
					<NativeBaseProvider theme={this.aspenTheme}>
						<StatusBar barStyle={this.state.statusBar} />
						<App/>
					</NativeBaseProvider>
				</SSRProvider>
			);
		} else {
			return (
				<SSRProvider>
					<NativeBaseProvider>
						<StatusBar barStyle="dark-content"/>
						<App/>
					</NativeBaseProvider>
				</SSRProvider>
			);
		}
	}
}
