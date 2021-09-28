import React, { Component } from "react";
import { Center, HStack, Spinner, Button, Box, Text, FlatList } from "native-base";
import * as SecureStore from 'expo-secure-store';
import { ListItem } from "react-native-elements";
import Constants from "expo-constants";

export default class More extends Component {
	constructor() {
		super();
		this.state = { isLoading: true };
	}

	// handles the mount information, setting session variables, etc
	componentDidMount = async () => {
		const url = global.libraryUrl + "/app/aspenMoreDetails.php?id=" + global.locationId + "&library=" + global.solrScope + "&version=" + global.version;

		fetch(url)
			.then((res) => res.json())
			.then((res) => {
				this.setState({
					data: res.options,
					isLoading: false,
				});
			})
			.catch((error) => {
				console.log("get data error from:" + url + " error:" + error);
			});
	};

	renderNativeItem = (item) => {
		return (
			<ListItem bottomDivider onPress={() => this.onPressItem(item.path)}>
				<ListItem.Content>
					<Text bold>{item.title}</Text>
					<Text fontSize="sm" color="coolGray.600">{item.subtitle}</Text>
				</ListItem.Content>
				<ListItem.Chevron />
			</ListItem>
		);
	};

	_logout = async () => {
        await SecureStore.deleteItemAsync("sessionId");
        await SecureStore.deleteItemAsync("pickupLocation");
        await SecureStore.deleteItemAsync("patronName");
        await SecureStore.deleteItemAsync("library");
        await SecureStore.deleteItemAsync("libraryName");
        await SecureStore.deleteItemAsync("locationId");
        await SecureStore.deleteItemAsync("solrScope");
        await SecureStore.deleteItemAsync("pathUrl");
        await SecureStore.deleteItemAsync("version");
        await SecureStore.deleteItemAsync("userKey");
        await SecureStore.deleteItemAsync("secretKey");
        await SecureStore.deleteItemAsync("userToken");
		this.props.navigation.navigate("Permissions");
	};

	onPressItem = (item) => {
		this.props.navigation.navigate(item, { item });
	};

	_listEmptyComponent = () => {
		return (
			<ListItem bottomDivider>
				<ListItem.Content>
					<Text bold>Something went wrong. Please try again later.</Text>
				</ListItem.Content>
			</ListItem>
		);
	};

	render() {
		if (this.state.isLoading) {
			return (
				<Center flex={1}>
					<HStack>
						<Spinner accessibilityLabel="Loading..." />
					</HStack>
				</Center>
			);
		}

		return (
			<Box>
				<FlatList
					data={this.state.data}
					ListEmptyComponent={this._listEmptyComponent()}
					renderItem={({ item }) => this.renderNativeItem(item)}
					keyExtractor={(item, index) => index.toString()}
				/>

                <Center mt={5}>
                    <Button onPress={this._logout}>Logout</Button>
                </Center>
			</Box>
		);
	}
}
