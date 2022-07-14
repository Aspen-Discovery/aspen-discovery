import React, { Component } from "react";
import {
	Avatar,
	Box,
	FlatList,
	Badge,
	Image,
	Pressable,
	Stack,
	Text,
	HStack,
	VStack
} from "native-base";
import { CommonActions } from '@react-navigation/native';

// custom components and helper files
import { translate } from '../../translations/translations';
import { loadingSpinner } from "../../components/loadingSpinner";
import {getListTitles} from "../../util/loadPatron";
import {userContext} from "../../context/user";
import {ScrollView} from "react-native";
import AddToList from "./AddToList";
import {listofListSearchResults} from "../../util/search";

export default class SearchByList extends Component {
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

	componentDidMount = async () => {
		const { route } = this.props;
		const givenList = route.params?.category ?? '';
		const libraryUrl = route.params?.libraryUrl ?? '';

		this.setState({
			isLoading: false,
			listDetails: givenList,
			libraryUrl: libraryUrl
		});

		await this.loadList();
	};

	loadList = async () => {
		const { route } = this.props;
		const givenListId = route.params?.category ?? 0;
		const libraryUrl = route.params?.libraryUrl ?? 0;

		await listofListSearchResults(givenListId, 25, 1, libraryUrl).then(response => {
			this.setState({
				list: Object.values(response.items),
				id: response.id,
			})
		});
	}

	renderItem = (item, library, user) => {
		let recordType = "grouped_work";
		if(item.recordtype) {
			recordType = item.recordtype;
		}
		const imageUrl = library.baseUrl + "/bookcover.php?id=" + item.id + "&size=large&type=" + recordType;
		console.log(imageUrl);

		return (
			<Pressable borderBottomWidth="1" _dark={{ borderColor: "gray.600" }} borderColor="coolGray.200" pl="4" pr="5" py="2" onPress={() => this.openItem(item.id, library, item.recordtype, item.title)}>
				<HStack space={3} justifyContent="flex-start" alignItems="flex-start">
					<VStack>
						<Image source={{ uri: imageUrl }} alt={item.title_display} borderRadius="md" size={{base: "90px", lg: "120px"}} />
						<Badge mt={1} _text={{fontSize: 10}}>{item.language}</Badge>
						<AddToList item={item.id} libraryUrl={library.baseUrl} lastListUsed={user.lastListUsed}/>
					</VStack>
					<VStack w="65%">
						<Text _dark={{ color: "warmGray.50" }} color="coolGray.800" bold fontSize={{base: "sm", lg: "md"}}>{item.title_display}</Text>
						{item.author_display ? <Text _dark={{ color: "warmGray.50" }} color="coolGray.800" fontSize="xs">{translate('grouped_work.by')} {item.author_display}</Text> : null }
						{item.format ? <Stack mt={1.5} direction="row" space={1} flexWrap="wrap">
							{item.format.map((format, i) => {
								return <Badge colorScheme="secondary" mt={1} variant="outline" rounded="4px"
								              _text={{fontSize: 12}}>{format}</Badge>;
							})}
						</Stack>: null}
					</VStack>
				</HStack>
			</Pressable>
		)
	};

	// handles the on press action
	openItem = (item, library, recordtype, title) => {
		const { navigation, route } = this.props;
		const libraryUrl = library.baseUrl;

		console.log(item);
		if(recordtype === "list") {
			navigation.dispatch(CommonActions.navigate({
				name: 'ListResults',
				params: {
					category: item,
					categoryLabel: title,
					libraryUrl: libraryUrl,
				},
			}));
		} else {
			navigation.dispatch(CommonActions.navigate({
				name: 'GroupedWorkScreen',
				params: {
					item: item,
					libraryUrl: libraryUrl,
				},
			}));
		}
	};

	static contextType = userContext;

	render() {
		const {list} = this.state;
		const user = this.context.user;
		const location = this.context.location;
		const library = this.context.library;
		const { route } = this.props;
		const givenListId = route.params?.category ?? 0;
		const libraryUrl = route.params?.libraryUrl ?? null;

		if (this.state.isLoading) {
			return ( loadingSpinner() );
		}


		return (
			<ScrollView>
				<Box safeArea={2}>
					<FlatList
						data={list}
						renderItem={({ item }) => this.renderItem(item, library, user)}
						keyExtractor={(item) => item.id}
					/>
				</Box>
			</ScrollView>
		);
	}
}

