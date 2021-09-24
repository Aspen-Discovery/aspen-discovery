import React, { Component, useEffect } from "react";
import { ActivityIndicator, FlatList, TouchableOpacity, View, Dimensions, Animated, Pressable, StatusBar } from "react-native";
import { NativeBaseProvider, Center, HStack, Spinner, Button, Actionsheet, useDisclose, Badge, Divider, Box, Text } from "native-base";
import AsyncStorage from "@react-native-async-storage/async-storage";
import { Avatar, ListItem } from "react-native-elements";
import { TabView, SceneMap } from "react-native-tab-view";
import Constants from "expo-constants";
import Stylesheet from "./Stylesheet";
import moment from "moment";

export class ListCKO extends Component {
	// establishes the title for the window
	static navigationOptions = { title: "Checked Out Items" };

	constructor() {
		super();
		this.state = {
			isLoading: false,
		};
	}

	// handles the mount information, setting session variables, etc
	componentDidMount = async () => {
		// store the values into the state
		this.setState({
			password: await AsyncStorage.getItem("password"),
			pathLibrary: await AsyncStorage.getItem("library"),
			pathUrl: await AsyncStorage.getItem("url"),
			patronName: await AsyncStorage.getItem("patronName"),
			username: await AsyncStorage.getItem("username"),
		});

		// grab the checkouts
		this.getCheckOuts();

		// forces a new connection to ensure that we're getting the newest stuff
		this.willFocusSubscription = this.props.navigation.addListener("willFocus", () => {
			this.getCheckOuts();
		});
	};

	// grabs the items checked out to the account
	getCheckOuts = () => {
		const random = new Date().getTime();
		const url =
			this.state.pathUrl +
			"/app/aspenListCKO.php?library=" +
			this.state.pathLibrary +
			"&barcode=" +
			this.state.username +
			"&pin=" +
			this.state.password +
			"&action=ilsCKO&rand=" +
			random;
		console.log(url);
		fetch(url)
			.then((res) => res.json())
			.then((res) => {
				this.setState({
					data: res.Items,
					isLoading: false,
				});
			})
			.catch((error) => {
				console.log("get data error from:" + url + " error:" + error);
			});
	};

	// renders the items on the screen
	renderNativeItem = (item) => {
		var dateDue = moment(item.dateDue).format("MMM D, YYYY");

		return (
			<>
				<ListItem bottomDivider onPress={() => this.onPressItem(item)}>
					<Avatar source={{ uri: item.thumbnail }} />
					<ListItem.Content>
						<Text fontSize="md" bold>
							{item.key}
						</Text>
						<Text fontSize="xs">
							<Text bold fontSize="xs">
								Author:{" "}
							</Text>
							{item.author}
						</Text>
						<Text fontSize="xs">
							<Text bold fontSize="xs">
								Due:{" "}
							</Text>
							{dateDue}
						</Text>
					</ListItem.Content>
					<ListItem.Chevron />
				</ListItem>
			</>
		);
	};

	// handles the on press action and attempts to renew an individual item
	onPressItem = (item) => {
		this.setState({
			actionSheetDisplay: !this.state.actionSheetDisplay,
		});
	};

	// handles the on press action and attempts to renew all items
	onPressRenewAll = () => {
		this.props.navigation.navigate("RenewAll", {});
	};

	_listEmptyComponent = () => {
		return (
			<ListItem bottomDivider>
				<ListItem.Content>
					<ListItem.Title>No items currently checked out.</ListItem.Title>
				</ListItem.Content>
			</ListItem>
		);
	};

	getHeader = () => {
		return (
			<Box>
				<Center mt={5}>
					<Button.Group>
						<Button onPress={() => this.onPressRenewAll()}>Renew All</Button>
						<Button onPress={() => this.getCheckOuts()}>Refresh</Button>
					</Button.Group>
				</Center>
			</Box>
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
			<FlatList
				data={this.state.data}
				ListEmptyComponent={this._listEmptyComponent()}
				renderItem={({ item }) => this.renderNativeItem(item)}
				ListFooterComponent={this.getHeader()}
			/>
		);
	}
}

