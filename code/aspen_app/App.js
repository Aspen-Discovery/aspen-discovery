import React, {Component} from "react";
import {NativeBaseProvider, StatusBar} from "native-base";
import {SSRProvider} from "@react-aria/ssr";
import App from "./components/navigation";
import {createTheme, saveTheme} from "./themes/theme";

export default class AppContainer extends Component {
	constructor(props) {
		super(props);
		this.state = { themeSet: false };
		this.aspenTheme = null;
	}

	componentDidMount = async () => {
		await createTheme().then(async response => {
			this.aspenTheme = response;
			this.setState({ themeSet: true })
			this.aspenTheme.colors.primary['baseContrast'] === "#000000" ? this.setState({ statusBar: "dark-content" }) : this.setState({ statusBar: "light-content" })
			console.log("Theme set from createTheme in App.js");
			await saveTheme();
		});
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
