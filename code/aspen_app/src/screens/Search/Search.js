import React, {Component} from "react";
import {Box, Button, Center, FlatList, FormControl, Input, Text, HStack, Icon} from "native-base";
import AsyncStorage from '@react-native-async-storage/async-storage';
import {MaterialIcons} from "@expo/vector-icons";
import _ from "lodash";

// custom components and helper files
import {translate} from '../../translations/translations';
import {loadingSpinner} from "../../components/loadingSpinner";
import {userContext} from "../../context/user";
import {ScrollView} from "react-native";

export default class Search extends Component {
	constructor() {
		super();
		this.state = {
			isLoading: true,
			searchTerm: "",
			showRecentSearches: false,
			recentSearches: {},
		};
		this._isMounted = false;
		this._getRecentSearches = this._getRecentSearches.bind(this);
	}

	componentDidMount = async () => {
		this._isMounted = true;

		this.setState({
			isLoading: false,
		});

		// build a received notification storage for later
		this._isMounted && await this._getRecentSearches();

	};

	componentWillUnmount() {
		this._isMounted = false;
	}

	initiateSearch = async () => {
		const {searchTerm, libraryUrl} = this.state;
		const { navigation } = this.props;
		navigation.navigate("SearchResults", {
			term: searchTerm,
			libraryUrl: this.context.library.baseUrl,
		});
		await this._addRecentSearch(searchTerm).then(res => {
			this.clearText();
		});
	};

	renderItem = (item, libraryUrl) => {
		const { navigation } = this.props;
		return (
			<Button
				mb={3}
				onPress={() =>
					navigation.navigate("SearchResults", {
						term: item.searchTerm,
						libraryUrl: this.context.library.baseUrl,
					})
				}
			>
				{item.label}
			</Button>
		);
	};

	_recentSearchItem = (search) => {
		const { navigation } = this.props;
		return (
			<HStack space={3} alignItems="center" justifyContent="space-between" pb={2}>
				<Button
					size="sm"
					onPress={() =>
						navigation.navigate("SearchResults", {
							term: search,
							libraryUrl: this.context.library.baseUrl,
						})
					}
				>
					{search}
				</Button>
				<Button variant="ghost" onPress={() => this._removeRecentSearch(search)} startIcon={<Icon as={MaterialIcons} name="close" size="sm" mr={-1} mt={.5}/>}>Remove</Button>
			</HStack>
		);
	}

	_recentSearchFooter = () => {
		return(
			<Button onPress={() => this._clearRecentSearches()}>Remove all</Button>
		)
	}

	clearText = () => {
		this.setState({searchTerm: ""});
	};

	_getRecentSearches = async () => {
		try {
			const recentSearches = await AsyncStorage.getItem('@recentSearches');
			this.setState({
				recentSearches: JSON.parse(recentSearches),
			})
			return recentSearches != null ? JSON.parse(recentSearches) : null;
		} catch (e) {
			console.log(e);
		}
	}

	_createRecentSearches = async (searchTerm) => {
		try {
			let searches = [];
			let search = {
				[searchTerm]: searchTerm,
			}
			searches.push(searchTerm);
			await AsyncStorage.setItem('@recentSearches', JSON.stringify(searches));
		} catch (e) {
			console.log(e);
		}
	}

	_addRecentSearch = async (searchTerm) => {
		let storage = await this._getRecentSearches().then(async response => {
			if (response) {
				console.log(response);
				let search = {
					[searchTerm]: searchTerm,
				};
				response.push(searchTerm)
				try {
					await AsyncStorage.setItem('@recentSearches', JSON.stringify(response));
				} catch (e) {
					console.log(e);
				}
			} else {
				await this._createRecentSearches(searchTerm);
			}
		});
	}

	_removeRecentSearch = async (needle) => {
		let storage = await this._getRecentSearches().then(async response => {
			if (response) {
				let haystack = response;
				if(haystack.includes(needle)) {
					_.pull(haystack, needle);
					try {
						await AsyncStorage.setItem('@recentSearches', JSON.stringify(haystack));
					} catch (e) {
						console.log(e);
					}
				}
			}
		});

		await this._getRecentSearches();
	}

	_clearRecentSearches = async () => {
		await AsyncStorage.removeItem('@recentSearches');
		await this._getRecentSearches();
	}

	static contextType = userContext;

	render() {
		const user = this.context.user;
		const location = this.context.location;
		const library = this.context.library;

		const quickSearchNum = _.size(library.quickSearches);
		const recentSearchNum = _.size(this.state.recentSearches);

		if (this.state.isLoading) {
			return (loadingSpinner());
		}

		return (
			<ScrollView>
				<Box safeArea={5}>
					<FormControl>
						<Input
							variant="filled"
							autoCapitalize="none"
							onChangeText={(searchTerm) => this.setState({searchTerm, libraryUrl: library.baseUrl})}
							status="info"
							placeholder={translate('search.title')}
							clearButtonMode="always"
							onSubmitEditing={this.initiateSearch}
							value={this.state.searchTerm}
							size="xl"
						/>
					</FormControl>

					{quickSearchNum > 0 ?
					(
					<Box>
						<Center>
							<Text mt={8} mb={2} fontSize="xl" bold>
								{translate('search.quick_search_title')}
							</Text>
						</Center>
						<FlatList
							data={_.sortBy(library.quickSearches, ['weight', 'label'])}
							renderItem={({item}) => this.renderItem(item, library.baseUrl)}
						/>
					</Box>
					)
					: null }

					{this.state.showRecentSearches && recentSearchNum > 0 ?
						(
							<Box>
								<Center>
									<Text mt={8} mb={2} fontSize="xl" bold>
										Recent Searches
									</Text>
								</Center>
								<FlatList
									data={this.state.recentSearches}
									renderItem={({item}) => this._recentSearchItem(item)}
									ListFooterComponent={this._recentSearchFooter}
								/>
							</Box>
						)
						: null }
				</Box>
			</ScrollView>
		);
	}
}