export class ListHold extends Component {
	constructor() {
		super();
		this.state = {
			isLoading: false,
		};
	}

	// handles the mount information, setting session variables, etc
	componentDidMount = async () => {
		// store the values into the state
		this.setState({
			password: await AsyncStorage.getItem("password"),
			pathLibrary: await AsyncStorage.getItem("library"),
			pathUrl: await AsyncStorage.getItem("url"),
			patronName: await AsyncStorage.getItem("patronName"),
			username: await AsyncStorage.getItem("username"),
		});

		// grab the checkouts
		this.getHolds();

		// forces a new connection to ensure that we're getting the newest stuff
		this.willFocusSubscription = this.props.navigation.addListener("willFocus", () => {
			this.getHolds();
		});
	};

	// grabs the items checked out to the account
	getHolds = () => {
		const random = new Date().getTime();
		const url =
			this.state.pathUrl +
			"/app/aspenListHolds.php?library=" +
			this.state.pathLibrary +
			"&barcode=" +
			this.state.username +
			"&pin=" +
			this.state.password +
			"&action=ilsCKO&rand=" +
			random;
		console.log(url);
		fetch(url)
			.then((res) => res.json())
			.then((res) => {
				this.setState({
					data: res.Items,
					isLoading: false,
				});
			})
			.catch((error) => {
				console.log("get data error from:" + url + " error:" + error);
			});
	};

	// renders the items on the screen
	renderNativeItem = (item) => {
		var position = item.position;
		var subtitle = "By: " + item.author + "\nYour position in queue: " + item.position;
		if (position.length > 5) {
			subtitle = "By: " + item.author + "\n" + item.position;
		}
		if (item.holdSource != "ILS") {
			subtitle += " (" + item.holdSource + ")";
		}

		return (
			<ListItem bottomDivider onPress={() => this.onPressItem(item)}>
				<Avatar rounded source={{ uri: item.thumbnail }} />
				<ListItem.Content>
					<ListItem.Title>{item.key}</ListItem.Title>
					<ListItem.Subtitle>{subtitle}</ListItem.Subtitle>
				</ListItem.Content>
				<ListItem.Chevron />
			</ListItem>
		);
	};

	// remains for future hold manipulation (suspend, unsuspend, etc)
	onPressItem = (item) => {
		Alert.alert("Coming Soon", "We are constantly trying to improve the App. Manipulating holds is not yet available. We hope to include this in an upcoming version.", [
			{ text: "Close" },
		]);
	};

	_listEmptyComponent = () => {
		return (
			<ListItem bottomDivider>
				<ListItem.Content>
					<ListItem.Title>You've got no items on hold.</ListItem.Title>
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
			<View style={Stylesheet.searchResultsContainer}>
				<FlatList data={this.state.data} ListEmptyComponent={this._listEmptyComponent()} renderItem={({ item }) => this.renderNativeItem(item)} />
			</View>
		);
	}
}

const FirstRoute = () => <ListCKO />;

const SecondRoute = () => <ListHold />;

const initialLayout = { width: Dimensions.get("window").width };

const renderScene = SceneMap({
	first: FirstRoute,
	second: SecondRoute,
});

export default function MyItems() {
	const [index, setIndex] = React.useState(0);
	const [routes] = React.useState([
		{ key: "first", title: "Checked Out" },
		{ key: "second", title: "On Hold" },
	]);

	return (
		<>
			<TabHeader />
			<TabView
				navigationState={{ index, routes }}
				renderScene={renderScene}
				onIndexChange={setIndex}
				initialLayout={initialLayout}
				style={{ marginTop: StatusBar.currentHeight }}
			/>
		</>
	);
}
