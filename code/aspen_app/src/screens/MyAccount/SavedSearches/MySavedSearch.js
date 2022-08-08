import React, {Component} from "react";
import AsyncStorage from '@react-native-async-storage/async-storage';
import {
	Actionsheet,
	Avatar,
	Badge,
	Box,
	Button,
	Center,
	Divider,
	FlatList,
	IconButton,
	Container,
	Pressable,
	Text,
	Stack,
	HStack,
	VStack,
	Image
} from "native-base";
import {MaterialIcons} from "@expo/vector-icons";
import moment from "moment";
import * as WebBrowser from 'expo-web-browser';

// custom components and helper files
import {translate} from '../../../translations/translations';
import {loadingSpinner} from "../../../components/loadingSpinner";
import {removeLinkedAccount, renewAllCheckouts} from "../../../util/accountActions";
import {getListTitles, getSavedSearchTitles, removeTitlesFromList} from "../../../util/loadPatron";
import {ScrollView} from "react-native";
import {userContext} from "../../../context/user";
import _ from "lodash";
import AddToList from "../../Search/AddToList";

export default class MySavedSearch extends Component {
	constructor() {
		super();
		this.state = {
			isLoading: true,
			hasError: false,
			error: null,
			user: [],
			search: [],
			searchDetails: [],
			id: null,
		};
	}

	loadSearch = async () => {
		const { route } = this.props;
		const givenSearchId = route.params?.search ?? 0;
		const libraryUrl = route.params?.libraryUrl ?? 0;

		await getSavedSearchTitles(givenSearchId, libraryUrl).then(response => {
			this.setState({
				search: response,
				id: givenSearchId,
			})
		});
	}

	componentDidMount = async () => {
		const { route } = this.props;
		const givenSearch = route.params?.details ?? '';
		const libraryUrl = route.params?.libraryUrl ?? '';

		this.setState({
			isLoading: false,
			searchDetails: givenSearch,
			libraryUrl: libraryUrl
		});

		await this.loadSearch();

		this.interval = setInterval(() => {
			this.loadSearch();
		}, 100000)

		return () => clearInterval(this.interval)

	};

	componentWillUnmount() {
		clearInterval(this.interval);
	}

	// renders the items on the screen
	renderItem = (item, libraryUrl) => {
		const imageUrl = libraryUrl + item.image;
		let formats = [];
		if(item.format) {
			formats = this.getFormats(item.format);
		}
		let isNew = false;
		if(typeof item.isNew !== "undefined") {
			isNew = item.isNew;
		};
		return (
			<Pressable borderBottomWidth="1" _dark={{ borderColor: "gray.600" }} borderColor="coolGray.200" pl="4" pr="5" py="2" onPress={() => this.openItem(item.id, this.state.libraryUrl)}>
				<HStack space={3} justifyContent="flex-start" alignItems="flex-start">
					<VStack>
						{isNew ? (<Container zIndex={1}><Badge colorScheme="warning" shadow={1} mb={-3} ml={-1} _text={{fontSize: 9}}>New!</Badge></Container>) : null}
						<Image source={{ uri: imageUrl }} alt={item.title} borderRadius="md" size="90px" />
						<Badge mt={1} _text={{fontSize: 10, color: "coolGray.600"}} bgColor="warmGray.200" _dark={{ bgColor: "coolGray.900", _text: {color: "warmGray.400"}}}>{item.language}</Badge>
						<AddToList item={item.id} libraryUrl={libraryUrl}/>
					</VStack>

					<VStack w="65%">
						<Text _dark={{ color: "warmGray.50" }} color="coolGray.800" bold fontSize={{base: "sm", lg: "md"}}>{item.title}</Text>
						{item.author ? <Text _dark={{ color: "warmGray.50" }} color="coolGray.800" fontSize="xs">{translate('grouped_work.by')} {item.author}</Text> : null }
						{item.format ? <Stack mt={1.5} direction="row" space={1} flexWrap="wrap">
							{formats.map((format, i) => {
								return <Badge colorScheme="secondary" mt={1} variant="outline" rounded="4px"
								              _text={{fontSize: 12}}>{format}</Badge>;
							})}
						</Stack>: null}
					</VStack>
				</HStack>
			</Pressable>
		)
	};

	getFormats = (data) => {
		let formats = [];

		data.map((item) => {
			let thisFormat = item.split("#");
			thisFormat = thisFormat[thisFormat.length - 1];
			formats.push(thisFormat);
		});

		formats = _.uniq(formats);
		return formats;
	}

	openItem = (id, libraryUrl) => {
		this.props.navigation.navigate("AccountScreenTab", {screen: 'GroupedWork', params: {item: id, libraryUrl: this.state.libraryUrl}});
	};

	_listEmpty = () => {
		return (
			<Center mt={5} mb={5}>
				<Text bold fontSize="lg">
					No search results.
				</Text>
			</Center>
		);
	};

	static contextType = userContext;

	render() {
		const {search} = this.state;
		const user = this.context.user;
		const location = this.context.location;
		const library = this.context.library;
		const { route } = this.props;
		const givenSearchId = route.params?.search ?? 0;

		if (this.state.isLoading) {
			return (loadingSpinner());
		}

		//console.log(this.state.search);

		return (
			<ScrollView>
				<Box safeArea={2}>
					<FlatList
						data={this.state.search}
						renderItem={({ item }) => this.renderItem(item, this.state.libraryUrl)}
						keyExtractor={(item) => item.id}
					/>
				</Box>
			</ScrollView>
		);

	}
}