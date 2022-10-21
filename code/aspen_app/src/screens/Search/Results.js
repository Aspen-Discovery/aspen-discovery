import * as React from "react";
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
	Pressable,
	Image,
	Icon
} from "native-base";
import { CommonActions } from '@react-navigation/native';
import _ from "lodash";
import {MaterialIcons} from "@expo/vector-icons";

// custom components and helper files
import { translate } from '../../translations/translations';
import { loadingSpinner } from "../../components/loadingSpinner";
import { loadError } from "../../components/loadError";
import { searchResults } from "../../util/search";
import {getLists} from "../../util/loadPatron";
import {AddToList} from "./AddToList";
import {userContext} from "../../context/user";
import {GLOBALS} from "../../util/globals";

window.addEventListener = x => x
window.removeEventListener = x => x

export default class Results extends React.Component {
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
			discoveryVersion: this.props.route.params?.discoveryVersion ?? "22.10.00",
			facetSet: [],
			categorySelected: null,
			sortList: [],
			appliedSort: 'relevance',
			filters: [],
			query: this.props.route.params?.term ?? "",
			pendingFilters: this.props.route.params?.pendingFilters ?? [],
			totalResults: 0,
		};
		//this._getLastListUsed();
		GLOBALS.pendingSearchFilters = [];
		this._facetsNum = 0;
		this._facets = [];
		this._sortOptions = [];
		this._allOptions = [];
		this._isMounted = false;
		this.lastListUsed = 0;
		this.solrScope = null;
		this.locRef = React.createRef({});
		this.updateLastListUsed = this.updateLastListUsed.bind(this);
	}

	componentDidMount = async () => {
		this._isMounted = true;
		//const level      = this.props.navigation.state.params.level;
		//const format     = this.props.navigation.state.params.format;
		//const searchType = this.props.navigation.state.params.searchType;
		const { navigation, route } = this.props;
		const libraryUrl = this.context.library.baseUrl;

		this._isMounted && await getLists(libraryUrl);
		this._getLastListUsed();
		this._isMounted && await this._fetchResults();

		this.getCurrentSort();
		//this.formatFilters();
	};


	getSnapshotBeforeUpdate(prevProps, prevState) {
		// Are we adding new items to the list?
		// Capture the scroll position so we can adjust scroll later.
		if (prevState.lastListUsed !== this.state.lastListUsed) {
			const page = this.locRef.current;
			return page.scrollHeight - page.scrollTop;
		}
		return null;
	}

	async componentDidUpdate(prevProps, prevState, snapshot) {
		// If we have a snapshot value, we've just added new items.
		// Adjust scroll so these new items don't push the old ones out of view.
		// (snapshot here is the value returned from getSnapshotBeforeUpdate)
		if (snapshot !== null) {
			const page = this.locRef.current;
			page.scrollTop = page.scrollHeight - snapshot;
		}

		if (prevProps.route.params?.pendingParams !== this.props.route.params?.pendingParams) {
			this.setState({
				isLoading: true,
			})
			this._isMounted && await this._fetchResults();
		}
	}

	componentWillUnmount() {
		this._isMounted = false;
	}

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

	getCurrentSort = () => {
		const sortList = this.state.sortList;
		const appliedSort = this.state.appliedSort;

		const sort = _.get(sortList, appliedSort);

		this.setState({
			appliedSort: sort.desc,
		})
	}

	_fetchResults = async () => {
	    const { page } = this.state;
		const { navigation, route } = this.props;
		const givenSearch = route.params?.term ?? '%20';
		const libraryUrl = this.context.library.baseUrl;
        const searchTerm = givenSearch.replace(/" "/g, "%20");
		const pendingFilters = route.params?.pendingFilters ?? [];
		const pendingParams = route.params?.pendingParams ?? [];

        await searchResults(searchTerm, 100, page, "http://aspen.local:8888/", pendingParams).then(response => {
            if(response) {
				const searchResults = response['result'] ?? [];
				const defaultFilters = response['filters'] ?? [];
                if(searchResults.count > 0) {
	                const filters = defaultFilters;
	                let filtersArr = [];
	                if(filters.length > 0) {
		                _.forEach(filters, function(value) {
			                let str = value.split("=").slice(1).join();
			                str = str.split(":");
			                let obj = {};
			                obj[str[0]] = _.trim(str[1], '%22');
			                filtersArr = _.concat(filtersArr, obj);
		                })
	                }

					GLOBALS.pendingSearchFilters = filtersArr;
					GLOBALS.availableFacetClusters = searchResults.facetSet;

					const availableFacets = setupFacetClusters(searchResults.facetSet ?? []);
	                this._facets = availableFacets['facets'];
					this._facetsNum = availableFacets['count'];

					const sortOptions = setupSortOptions(searchResults.sortList ?? [])
	                this._sortOptions = sortOptions['facets'];

					this._allOptions = _.concat(this._facets, this._sortOptions);
	                this._allOptions = _.orderBy(this._allOptions, 'key');

                    this.setState((prevState, nextProps) => ({
                        data:
                            page === 1
                                ? Array.from(searchResults.items)
                                : [...this.state.data, ...searchResults.items],
                        refreshing: false,
	                    facetSet: searchResults.facetSet,
	                    sortList: searchResults.sortList ?? [],
	                    appliedSort: searchResults.sortedBy ?? "",
	                    categorySelected: searchResults.categorySelected ?? "",
	                    totalResults: searchResults.totalResults,
	                    filters: filtersArr,
	                    isLoading: false,
	                    isLoadingMore: false,
                    }));
                } else {
	                if(page === 1 && searchResults.count === 0) {
                    /* No search results were found */
                        this.setState({
                            hasError: true,
                            error: searchResults.message,
                            isLoading: false,
                            isLoadingMore: false,
                            refreshing: false,
                            dataMessage: searchResults.message,
                        });
                    } else {
                        /* Tried to fetch next page, but end of results */
                        this.setState({
                            isLoading: false,
                            isLoadingMore: false,
                            refreshing: false,
                            dataMessage: searchResults.message ?? "Unknown error fetching results",
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
		return (
			<Pressable borderBottomWidth="1" _dark={{ borderColor: "gray.600" }} borderColor="coolGray.200" pl="4" pr="5" py="2" onPress={() => this.onPressItem(item.key, library, item.title)}>
				<HStack space={3}>
					<VStack>
						<Image source={{ uri: item.image }} alt={item.title} borderRadius="md" size={{base: "90px", lg: "120px"}} />
						<Badge mt={1} _text={{fontSize: 10, color: "coolGray.600"}} bgColor="warmGray.200" _dark={{ bgColor: "coolGray.900", _text: {color: "warmGray.400"}}}>{item.language}</Badge>
						<AddToList
							item={item.key}
							libraryUrl={library.baseUrl}
							lastListUsed={lastListUsed}
							updateLastListUsed={this.updateLastListUsed}
						/>

					</VStack>
					<VStack w="65%">
						<Text _dark={{ color: "warmGray.50" }} color="coolGray.800" bold fontSize={{base: "md", lg: "lg"}}>{item.title}</Text>
						{item.author ? <Text _dark={{ color: "warmGray.50" }} color="coolGray.800">{translate('grouped_work.by')} {item.author}</Text> : null }
						<Stack mt={1.5} direction="row" space={1} flexWrap="wrap">
							{item.itemList.map((item, i) => {
								return <Badge colorScheme="secondary" mt={1} variant="outline" rounded="4px" _text={{ fontSize: 12 }}>{item.name}</Badge>;
							})}
						</Stack>
					</VStack>
				</HStack>
			</Pressable>
		)
	}

	// handles the on press action
	onPressItem = (item, library, title) => {
		const { navigation, route } = this.props;
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
		const { navigation, route } = this.props;
		return (
            <Center flex={1}>
                <Heading pt={5}>{translate('search.no_results')}</Heading>
                <Text bold w="75%" textAlign="center">{route.params?.term}</Text>
                <Button mt={3} onPress={() => navigation.dispatch(CommonActions.goBack())}>{translate('search.new_search_button')}</Button>
            </Center>
		);
	};

	_renderFooter = () => {
	    if(!this.state.isLoadingMore) return null;
	    return ( loadingSpinner() );
	}

	filterBar = () => {
		const numResults = this.state.totalResults.toLocaleString();
		const filters = GLOBALS.pendingSearchFilters;
		const availableFacetClusters = GLOBALS.availableFacetClusters;

		let keys = {};
		_.forEach(availableFacetClusters, function(tempValue, tempKey) {
			const key = {[tempKey]: tempValue.label}
			keys = _.merge(keys, key);
		})

		let numGroupedKeysWithValue = 0;
		let groupedKeys = [];
		_.forEach(keys, function(tempValue, tempKey) {
			let key = _.filter(filters, tempKey);
			let keyCount = key.length;
			let groupByKey = {
				'label': tempValue,
				'key': tempKey,
				'num': keyCount,
				'facets': key,
			}
			groupedKeys = _.concat(groupedKeys, groupByKey);

			if(keyCount > 0) {
				numGroupedKeysWithValue++;
			}
		});

		if(numGroupedKeysWithValue === 0) {
			groupedKeys = [];
		}

		let resultsLabel = translate('filters.results', {num: numResults});
		if(this.state.totalResults === 1) {
			resultsLabel = translate('filters.result', {num: numResults});
		}

		if(this.state.discoveryVersion >= "22.10.00") {
			return (
				<Box safeArea={2} _dark={{ bg: 'coolGray.700' }} bgColor="coolGray.100" shadow={4}>
					<FlatList
						horizontal
						data={groupedKeys}
						ListHeaderComponent={this.filterBarHeader()}
						ListEmptyComponent={this.filterBarEmpty(this._facets)}
						renderItem={({ item }) => this.filterBarButton(item, availableFacetClusters)}
						keyExtractor={({ item }) => _.uniqueId(item + '_')}
					/>
					<Center>
						<Text mt={3} fontSize="lg">{resultsLabel}</Text>
					</Center>
				</Box>
			)
		}
		return null;
	}

	filterBarHeader = () => {
		let isLaunching = false;
		return(
			<Button
				size="sm"
				leftIcon={<Icon as={MaterialIcons} name="tune" size="sm" />}
				variant="solid"
				mr={1}
				isLoading={this.state.isLoading}
				onPress={() => {
					this.setState({isLoading: true})
					this.props.navigation.push('modal', {
						screen: 'Filters',
						params: {
							options: this._allOptions,
							navigation: this.props.navigation,
							term: this.state.query,
							pendingUpdates: [],
						}
					})

					this.setState({isLoading: false})
				}}
			>{translate('filters.title')}</Button>
		)
	}

	filterBarButton = (item) => {
		if(item.num > 0) {
			const categoryData = _.filter(this._allOptions, ['category', item.key]);
			const facets = item.facets;
			const facetClusterLabel = item.label;
			let facetLabel = "";
			let appliedFacets = [];
			_.map(facets, function(item, index, array) {
				let facet = _.join(_.values(item), '');
				facet = decodeURI(facet);
				facet = facet.replace(/%20/g,' ');
				appliedFacets = _.concat(appliedFacets, facet)
			})

			facetLabel = _.join(appliedFacets, ', ');
			const label = _.truncate(facetClusterLabel + ": " + facetLabel);
			return(
				<Button
					mr={1}
					size="sm"
					vertical
					variant="outline"
					onPress={() => {
						this.props.navigation.push('modal', {
							screen: 'Facet',
							params: {
								navigation: this.props.navigation,
								term: this.state.query,
								data: categoryData[0],
								pendingUpdates: [],
								title: categoryData[0].label,
							}
						})
					}}
				>{label}</Button>
			)
		} else {
			return null;
		}
	}

	filterBarEmpty = (availableFacetClusters) => {
		const filters = getLimitedObjects(availableFacetClusters, 5);

		return(
			<Button.Group size="sm" space={1} vertical variant="outline">
				{filters.map((item, index, array) => {
					const categoryData = _.filter(this._allOptions, ['category', item.category]);
					return <Button
						onPress={() => {
							this.props.navigation.push('modal', {
								screen: 'Facet',
								params: {
									navigation: this.props.navigation,
									term: this.state.query,
									data: categoryData[0],
									pendingUpdates: [],
									title: categoryData[0].label,
								}
							})
						}}
					>{item.label}</Button>
				})}
			</Button.Group>
		)
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
                    <Text bold w="75%" textAlign="center">{route.params?.term}</Text>
                    <Button mt={3} onPress={() => navigation.dispatch(CommonActions.goBack())}>{translate('search.new_search_button')}</Button>
                </Center>
            );
        }

		return (
			<Box ref={this.locRef} flex={1}>
				{this.filterBar()}
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

function getLimitedObjects(array, n) {
	let i = 0;
	let keys = [];

	_.map(array, function (item, index, array) {
		if(i < n) {
			i++;
			keys = _.concat(keys, item);
		}
	})

	return keys;
}

function setupSortOptions(payload) {
	if(_.isArrayLike(payload) || _.isObjectLike(payload)) {
		let sortList = [];
		let i = 0;

		const container = {
			key: 0,
			category: 'sort_by',
			label: 'Sort By',
			multiSelect: false,
			count: 0,
			hasApplied: false,
			list: [],
			applied: [],
		}
		sortList.push(container);

		_.map(payload, function(item, index, array) {
			sortList[0]['list'].push({
				key: i++,
				label: item.desc,
				numResults: 0,
				isApplied: item.selected,
				value: index,
			})

			if(item.selected) {
				sortList[0]['applied'].push({
					key: 0,
					label: item.desc,
					numResults: 0,
					isApplied: item.selected,
					value: index,
				})
			}
		})

		let foundApplied = _.has(sortList[0]['list'][0], 'isApplied')
		Object.assign(sortList[0], {count: i, hasApplied: foundApplied});

		// order facets by key
		sortList = _.orderBy(sortList, ['key']);

		return {
			'facets': sortList,
			'count': _.size(sortList),
		}
	} else {
		//make sure we return something
		return {
			'facets': [],
			'count': 0,
		}
	}
}

function setupFacetClusters(payload) {
	if(_.isArrayLike(payload) || _.isObjectLike(payload)) {
		let facetClusterKey = 1;
		let facetCluster = [];
		_.forEach(payload, function(value, key) {
			const facetList = value.list;
			let facetKey = 0;
			let i = 0;

			const container = {
				key: facetClusterKey++,
				category: key,
				label: value.label ?? "Unknown",
				multiSelect: value.multiSelect ?? false,
				hasApplied: false,
				count: 0,
				list: [],
				applied: [],
			}

			facetCluster.push(container);

			let cluster = facetCluster.find(cluster => cluster.category === key);
			_.map(facetList, function(item, index, collection) {
				cluster['list'].push({
					key: facetKey++,
					label: item.display ?? "Unknown",
					value: item.value,
					numResults: item.count,
					isApplied: item.isApplied,
				})

				if(item.isApplied) {
					cluster['applied'].push({
						key: i++,
						label: item.display ?? "Unknown",
						value: item.value,
						numResults: item.count,
						isApplied: item.isApplied,
					})
				}
			})

			let foundApplied = _.size(cluster['applied']) > 0;
			Object.assign(cluster, {count: facetKey, hasApplied: foundApplied});

		})

		// order facets by key
		facetCluster = _.orderBy(facetCluster, ['key']);

		return {
			'facets': facetCluster,
			'count': _.size(facetCluster),
		}

	} else {
		//make sure we return something
		return {
			'facets': [],
			'count': 0,
		}
	}
}
