import React, {Component} from "react";
import AsyncStorage from '@react-native-async-storage/async-storage';
import {Badge, Box, Center, FlatList, Image, Pressable, Text, HStack, VStack, ScrollView} from "native-base";
import moment from "moment";

// custom components and helper files
import {loadingSpinner} from "../../../components/loadingSpinner";
import {getHolds, getLists, getSavedSearches} from "../../../util/loadPatron";
import {userContext} from "../../../context/user";

export default class MySavedSearches extends Component {
	constructor() {
		super();
		this.state = {
			isLoading: true,
			hasError: false,
			error: null,
			libraryUrl: '',
			searches: []
		};
	}

	_fetchSearches = async () => {
		const { route } = this.props;
		const libraryUrl = route.params?.libraryUrl ?? 'null';

		await getSavedSearches(libraryUrl).then(response =>
			this.setState({
				searches: response
			})
		);
	}

	componentDidMount = async () => {
		await this._fetchSearches().then(r => {
			this.setState({
				isLoading: false
			})
		});

		this.interval = setInterval(() => {
			this._fetchSearches();
		}, 1000)
		return () => clearInterval(this.interval)

	};

	componentWillUnmount() {
		clearInterval(this.interval);
	}

	// renders the items on the screen
	renderSearch = (item, libraryUrl) => {

		let hasNewResults = 0;
		if(typeof item.hasNewResults !== "undefined") {
			hasNewResults = item.hasNewResults;
		}

		console.log(hasNewResults);

		return (
			<Pressable onPress={() => {this.openList(item.id, item, libraryUrl)}} borderBottomWidth="1" _dark={{ borderColor: "gray.600" }} borderColor="coolGray.200" pl="1" pr="1" py="2">
				<HStack space={3} justifyContent="flex-start">
					<VStack space={1}>
						{/*<Image source={{uri: item.cover}} alt={item.title} size="lg" resizeMode="contain" />*/}
					</VStack>
					<VStack space={1} justifyContent="space-between" maxW="80%">
						<Box>
							<Text bold fontSize="md">{item.title}  {hasNewResults == 1 ? (<Badge mb="-0.5" colorScheme="warning">Updated</Badge>) : null}</Text>
							<Text fontSize="9px" italic>Created on {item.created}</Text>
						</Box>
					</VStack>
				</HStack>
			</Pressable>
		);
	};

	openList = (id, item, libraryUrl) => {
		this.props.navigation.navigate("AccountScreenTab", {screen: 'SavedSearch', params: { search: id, details: item, name: item.title, libraryUrl: libraryUrl }});
	};

	_listEmptyComponent = () => {
		return (
			<Center mt={5} mb={5}>
				<Text bold fontSize="lg">
					You have no saved searches.
				</Text>
			</Center>
		);
	};


	static contextType = userContext;

	render() {
		const {searches} = this.state;
		const user = this.context.user;
		const location = this.context.location;
		const library = this.context.library;

		if (this.state.isLoading) {
			return (loadingSpinner());
		}

		return (
			<ScrollView>
				<Box safeArea={2} h="100%">
					<FlatList
						data={searches}
						ListEmptyComponent={this._listEmptyComponent()}
						renderItem={({item}) => this.renderSearch(item, library.baseUrl)}
						keyExtractor={(item) => item.id}
					/>
				</Box>
			</ScrollView>
		);

	}
}