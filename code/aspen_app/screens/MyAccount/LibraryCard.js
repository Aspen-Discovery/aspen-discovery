import React, { Component } from "react";
import { View } from "react-native";
import { Center, Spinner, HStack, Text, Image, Flex } from "native-base";
import * as SecureStore from 'expo-secure-store';
import Barcode from "react-native-barcode-expo";

import { loadingSpinner } from "../../components/loadingSpinner";
import { loadError } from "../../components/loadError";

export default class LibraryCard extends Component {
	// done to allow the page title to change down in the code
	static navigationOptions = ({ navigation }) => ({
		title: typeof navigation.state.params === "undefined" || typeof navigation.state.params.title === "undefined" ? "Your Library Card" : navigation.state.params.title,
	});

	constructor(props) {
		super(props);
		this.state = {
			isLoading: true,
			hasError: false,
			error: null,
			libraryCard: null,
		};

	}

	// store the values into the state
	componentDidMount = async () => {
		this.setState({
			isLoading: false,
			libraryCard: global.barcode,
		});

		// change the page name to personalize it
		this.props.navigation.setParams({
			title: global.patron + "'s Library Card",
		});
	};

	render() {
		if (this.state.isLoading) {
			return ( loadingSpinner() );
		}

		return (
			<Center flex={1} px={3}>
				<Flex direction="column" mb={2.5} mt={1.5} bgColor="white" p={10} borderRadius={12} borderColor="black" borderWidth={1}>
					<Center>
						<Flex direction="row">
							<Image
                            source={{ uri: global.favicon }}
                            fallbackSource={require("../../themes/default/aspenLogo.png")}
							w={38} h={38} alt="Digital Library Card" />
							<Text bold pl={3} mt={2} fontSize="lg">
								{this.state.libraryCard}
							</Text>
						</Flex>
					</Center>
					<Center pt={5}>
						{this.state.libraryCard ? <Barcode value={this.state.libraryCard} format="CODE128" /> : <Text>No library barcode to load.</Text> }
					</Center>
				</Flex>
			</Center>
		);
	}
}
