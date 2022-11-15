import React, {Component} from "react";
import {SafeAreaView} from 'react-native';
import {
	Box,
	Button,
	Center,
	FlatList,
	Icon,
	Pressable,
	Text,
	HStack,
	VStack,
	Image
} from "native-base";
import {MaterialIcons} from "@expo/vector-icons";
import { CommonActions } from '@react-navigation/native';

// custom components and helper files
import {translate} from '../../../translations/translations';
import {loadingSpinner} from "../../../components/loadingSpinner";
import {getListTitles, removeTitlesFromList} from "../../../util/loadPatron";
import EditList from "./EditList";
import {ScrollView} from "react-native";
import {userContext} from "../../../context/user";
import {GLOBALS} from "../../../util/globals";

export default class MyList extends Component {
	constructor(props, context) {
		super(props, context);
		this.state = {
			isLoading: true,
			hasError: false,
			error: null,
			user: [],
			list: [],
			listDetails: [],
			id: null,
		};
		this._isMounted = false;
	}

	loadList = async () => {
		this.setState({
			isLoading: true,
		})
		const { route } = this.props;
		const givenListId = route.params?.id ?? 0;
		const libraryUrl = this.context.library.baseUrl;

		await getListTitles(givenListId, libraryUrl).then(response => {
			this.setState({
				list: response,
				id: givenListId,
				isLoading: false,
			})
		});
	}

	componentDidMount = async () => {
		this._isMounted = true;
		const { route } = this.props;
		const givenList = route.params?.details ?? '';

		this._isMounted && await this.loadList().then(res => {
			this.setState({
				isLoading: false,
				listDetails: givenList,
				libraryUrl: this.context.library.baseUrl
			});
		})

	};

	componentWillUnmount() {
		this._isMounted = false;
	}

	_updateRouteParam = (newTitle) => {
		const { navigation, route } = this.props;
		navigation.dispatch({
			...CommonActions.setParams({ title: newTitle }),
			source: route.key,
		});
	}

	// renders the items on the screen
	renderItem = (item, libraryUrl) => {
		return (
			<Pressable borderBottomWidth="1" _dark={{ borderColor: "gray.600" }} borderColor="coolGray.200" pl="4" pr="5" py="2" onPress={() => this.openItem(item.id, this.state.libraryUrl)}>
				<HStack space={3} justifyContent="flex-start" alignItems="flex-start">
					<VStack w="25%">
						<Image source={{ uri: item.image }} alt={item.title} borderRadius="md" size="90px" />
						<Button onPress={async () => {
							await removeTitlesFromList(this.state.id, item.id, this.state.libraryUrl);
							await this.loadList()
						}} colorScheme="danger" leftIcon={<Icon as={MaterialIcons} name="delete" size="xs"/>} size="sm" variant="ghost">{translate('general.delete')}</Button>
					</VStack>
					<VStack w="65%">
						<Text _dark={{ color: "warmGray.50" }} color="coolGray.800" bold fontSize={{base: "sm", lg: "md"}}>{item.title}</Text>
						{item.author ? <Text _dark={{ color: "warmGray.50" }} color="coolGray.800" fontSize="xs">{translate('grouped_work.by')} {item.author}</Text> : null }
					</VStack>
				</HStack>
			</Pressable>
		)
	};

	openItem = (id, libraryUrl) => {
		this.props.navigation.navigate("AccountScreenTab", {screen: 'GroupedWork', params: {id: id, libraryUrl: this.state.libraryUrl}});
	};

	_listEmpty = () => {
		return (
			<Center mt={5} mb={5}>
				<Text bold fontSize="lg">
					{translate('lists.empty')}
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
		const { route } = this.props;
		const givenListId = route.params?.id ?? 0;

		if (this.state.isLoading) {
			return (loadingSpinner());
		}

		return (
			<SafeAreaView style={{flex: 1}}>
			<Box safeArea={2} pb={10}>
				<EditList data={this.state.listDetails} listId={givenListId} navigation={this.props.navigation} libraryUrl={this.state.libraryUrl} loadList={this.loadList} _updateRouteParam={this._updateRouteParam}/>
				<FlatList
					data={this.state.list}
					renderItem={({ item }) => this.renderItem(item, this.state.libraryUrl)}
					keyExtractor={(item) => item.id}
				/>
			</Box>
			</SafeAreaView>
		);

	}
}