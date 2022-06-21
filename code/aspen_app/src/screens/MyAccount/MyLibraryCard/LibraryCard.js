import React, {Component} from "react";
import AsyncStorage from '@react-native-async-storage/async-storage';
import {Center, Flex, Image, Text} from "native-base";
import Barcode from "react-native-barcode-expo";
import Constants from 'expo-constants';
import _ from "lodash";

// custom components and helper files
import {translate} from '../../../translations/translations';
import {loadingSpinner} from "../../../components/loadingSpinner";
import {loadError} from "../../../components/loadError";

export default class LibraryCard extends Component {
	constructor(props) {
		super(props);
		this.state = {
			isLoading: true,
			hasError: false,
			error: null,
			barcodeStyleInvalid: false,
			user: {
				displayName: "",
				cat_username: "0",
				expires: ""
			},
			library: {
				barcodeStyle: "CODE128",
			},
			location: {
				name: "",
			},
		};
		this.loadUser();
		this.loadLocation();
		this.loadLibrary();
	}

	loadUser = async () => {
		const tmp = await AsyncStorage.getItem('@patronProfile');
		const profile = JSON.parse(tmp);
		this.setState({
			user: profile,
			isLoading: false,
		})
	}

	loadLocation = async () => {
		const tmp = await AsyncStorage.getItem('@patronLibrary');
		const profile = JSON.parse(tmp);
		this.setState({
			location: profile,
			isLoading: false,
		})
	}

	loadLibrary = async () => {
		const tmp = await AsyncStorage.getItem('@libraryInfo');
		const profile = JSON.parse(tmp);
		console.log(profile);
		if(typeof profile.barcodeStyle !== null){
			this.setState({
				library: profile,
				isLoading: false,
			})
		} else {
			this.setState({
				isLoading: false,
			})
		}
	}


	// store the values into the state
	componentDidMount = async () => {
		this.setState({
			isLoading: false,
		});

		await this.loadUser();
		await this.loadLocation();
		await this.loadLibrary();
	};


	invalidFormat = () => {
		this.setState({
			barcodeStyleInvalid: true,
		});
	};

	render() {
		const barcodeStyle = this.state.library.barcodeStyle;
		const logo = Constants.manifest.extra.libraryCardLogo;

		let barcodeValue = "UNKNOWN";
		if(this.state.user.cat_username) {
			barcodeValue = this.state.user.cat_username;
		}

		if (this.state.isLoading || this.state.user.cat_username === "") {
			return (loadingSpinner());
		}

		if (this.state.hasError) {
			return (loadError(this.state.error));
		}

		if(_.isNull(this.state.barcodeStyle)) {
			return (
				<Center flex={1} px={3}>
					<Flex direction="column" bg="white" maxW="90%" px={8} py={5} borderRadius={20}>
						<Center>
							<Flex direction="row">
								<Image
									source={{uri: logo}}
									fallbackSource={require("../../../themes/default/aspenLogo.png")}
									w={42} h={42} alt={translate('user_profile.library_card')}/>
								<Text bold ml={3} mt={2} fontSize="lg" color="darkText">
									{this.state.location.name}
								</Text>
							</Flex>
						</Center>
						<Center pt={8}>
							<Text pb={2} color="darkText">{this.state.user.displayName}</Text>
							<Text color="darkText" bold fontSize="xl">{this.state.user.cat_username}</Text>
							{this.state.user.expires ? (<Text color="darkText" fontSize={10}>Expires on {this.state.user.expires}</Text>) : null}
						</Center>
					</Flex>
				</Center>
			)
		}

		return (
			<Center flex={1} px={3}>
				<Flex direction="column" bg="white" maxW="90%" px={8} py={5} borderRadius={20}>
					<Center>
						<Flex direction="row">
							<Image
								source={{uri: logo}}
								fallbackSource={require("../../../themes/default/aspenLogo.png")}
								w={42} h={42} alt={translate('user_profile.library_card')}/>
							<Text bold ml={3} mt={2} fontSize="lg" color="darkText">
								{this.state.location.name}
							</Text>
						</Flex>
					</Center>
					<Center pt={8}>
						<Barcode value={barcodeValue} format={barcodeStyle}
						         text={barcodeValue} background="warmGray.100" />
						{this.state.user.expires ? (<Text color="darkText" fontSize={10} pt={2}>Expires on {this.state.user.expires}</Text>) : null}
					</Center>
				</Flex>
			</Center>
		);
	}
}
