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
	Icon,
	Pressable,
	Text,
	Container,
	HStack,
	VStack
} from "native-base";
import {MaterialIcons} from "@expo/vector-icons";
import moment from "moment";
import * as WebBrowser from 'expo-web-browser';

// custom components and helper files
import {translate} from '../../../translations/translations';
import {loadingSpinner} from "../../../components/loadingSpinner";
import {removeLinkedAccount, renewAllCheckouts} from "../../../util/accountActions";
import {getListTitles, removeTitlesFromList} from "../../../util/loadPatron";
import EditList from "./EditList";
import {ScrollView} from "react-native";
import {userContext} from "../../../context/user";

export default class MyList extends Component {
	constructor() {
		super();
		this.state = {
			isLoading: true,
			hasError: false,
			error: null,
			user: [],
			list: [],
			listDetails: [],
			id: null,
		};
	}

	loadList = async () => {
		const { route } = this.props;
		const givenListId = route.params?.list ?? 0;
		const libraryUrl = route.params?.libraryUrl ?? 0;

		await getListTitles(givenListId, libraryUrl).then(response => {
			this.setState({
				list: response,
				id: givenListId,
			})
		});
	}

	componentDidMount = async () => {
		const { route } = this.props;
		const givenList = route.params?.details ?? '';
		const libraryUrl = route.params?.libraryUrl ?? '';

		this.setState({
			isLoading: false,
			listDetails: givenList,
			libraryUrl: libraryUrl
		});

		await this.loadList();

		this.interval = setInterval(() => {
			this.loadList();
		}, 5000)

		return () => clearInterval(this.interval)

	};

	componentWillUnmount() {
		clearInterval(this.interval);
	}

	// renders the items on the screen
	renderItem = (item, libraryUrl) => {
		return (
			<Pressable borderBottomWidth="1" _dark={{ borderColor: "gray.600" }} borderColor="coolGray.200" pl="4" pr="5" py="2" onPress={() => this.openItem(item.id, library.baseUrl)}>
				<HStack space={3} justifyContent="flex-start" alignItems="flex-start">
					<Avatar source={{ uri: item.image }} alt={item.title} borderRadius="md" size={{base: "80px", lg: "120px"}} />
					<VStack w="65%">
						<Text _dark={{ color: "warmGray.50" }} color="coolGray.800" bold fontSize={{base: "sm", lg: "md"}}>{item.title}</Text>
						{item.author ? <Text _dark={{ color: "warmGray.50" }} color="coolGray.800" fontSize="xs">{translate('grouped_work.by')} {item.author}</Text> : null }
					</VStack>
					<IconButton icon={<Icon as={MaterialIcons} name="delete" />} _icon={{size: "xs", color: "gray.600"}} onPress={() => {
						removeTitlesFromList(this.state.id, item.id, libraryUrl)
					}} style={{ justifyContent: "flex-end", textAlign: "right", alignSelf: "top" }}/>
				</HStack>
			</Pressable>
		)
	};

	openItem = (id, libraryUrl) => {
		this.props.navigation.navigate("AccountScreenTab", {screen: 'ItemDetails', params: {item: id, libraryUrl: libraryUrl}});
	};

	_listEmpty = () => {
		return (
			<Center mt={5} mb={5}>
				<Text bold fontSize="lg">
					This list is empty. Why not add something to it?
				</Text>
			</Center>
		);
	};

	static contextType = userContext;

	render() {
		const {list} = this.state;
		const user = this.context.user;
		const location = this.context.location;
		const library = this.context.library;

		if (this.state.isLoading) {
			return (loadingSpinner());
		}

		return (
			<ScrollView style={{ marginBottom: 80 }}>
			<Box safeArea={2}>
				<EditList data={this.state.listDetails} listId={this.state.id} navigation={this.props.navigation} libraryUrl={library.baseUrl}/>
				<FlatList
					data={this.state.list}
					renderItem={({ item }) => this.renderItem(item, library.baseUrl)}
					keyExtractor={(item) => item.id}
				/>
			</Box>
			</ScrollView>
		);

	}
}