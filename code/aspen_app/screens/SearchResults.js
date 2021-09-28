import React, { Component } from "react";
import { ListItem } from "react-native-elements";
import {
	Alert,
	Container,
	HStack,
	VStack,
	Center,
	Spinner,
	Button,
	Divider,
	Flex,
	Box,
	Badge,
	Text,
	Icon,
	ChevronRightIcon,
	Input,
	FormControl,
	FlatList,
	Heading,
	Avatar,
} from "native-base";
import * as SecureStore from 'expo-secure-store';
import { MaterialCommunityIcons, Ionicons, MaterialIcons } from "@expo/vector-icons";

export default class SearchResults extends Component {
	static navigationOptions = ({ navigation }) => ({
		title: typeof navigation.state.params === "undefined" || typeof navigation.state.params.title === "undefined" ? "Results" : navigation.state.params.title,
	});

	constructor() {
		super();
		this.state = { isLoading: true };
	}

	// handles the mount information, setting session variables, etc
	componentDidMount = async () => {
		//const level      = this.props.navigation.state.params.level;
		//const format     = this.props.navigation.state.params.format;
		//const searchType = this.props.navigation.state.params.searchType;

		// need to replace any space in the search term with a friendly %20
		const searchTerm = encodeURI(this.props.navigation.state.params.searchTerm.replace(" ", "%20"));

		/// need to build out the URL to call, including the search term
		const url = global.libraryUrl + "/app/aspenSearchResults.php?library=" + global.solrScope + "&searchTerm=" + searchTerm; //+ '&searchType=' + searchType;

		fetch(url)
			.then((res) => res.json())
			.then((res) => {
				this.setState({
					data: res.Items,
					isLoading: false,
				});
			})
			.catch((error) => {
				console.log("Unable to fetch data from <" + url + "> in SearchResults");
				console.log("Error: " + error)
            });

        this.props.navigation.setParams({
            title: "Results for " + this.props.navigation.state.params.searchTerm,
        });
	};

	// renders the items on the screen
	renderNativeItem = (item) => {
		const subtitle = item.author + " (" + item.format + ")";
		if(item.author) {
		    const author = item.author;
		}

		if (item.image) {
			return (
				<ListItem bottomDivider onPress={() => this.onPressItem(item)}>
					<Avatar source={{ uri: item.image }} size="58px" />
					<ListItem.Content>
						<Badge colorScheme="tertiary" variant="outline" rounded="4px" mb={1}>
							{item.format}
						</Badge>
						<Text fontSize="md" bold mb={0.5} color="coolGray.800">
							{item.title}
						</Text>
						{item.author ? <Text fontSize="xs" color="coolGray.600">
							By: {item.author}
						</Text> : null }
					</ListItem.Content>
					<ListItem.Chevron />
				</ListItem>
			);
		} else {
			return (
				<ListItem bottomDivider onPress={() => this.onPressItem(item)}>
					<Avatar size="58px" />
					<ListItem.Content>
						<Badge color="tertiary.400" variant="outline" rounded="4px" mb={1}>
							{item.format}
						</Badge>
						<Text fontSize="md" bold mb={0.5} color="coolGray.800">
							{item.title}
						</Text>
						<Text fontSize="xs" color="coolGray.600">
							By: {item.author}
						</Text>
					</ListItem.Content>
					<ListItem.Chevron />
				</ListItem>
			);
		}
	};

	// handles the on press action
	onPressItem = (item) => {
		this.props.navigation.navigate("ItemDetails", { item });
	};

	_listEmptyComponent = () => {
		return (
			<Center flex={1} justifyContent="center" alignItems="center" pt="70%">
				<VStack space={5}>
					<Heading>No results found.</Heading>
					<Button onPress={() => this.props.navigation.goBack()}>Try a new search</Button>
				</VStack>
			</Center>
		);
	};

	render() {
		if (this.state.isLoading) {
			return (
				<Center flex={1}>
					<HStack>
						<Spinner accessibilityLabel="Searching..." />
					</HStack>
				</Center>
			);
		}

		return (
			<Box w={{ base: "100%", md: "25%" }}>
				<FlatList
					data={this.state.data}
					ListEmptyComponent={this._listEmptyComponent()}
					renderItem={({ item }) => this.renderNativeItem(item)}
					keyExtractor={(item, index) => index.toString()}
				/>
			</Box>
		);
	}
}
