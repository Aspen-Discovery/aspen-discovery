import React, {Component} from "react";
import {SafeAreaView} from 'react-native';
import {Badge, Box, Center, FlatList, Image, Pressable, Text, HStack, VStack, ScrollView} from "native-base";
import moment from "moment";

// custom components and helper files
import {loadingSpinner} from "../../../components/loadingSpinner";
import CreateList from "./CreateList";
import {getHolds, getLists} from "../../../util/loadPatron";
import {userContext} from "../../../context/user";
import {translate} from "../../../translations/translations";
import {GLOBALS} from "../../../util/globals";

export default class MyLists extends Component {
	constructor() {
		super();
		this.state = {
			isLoading: true,
			hasError: false,
			error: null,
			libraryUrl: '',
			lists: []
		};
		this._isMounted = false;
	}

	_fetchLists = async () => {
		const { route } = this.props;
		const libraryUrl = this.context.library.baseUrl;

		this._isMounted && await getLists(libraryUrl).then(response =>
			{
				this.setState({
					lists: response
				})

			}
		);
	}

	componentDidMount = async () => {
		this._isMounted = true;

		this._isMounted && await this._fetchLists().then(r => {
			this.setState({
				isLoading: false
			})
		});
	};

	componentWillUnmount() {
		this._isMounted = false;
	}

	// renders the items on the screen
	renderList = (item, libraryUrl) => {
		let lastUpdated = moment.unix(item.dateUpdated);
		lastUpdated = moment(lastUpdated).format("MMM D, YYYY");
		let privacy = translate('general.private');
		if(item.public === 1) {
			privacy = translate('general.public');
		}
		if(item.id !== "recommendations") {
			return (
				<Pressable onPress={() => {this.openList(item.id, item, libraryUrl)}} borderBottomWidth="1" _dark={{ borderColor: "gray.600" }} borderColor="coolGray.200" pl="1" pr="1" py="2">
					<HStack space={3} justifyContent="flex-start">
						<VStack space={1}>
							<Image source={{uri: item.cover}} alt={item.title} size="lg" resizeMode="contain" />
							<Badge mt={1}>{privacy}</Badge>
						</VStack>
						<VStack space={1} justifyContent="space-between" maxW="80%">
							<Box>
								<Text bold fontSize="md">{item.title}</Text>
								{item.description ? (<Text fontSize="xs" mb={2}>{item.description}</Text>) : null}
								<Text fontSize="9px" italic>{translate('general.last_updated_on', {date: lastUpdated})}</Text>
								<Text fontSize="9px" italic>{translate('lists.num_items_on_list', {num: item.numTitles})}</Text>
							</Box>
						</VStack>
					</HStack>
				</Pressable>
			);
		}
	};

	openList = (id, item, libraryUrl) => {
		this.props.navigation.navigate("AccountScreenTab", {screen: 'List', params: { id: id, details: item, title: item.title, libraryUrl: libraryUrl }});
	};

	_listEmptyComponent = () => {
		return (
			<Center mt={5} mb={5}>
				<Text bold fontSize="lg">
					{translate('lists.no_lists_yet')}
				</Text>
			</Center>
		);
	};


	static contextType = userContext;

	render() {
		const {lists} = this.state;
		const user = this.context.user;
		const location = this.context.location;
		const library = this.context.library;

		if (this.state.isLoading) {
			return (loadingSpinner());
		}

		return (
			<SafeAreaView style={{flex: 1}}>
			<Box safeArea={2} t={10} pb={10}>
				<CreateList libraryUrl={library.baseUrl} _fetchLists={this._fetchLists}/>
				<FlatList
					data={lists}
					ListEmptyComponent={this._listEmptyComponent()}
					renderItem={({item}) => this.renderList(item, library.baseUrl)}
					keyExtractor={(item) => item.id}
				/>
			</Box>
			</SafeAreaView>
		);

	}
}