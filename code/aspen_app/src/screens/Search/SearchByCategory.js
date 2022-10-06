import React, { Component } from "react";
import {
	Center,
	Button,
	Box,
	Badge,
	Text,
	FlatList,
	Heading,
	Stack,
	HStack,
	VStack,
	Avatar,
	Pressable,
	Image,
	Icon
} from "native-base";
import { CommonActions } from '@react-navigation/native';
import {MaterialIcons} from "@expo/vector-icons";

// custom components and helper files
import { translate } from '../../translations/translations';
import { loadingSpinner } from "../../components/loadingSpinner";
import { loadError } from "../../components/loadError";
import {categorySearchResults, searchResults} from "../../util/search";
import _ from "lodash";
import {getLists, removeTitlesFromList} from "../../util/loadPatron";
import AddToList from "./AddToList";
import {userContext} from "../../context/user";

export default class SearchByCategory extends Component {
	constructor() {
		super();
		this.state = {
			isLoading: true,
			isLoadingMore: false,
			data: [],
			searchMessage: null,
			page: 1,
			hasError: false,
			error: null,
			refreshing: false,
			filtering: false,
			endOfResults: false,
			dataMessage: null,
			listLastUsed: 0,
		};
		this.lastListUsed = 0;
		this.updateLastListUsed = this.updateLastListUsed.bind(this);
	}

	componentDidMount = async () => {
		//const level      = this.props.navigation.state.params.level;
		//const format     = this.props.navigation.state.params.format;
		//const searchType = this.props.navigation.state.params.searchType;
		const { navigation, route } = this.props;
		const libraryUrl = this.context.library.baseUrl;

		await getLists(libraryUrl);
		this._getLastListUsed();
		await this._fetchResults();
	};

	_getLastListUsed = () => {
		if(this.context.user) {
			const user = this.context.user;
			this.lastListUsed = user.lastListUsed;
		}
	}

	updateLastListUsed = (id) => {
		this.setState({
			isLoading: true,
		})

		this.lastListUsed = id;

		this.setState({
			isLoading: false,
		})
	}

	_fetchResults = async () => {
		const { page } = this.state;
		const { navigation, route } = this.props;
		//console.log(route);
		const category = route.params?.id ?? '';
		const libraryUrl = this.context.library.baseUrl;

		await categorySearchResults(category, 25, page, libraryUrl).then(response => {
			if(response.ok) {
				let records = response.data.result.records;

				console.log(response);
				if(records.length > 0) {
					this.setState((prevState, nextProps) => ({
						data:
							page === 1
								? Array.from(response.data.result.records)
								: [...this.state.data, ...response.data.result.records],
						isLoading: false,
						isLoadingMore: false,
						refreshing: false
					}));
				} else {
					if(page === 1 && records.length === 0) {
						/* No search results were found */
						this.setState({
							hasError: true,
							error: response.data.result.message,
							isLoading: false,
							isLoadingMore: false,
							refreshing: false,
							dataMessage: response.data.result.message,
						});
					} else {
						/* Tried to fetch next page, but end of results */
						this.setState({
							isLoading: false,
							isLoadingMore: false,
							refreshing: false,
							dataMessage: response.data.result.message,
							endOfResults: true,
						});
					}
				}
			}
		})

	}

	_handleLoadMore = () => {
		this.setState(
			(prevState, nextProps) => ({
				page: prevState.page + 1,
				isLoadingMore: true
			}),
			() => {
				this._fetchResults();
			}
		)
	};

	renderItem = (item, library, user, lastListUsed) => {
		const imageUrl = library.baseUrl + "/bookcover.php?id=" + item.id + "&size=medium&type=grouped_work";
		return (
			<Pressable borderBottomWidth="1" _dark={{ borderColor: "gray.600" }} borderColor="coolGray.200" pl="4" pr="5" py="2" onPress={() => this.onPressItem(item.id, library)}>
				<HStack space={3}>
					<VStack>
						<Image source={{ uri: imageUrl }} alt={item.title_display} borderRadius="md" size={{base: "90px", lg: "120px"}} />
						<Badge mt={1} _text={{fontSize: 10, color: "coolGray.600"}} bgColor="warmGray.200" _dark={{ bgColor: "coolGray.900", _text: {color: "warmGray.400"}}}>{item.language}</Badge>
						<AddToList item={item.id} libraryUrl={library.baseUrl} lastListUsed={lastListUsed} updateLastListUsed={this.updateLastListUsed} />
					</VStack>

					<VStack w="65%">
						<Text _dark={{ color: "warmGray.50" }} color="coolGray.800" bold fontSize={{base: "md", lg: "lg"}}>{item.title_display}</Text>
						{item.author_display ? <Text _dark={{ color: "warmGray.50" }} color="coolGray.800">{translate('grouped_work.by')} {item.author_display}</Text> : null }
						<Stack mt={1.5} direction="row" space={1} flexWrap="wrap">
							{item.format.map((format, i) => {
								return <Badge colorScheme="secondary" mt={1} variant="outline" rounded="4px" _text={{ fontSize: 12 }}>{format}</Badge>;
							})}
						</Stack>
					</VStack>

				</HStack>
			</Pressable>
		)
	}

	// handles the on press action
	onPressItem = (item, library) => {
		const { navigation, route } = this.props;
		const libraryUrl = library.baseUrl;
		navigation.dispatch(CommonActions.navigate({
			name: 'GroupedWorkScreen',
			params: {
				id: item,
				libraryUrl: libraryUrl,
			},
		}));
	};

	// this one shouldn't probably ever load with the catches in the render, but just in case
	_listEmptyComponent = () => {
		const { navigation, route } = this.props;
		return (
			<Center flex={1}>
				<Heading pt={5}>{translate('search.no_results')}</Heading>
				<Text bold w="75%" textAlign="center">{route.params?.title}</Text>
				<Button mt={3} onPress={() => navigation.dispatch(CommonActions.goBack())}>{translate('search.new_search_button')}</Button>
			</Center>
		);
	};

	_renderFooter = () => {
		if(!this.state.isLoadingMore) return null;
		return ( loadingSpinner() );
	}

	static contextType = userContext;


	render() {
		const { navigation, route } = this.props;
		const user = this.context.user;
		const location = this.context.location;
		const library = this.context.library;

		if (this.state.isLoading) {
			return ( loadingSpinner() );
		}

		if (this.state.hasError && !this.state.dataMessage) {
			return ( loadError(this.state.error, this._fetchResults) );
		}

		if (this.state.hasError && this.state.dataMessage) {
			return (
				<Center flex={1}>
					<Heading pt={5}>{translate('search.no_results')}</Heading>
					<Text bold w="75%" textAlign="center">{route.params?.title}</Text>
					<Button mt={3} onPress={() => navigation.dispatch(CommonActions.goBack())}>{translate('search.new_search_button')}</Button>
				</Center>
			);
		}

		return (
			<Box>
				<FlatList
					data={this.state.data}
					ListEmptyComponent={this._listEmptyComponent()}
					renderItem={({ item }) => this.renderItem(item, library, user, this.lastListUsed)}
					keyExtractor={(item) => item.key}
					ListFooterComponent={this._renderFooter}
					onEndReached={!this.state.dataMessage ? this._handleLoadMore : null} // only try to load more if no message has been set
					onEndReachedThreshold={.5}
					initialNumToRender={25}
				/>
			</Box>
		);
	}
}

