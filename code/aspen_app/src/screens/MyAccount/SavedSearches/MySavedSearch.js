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
import { useIsFocused } from '@react-navigation/native';

// custom components and helper files
import {translate} from '../../../translations/translations';
import {loadingSpinner} from "../../../components/loadingSpinner";
import {removeLinkedAccount, renewAllCheckouts} from "../../../util/accountActions";
import {getListTitles, getSavedSearchTitles, removeTitlesFromList} from "../../../util/loadPatron";
import {ScrollView} from "react-native";
import {userContext} from "../../../context/user";
import _ from "lodash";
import AddToList from "../../Search/AddToList";

class MySavedSearch extends React.PureComponent {
	constructor(props, context) {
		super(props, context);
		this.state = {
			isLoading: true,
			hasError: false,
			error: null,
			user: [],
			search: [],
			searchDetails: [],
			id: 0,
			//reloadSearch: this.loadSearch.bind(this)
		};
	}

/*	static getDerivedStateFromProps(nextProps, prevState) {
		if(nextProps.route.params.search !== prevState.id){
			//Change in props
			return{
				model:prevState.reloadSearch(nextProps.model)
			};
		}
		return null; // No change to state
	}*/

	loadSearch = async () => {
		this.setState({
			isLoading: true,
		})

		const { route } = this.props;
		const givenSearchId = route.params?.search ?? 0;
		const libraryUrl = route.params?.libraryUrl ?? this.context.library.baseUrl;

		await getSavedSearchTitles(givenSearchId, libraryUrl).then(response => {
			this.setState({
				search: response,
				id: givenSearchId,
				libraryUrl: libraryUrl,
			})
		});
	}

	componentDidMount = async () => {
		const { route } = this.props;
		console.log(route.params);
		const givenSearchId = route.params?.search ?? 0;
		const givenSearch = route.params?.details ?? '';
		const givenResults = route.params?.results ?? '';
		const libraryUrl = route.params?.libraryUrl ?? this.context.library.baseUrl;

		//console.log(givenSearchId);

		await this.loadSearch();

		this.setState({
			isLoading: false,
		})

/*		this.interval = setInterval(() => {
			//console.log(this.state);
			this.loadSearch();
		}, 100000)

		return () => clearInterval(this.interval)*/

	};

	componentWillUnmount() {
		//clearInterval(this.interval);
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
			<Pressable borderBottomWidth="1" _dark={{ borderColor: "gray.600" }} borderColor="coolGray.200" pl="4" pr="5" py="2" onPress={() => this.openItem(item.id, libraryUrl)}>
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
		this.props.navigation.navigate("AccountScreenTab", {screen: 'GroupedWork', params: {item: id, libraryUrl: libraryUrl}});
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
		const {isFocused} = this.props;
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
				<Box safeArea={2} isFocused={isFocused}>
					<FlatList
						data={this.state.search}
						renderItem={({ item }) => this.renderItem(item, library.baseUrl)}
						keyExtractor={(item) => item.id}
					/>
				</Box>
			</ScrollView>
		);

	}
}

export default function SavedSearchScreen(props) {
	const isFocused = useIsFocused();
	return <MySavedSearch {...props} isFocused={isFocused} />;
}