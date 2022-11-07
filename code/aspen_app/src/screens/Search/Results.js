import * as React from 'react';
import {Badge, Box, Button, Center, Container, FlatList, Heading, HStack, Icon, Image, Pressable, Stack, Text, VStack} from 'native-base';
import {SafeAreaView, ScrollView} from 'react-native';
import {CommonActions} from '@react-navigation/native';
import _ from 'lodash';
import {MaterialIcons} from '@expo/vector-icons';

// custom components and helper files
import {translate} from '../../translations/translations';
import {loadingSpinner} from '../../components/loadingSpinner';
import {loadError} from '../../components/loadError';
import {getSearchResults, resetSearchGlobals, SEARCH, searchResults} from '../../util/search';
import {getLists} from '../../util/loadPatron';
import {AddToList} from './AddToList';
import {userContext} from '../../context/user';
import {LIBRARY} from '../../util/loadLibrary';

window.addEventListener = x => x;
window.removeEventListener = x => x;

export default class Results extends React.Component {
	static contextType = userContext;

	constructor(props) {
		super(props);
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
			lastListUsed: 0,
			scrollPosition: 0,
			facetSet: [],
			categorySelected: null,
			sortList: [],
			appliedSort: 'relevance',
			filters: [],
			query: this.props.route.params?.term ?? '',
			totalResults: 0,
			paramsToAdd: this.props.route.params?.pendingParams ?? [],
			lookfor: this.props.route.params?.term ?? '%20',
			lookfor_clean: '',
			resetSearch: false,
			curPage: 0,
			totalPages: 0,
		};
		this._isMounted = false;
		this.lastListUsed = 0;
		this.solrScope = null;
		this.locRef = React.createRef({});
		this.updateLastListUsed = this.updateLastListUsed.bind(this);
	}

	componentDidMount = async () => {
		this._isMounted = true;

		const {lookfor} = this.state;
		this.setState({
			lookfor_clean: lookfor.replace(/" "/g, '%20'),
		});

		const {navigation} = this.props;
		const routes = navigation.getState()?.routes;
		const prevRoute = routes[routes.length - 2];
		console.log(prevRoute['name']);
		if (prevRoute['name'] === 'SearchScreen') {
			resetSearchGlobals();
		}

		if (this._isMounted) {
			await getLists(this.context.library.baseUrl);
			this._getLastListUsed();
			if (LIBRARY.version >= '22.11.00') {
				await this.startSearch(false, true);
				//this.getCurrentSort();
			} else {
				await this._fetchResults();
			}
		}
	};

	getSnapshotBeforeUpdate(prevProps, prevState) {
		// Are we adding new items to the list?
		// Capture the scroll position, so we can adjust scroll later.
		if (prevState.lastListUsed !== this.state.lastListUsed) {
			const page = this.locRef.current;
			return page.scrollHeight - page.scrollTop;
		}
		return null;
	}

	async componentDidUpdate(prevProps, prevState, snapshot) {
		if (prevProps.route.params?.pendingParams !==
				this.props.route.params?.pendingParams) {
			if (LIBRARY.version >= '22.11.00') {
				this.setState({
					isLoading: true,
				});
				console.log('Starting a new search...');
				this.setState({
					data: [],
					totalResults: 0,
					curPage: 0,
					totalPages: 0,
					page: 0,
					endOfResults: false,
					dataMessage: null,
					hasError: false,
					resetSearch: true,
				});
				await this.startSearch(true, false);
			}
		}
	}

	componentWillUnmount() {
		this._isMounted = false;
	}

	_getLastListUsed = () => {
		if (this.context.user) {
			const user = this.context.user;
			this.lastListUsed = user.lastListUsed;
		}
	};

	updateLastListUsed = (id) => {
		this.setState({
			isLoading: true,
		});

		this.lastListUsed = id;

		this.setState({
			isLoading: false,
		});
	};

	getCurrentSort = () => {
		return _.filter(SEARCH.sortList, 'selected');
	};

	// search function for discovery 22.11.x or newer
	startSearch = async (isUpdated = false, freshSearch = true) => {
		const lookfor_clean = this.state.lookfor_clean;
		let page = this.state.page;
		if (isUpdated) {
			page = 1;
		}

		if (freshSearch) {
			SEARCH.sortMethod = 'relevance';
		}
		const paramsToAdd = this.props.route.params?.pendingParams ?? '';
		await getSearchResults(lookfor_clean, 100, page, this.context.library.baseUrl, paramsToAdd).then(response => {
			const data = response.data;
			if (data.success) {
				if (data['count'] > 0) {
					if (data['page_current'] <= data['page_total']) {
						this.setState((prevState, nextProps) => ({
							data:
									page === 1
											?
											Array.from(data['items']) :
											[...this.state.data, ...data['items']],
							refreshing: false,
							isLoading: false,
							isLoadingMore: false,
							totalResults: data['totalResults'],
							curPage: data['page_current'],
							totalPages: data['page_total'],
							resetSearch: false,
						}));
					} else if (data['page_current'] === data['page_total']) {
						this.setState((prevState, nextProps) => ({
							data:
									page === 1
											?
											Array.from(data['items']) :
											[...this.state.data, ...data['items']],
							refreshing: false,
							isLoading: false,
							isLoadingMore: false,
							totalResults: data['totalResults'],
							curPage: data['page_current'],
							totalPages: data['page_total'],
							dataMessage: data['message'] ?? translate('error.message'),
							endOfResults: true,
							resetSearch: false,
						}));
					} else {
						this.setState({
							isLoading: false,
							isLoadingMore: false,
							refreshing: false,
							dataMessage: data['message'] ?? translate('error.message'),
							endOfResults: true,
							resetSearch: false,
						});
					}
				} else {
					/* No search results were found */
					this.setState({
						hasError: true,
						error: data['message'],
						isLoading: false,
						isLoadingMore: false,
						refreshing: false,
						dataMessage: data['message'],
						resetSearch: false,
					});
				}
			} else {
				if (data.error) {
					this.setState({
						isLoading: false,
						hasError: true,
						error: data.error.message ?? 'Unknown error',
						resetSearch: false,
					});
				} else {
					this.setState({
						isLoading: false,
						isLoadingMore: false,
						refreshing: false,
						dataMessage: data.message ?? translate('error.message'),
						endOfResults: true,
						resetSearch: false,
					});
				}
			}
		});
	};

	// search function for discovery 22.10.x or earlier
	_fetchResults = async () => {
		const {page} = this.state;
		const {route} = this.props;
		const givenSearch = route.params?.term ?? '%20';
		const libraryUrl = this.context.library.baseUrl;
		const searchTerm = givenSearch.replace(/" "/g, '%20');

		await searchResults(searchTerm, 100, page, libraryUrl).then(response => {
			if (response.ok) {
				if (response.data.result.count > 0) {
					this.setState(() => ({
						data:
								page === 1
										? Array.from(response.data.result.items)
										: [...this.state.data, ...response.data.result.items],
						isLoading: false,
						isLoadingMore: false,
						refreshing: false,
					}));
				} else {
					if (page === 1 && response.data.result.count === 0) {
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
							dataMessage: response.data.result.message ??
									'Unknown error fetching results',
							endOfResults: true,
						});
					}
				}
			} else {
				this.setState({
					isLoading: false,
					isLoadingMore: false,
					refreshing: false,
					dataMessage: 'Unknown error fetching results',
					endOfResults: true,
				});
			}
		});
	};

	_handleLoadMore = () => {
		this.setState(
				(prevState, nextProps) => ({
					page: prevState.page + 1,
					isLoadingMore: true,
				}),
				() => {
					if (LIBRARY.version >= '22.11.00') {
						this.startSearch(false, false);
					} else {
						this._fetchResults();
					}
				},
		);
	};

	renderItem = (item, library, user, lastListUsed) => {
		return (
				<Pressable borderBottomWidth="1" _dark={{borderColor: 'gray.600'}}
									 borderColor="coolGray.200" pl="4" pr="5" py="2"
									 onPress={() => this.onPressItem(item.key, library,
											 item.title)}>
					<HStack space={3}>
						<VStack>
							<Image source={{uri: item.image}} alt={item.title}
										 borderRadius="md" size={{
								base: '90px',
								lg: '120px',
							}}/>
							<Badge mt={1} _text={{
								fontSize: 10,
								color: 'coolGray.600',
							}} bgColor="warmGray.200" _dark={{
								bgColor: 'coolGray.900',
								_text: {color: 'warmGray.400'},
							}}>{item.language}</Badge>
							<AddToList
									item={item.key}
									libraryUrl={library.baseUrl}
									lastListUsed={lastListUsed}
									updateLastListUsed={this.updateLastListUsed}
							/>

						</VStack>
						<VStack w="65%">
							<Text _dark={{color: 'warmGray.50'}} color="coolGray.800" bold
										fontSize={{
											base: 'md',
											lg: 'lg',
										}}>{item.title}</Text>
							{item.author ?
									<Text _dark={{color: 'warmGray.50'}}
												color="coolGray.800">{translate(
											'grouped_work.by')} {item.author}</Text> :
									null}
							<Stack mt={1.5} direction="row" space={1} flexWrap="wrap">
								{item.itemList.map((item, i) => {
									return <Badge colorScheme="secondary" mt={1} variant="outline"
																rounded="4px"
																_text={{fontSize: 12}}>{item.name}</Badge>;
								})}
							</Stack>
						</VStack>
					</HStack>
				</Pressable>
		);
	};

	// handles the on press action
	onPressItem = (item, library, title) => {
		const {
			navigation,
			route,
		} = this.props;
		const libraryUrl = library.baseUrl;
		navigation.dispatch(CommonActions.navigate({
			name: 'GroupedWork',
			params: {
				id: item,
				title: title,
				libraryUrl: libraryUrl,
			},
		}));
	};

	// this one shouldn't probably ever load with the catches in the render, but just in case
	_listEmptyComponent = () => {
		const {navigation, route} = this.props;
		return (
				<Center flex={1}>
					<Heading pt={5}>{translate('search.no_results')}</Heading>
					<Text bold w="75%" textAlign="center">{route.params?.term}</Text>
					<Button mt={3} onPress={() => navigation.dispatch(
							CommonActions.goBack())}>{translate(
							'search.new_search_button')}</Button>
				</Center>
		);
	};

	_listHeaderComponent = () => {
		const numResults = _.toInteger(this.state.totalResults);
		let resultsLabel = translate('filters.results', {num: numResults});
		if (numResults === 1) {
			resultsLabel = translate('filters.result', {num: numResults});
		}
		if (numResults > 0) {
			return (
					<Box _dark={{bg: 'coolGray.700'}} bgColor="coolGray.100" borderBottomWidth="1" _dark={{borderColor: 'gray.600'}}
							 borderColor="coolGray.200"><Container m={2}><Text>{resultsLabel}</Text></Container></Box>
			);
		} else {
			return null;
		}
	};

	_renderFooter = () => {
		if (!this.state.isLoadingMore) {
			return null;
		}
		return (loadingSpinner());
	};

	filterBar = () => {
		if (LIBRARY.version >= '22.11.00') {
			return (
					<Box safeArea={2} _dark={{bg: 'coolGray.700'}} bgColor="coolGray.100"
							 borderBottomWidth="1" _dark={{borderColor: 'gray.600'}}
							 borderColor="coolGray.200" flexWrap="nowrap">
						<ScrollView horizontal>
							{this.filterBarHeader()}
							{this.filterBarButton()}
						</ScrollView>
					</Box>
			);
		}
		return null;
	};

	filterBarHeader = () => {
		return (
				<Button
						size="sm"
						leftIcon={<Icon as={MaterialIcons} name="tune" size="sm"/>}
						variant="solid"
						mr={1}
						onPress={() => {
							this.props.navigation.push('modal', {
								screen: 'Filters',
								params: {
									navigation: this.props.navigation,
									route: this.props.route,
									pendingUpdates: [],
								},
							});
						}}
				>{translate('filters.title')}</Button>
		);
	};

	filterBarButton = () => {
		const appliedFilters = _.filter(SEARCH.availableFacets.data, 'hasApplied');
		const appliedFacets = SEARCH.appliedFilters;
		console.log(SEARCH.id);
		const navigation = this.props.navigation;
		const searchTerm = this.state.query;
		if (_.size(appliedFilters) > 0) {
			return (
					<Button.Group size="sm" space={1} vertical variant="outline">
						{_.map(appliedFacets, function(item, index, collection) {
							const cluster = _.filter(SEARCH.availableFacets.data, ['field', item[0]['field']]);
							let labels = '';
							_.forEach(item, function(value, key) {
								if (labels.length === 0) {
									labels = labels.concat(_.toString(value['display']));
								} else {
									labels = labels.concat(', ', _.toString(value['display']));
								}

							});
							const label = _.truncate(index + ': ' + labels);
							return <Button onPress={() => {
								navigation.push('modal', {
									screen: 'Facet',
									params: {
										navigation: navigation,
										key: item[0]['field'],
										term: searchTerm,
										title: cluster[0]['label'],
										facets: item[0]['facets'],
										pendingUpdates: [],
										extra: cluster[0],
									},
								});
							}}>{label}</Button>;
						})}
					</Button.Group>
			);
		} else {
			return this.filterBarDefaults();
		}
	};

	filterBarDefaults = () => {
		const defaults = SEARCH.defaultFacets;
		return (
				<Button.Group size="sm" space={1} vertical variant="outline">
					{defaults.map((obj, index) => {
						return <Button
								variant="outline"
								onPress={() => {
									this.props.navigation.push('modal', {
										screen: 'Facet',
										params: {
											navigation: this.props.navigation,
											key: obj['value'],
											term: this.state.query,
											title: obj['display'],
											facets: SEARCH.availableFacets.data[obj['display']].facets,
											pendingUpdates: [],
											extra: obj,
										},
									});
								}}
						>{obj.display}</Button>;
					})}
				</Button.Group>
		);
	};

	render() {
		const {navigation, route} = this.props;
		const user = this.context.user;
		const location = this.context.location;
		const library = this.context.library;

		//console.log(SEARCH.appliedFilters);
		const numResults = _.toInteger(this.state.totalResults);
		let resultsLabel = translate('filters.results', {num: numResults});
		if (numResults === 1) {
			resultsLabel = translate('filters.result', {num: numResults});
		}

		if (this.state.isLoading) {
			return (loadingSpinner());
		}

		if (this.state.hasError && !this.state.dataMessage) {
			return (loadError(this.state.error, ''));
		}

		if (this.state.hasError && this.state.dataMessage) {
			return (
					<Center flex={1}>
						<Heading pt={5}>{translate('search.no_results')}</Heading>
						<Text bold w="75%" textAlign="center">{route.params?.term}</Text>
						<Button mt={3} onPress={() => navigation.dispatch(
								CommonActions.goBack())}>{translate(
								'search.new_search_button')}</Button>
					</Center>
			);
		}

		return (
				<SafeAreaView style={{flex: 1}}>
					<Box ref={this.locRef} flex={1}>
						{this.filterBar()}
						<FlatList
								data={this.state.data}
								ListHeaderComponent={this._listHeaderComponent()}
								ListEmptyComponent={this._listEmptyComponent()}
								renderItem={({item}) => this.renderItem(item, library, user,
										this.lastListUsed)}
								keyExtractor={(item) => item.key}
								ListFooterComponent={this._renderFooter}
								onEndReached={!this.state.dataMessage ?
										this._handleLoadMore :
										null} // only try to load more if no message has been set
								onEndReachedThreshold={.5}
								initialNumToRender={25}
						/>
					</Box>
				</SafeAreaView>
		);
	}
}