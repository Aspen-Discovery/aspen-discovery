import React from "react";
import {SafeAreaView} from 'react-native';
import {
	Badge,
	Box,
	FlatList,
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
import {getSavedSearchTitles} from "../../../util/loadPatron";
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
		};
		this._isMounted = false;
	}

	loadSearch = async () => {
		this.setState({
			isLoading: true,
		})

		const { route } = this.props;
		const givenSearchId = route.params?.id ?? 0;
		const libraryUrl = this.context.library.baseUrl;

		this._isMounted && await getSavedSearchTitles(givenSearchId, libraryUrl).then(response => {
			this._isMounted && this.setState({
				search: response,
				id: givenSearchId,
				libraryUrl: libraryUrl,
				isLoading: false,
			})
		});
	}

	componentDidMount = async () => {
		this._isMounted = true;
		this._isMounted && await this.loadSearch();

		this.setState({
			isLoading: false,
		})
	};

	componentWillUnmount() {
		this._isMounted = false;
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
		this.props.navigation.navigate("AccountScreenTab", {screen: 'GroupedWork', params: {id: id, libraryUrl: libraryUrl}});
	};

	static contextType = userContext;

	render() {
		const {isFocused} = this.props;
		const library = this.context.library;

		if (this.state.isLoading) {
			return (loadingSpinner());
		}

		return (
			<SafeAreaView style={{flex: 1}}>
				<Box safeArea={2} isFocused={isFocused}>
					<FlatList
						data={this.state.search}
						renderItem={({ item }) => this.renderItem(item, library.baseUrl)}
						keyExtractor={(item) => item.id}
					/>
				</Box>
			</SafeAreaView>
		);

	}
}

export default function SavedSearchScreen(props) {
	const isFocused = useIsFocused();
	return <MySavedSearch {...props} isFocused={isFocused} />;
}