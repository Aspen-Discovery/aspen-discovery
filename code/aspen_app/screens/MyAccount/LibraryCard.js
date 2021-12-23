import React, {Component} from "react";
import {Center, Flex, Image, Text} from "native-base";
import Barcode from "react-native-barcode-expo";

// custom components and helper files
import {translate} from '../../util/translations';
import {loadingSpinner} from "../../components/loadingSpinner";
import {loadError} from "../../components/loadError";

export default class LibraryCard extends Component {
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

	static navigationOptions = ({navigation}) => ({
		title: typeof navigation.state.params === "undefined" || typeof navigation.state.params.title === "undefined" ? translate('user_profile.library_card') : navigation.state.params.title,
	});

	// store the values into the state
	componentDidMount = async () => {
		this.setState({
			isLoading: false,
			libraryCard: global.barcode,
			libraryName: global.libraryName,
			barcodeStyle: global.barcodeStyle,
		});

	};

	invalidFormat = () => {
		this.setState({
			hasError: true,
			error: "Invalid barcode for format"
		});
	};

	render() {
		if (this.state.isLoading) {
			return (loadingSpinner());
		}

		if (this.state.hasError) {
			return (loadError(this.state.error));
		}

		return (
			<Center flex={1} px={3}>
				<Flex direction="column">
					<Center>
						<Flex direction="row">
							<Image
								source={{uri: global.favicon}}
								fallbackSource={require("../../themes/default/aspenLogo.png")}
								w={38} h={38} alt={translate('user_profile.library_card')}/>
							<Text bold ml={3} mt={2} fontSize="lg">
								{this.state.libraryName}
							</Text>
						</Flex>
					</Center>
					<Center pt={8}>
						<Barcode value={this.state.libraryCard} format={this.state.barcodeStyle}
						         text={this.state.libraryCard} onError={this.invalidFormat}/>
					</Center>
				</Flex>
			</Center>
		);
	}
}
