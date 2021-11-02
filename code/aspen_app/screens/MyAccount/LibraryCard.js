import React, { Component } from "react";
import { View } from "react-native";
import { Center, Spinner, HStack, Text, Image, Flex } from "native-base";
import * as SecureStore from 'expo-secure-store';
import AsyncStorage from "@react-native-async-storage/async-storage";
import Barcode from "react-native-barcode-expo";

// custom components and helper files
import { translate } from '../../util/translations';
import { loadingSpinner } from "../../components/loadingSpinner";
import { loadError } from "../../components/loadError";

export default class LibraryCard extends Component {
	// done to allow the page title to change down in the code
	static navigationOptions = ({ navigation }) => ({
		title: typeof navigation.state.params === "undefined" || typeof navigation.state.params.title === "undefined" ? translate('user_profile.library_card') : navigation.state.params.title,
	});

	constructor(props) {
		super(props);
		this.state = {
			isLoading: true,
			hasError: false,
			error: null,
			libraryCard: null,
			libraryName: null,
			barcodeStyle: "CODE128"
		};

	}

	// store the values into the state
	componentDidMount = async () => {
		this.setState({
			isLoading: false,
			libraryCard: global.barcode,
			libraryName: global.libraryName,
			barcodeStyle: await AsyncStorage.getItem('@libraryBarcodeStyle'),
		});

	};

	render() {
		if (this.state.isLoading) {
			return ( loadingSpinner() );
		}

		return (
			<Center flex={1} px={3}>
				<Flex direction="column">
					<Center>
						<Flex direction="row">
							<Image
                            source={{ uri: global.favicon }}
                            fallbackSource={require("../../themes/default/aspenLogo.png")}
							w={38} h={38} alt={translate('user_profile.library_card')} />
						</Flex>
					</Center>
					<Center pt={8} pb={8}>
						{this.state.libraryCard ? <Barcode value={this.state.libraryCard} format={this.state.barcodeStyle} /> : null }
					</Center>
                    <Center>
                    <Text bold mt={2} fontSize="lg">
                        {this.state.libraryName}
                    </Text>
                    <Text bold pl={3} mt={2} fontSize="md">
                        {this.state.libraryCard}
                    </Text>
                    </Center>
				</Flex>
			</Center>
		);
	}
}
