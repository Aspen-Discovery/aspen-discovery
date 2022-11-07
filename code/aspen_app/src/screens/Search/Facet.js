import React, {Component} from 'react';
import {Box, Button, Center, Checkbox, ChevronLeftIcon, Input, Pressable, View} from 'native-base';
import _ from 'lodash';

// custom components and helper files
import Facet_Checkbox from './Facets/Checkbox';
import Facet_RadioGroup from './Facets/RadioGroup';
import Facet_Slider from './Facets/Slider';
import Facet_Year from './Facets/Year';
import Facet_Rating from './Facets/Rating';
import {ScrollView} from 'react-native';
import {userContext} from '../../context/user';
import {loadingSpinner} from '../../components/loadingSpinner';
import {translate} from '../../translations/translations';
import {UnsavedChangesExit} from './UnsavedChanges';
import {addAppliedFilter, buildParamsForUrl, removeAppliedFilter, SEARCH} from '../../util/search';

export default class Facet extends Component {
	static contextType = userContext;

	constructor(props, context) {
		super(props, context);
		this.state = {
			isLoading: true,
			term: this.props.route.params?.term,
			data: this.props.route.params?.data ?? [],
			title: this.props.route.params?.extra['label'] ?? 'Filter',
			facets: this.props.route.params?.facets ?? [],
			numFacets: 0,
			category: this.props.route.params?.extra['field'] ?? '',
			applied: [],
			multiSelect: Boolean(this.props.route.params?.extra['multiSelect']),
			pendingFilters: SEARCH.pendingFilters,
			filterByQuery: '',
			hasPendingChanges: false,
			showWarning: false,
			isUpdating: false,
			resetOptions: false,
			values: [],
			pending: [],
			valuesDefault: [],
		};
		this._isMounted = false;
	}

	componentDidMount = async () => {
		this._isMounted = true;

		const data = _.filter(SEARCH.availableFacets.data, ['field', this.state.category]);
		if (data[0]) {
			this.setState({
				facets: data[0]['facets'],
				numFacets: _.size(data[0]['facets']),
			});
		}

		this.preselectValues();

		this.setState({
			isLoading: false,
		});
	};

	componentDidUpdate(prevProps, prevState) {
		const {navigation} = this.props;
		const routes = navigation.getState()?.routes;
		const prevRoute = routes[routes.length - 2];
		if (prevRoute) {
			navigation.setOptions({
				headerLeft: () => (
						<Pressable onPress={() => {
							this.updateGlobal();
							this.props.navigation.navigate('Filters', {
								pendingFilters: SEARCH.pendingFilters,
							});
						}}>
							<ChevronLeftIcon color="primary.baseContrast"/>
						</Pressable>
				),
				headerRight: () => (
						<UnsavedChangesExit updateSearch={this.updateSearch} discardChanges={this.discardChanges} updateGlobal={this.updateGlobal}/>
				),
			});
		} else {
			navigation.setOptions({
				headerLeft: () => (
						<Box></Box>
				),
				headerRight: () => (
						<UnsavedChangesExit updateSearch={this.updateSearch} discardChanges={this.discardChanges}/>
				),
			});
		}

	}

	componentWillUnmount() {
		this._isMounted = false;
	}

	filter(list, sorted = false) {
		const filterByQuery = this.state.filterByQuery;
		//todo: add method to use api endpoint to get more results to filter through by query
		if (sorted) {
			const sortedList = _.orderBy(list, ['isApplied', 'count', 'display'], ['desc', 'desc', 'asc']);
			return _.filter(sortedList, function(facet) {
				return facet.display.indexOf(filterByQuery) > -1;
			});
		}

		return _.filter(list, function(facet) {
			return facet.display.indexOf(filterByQuery) > -1;
		});
	}

	searchBar = () => {
		//todo: add searchbar to >10 results when able to filter thru every facet properly
		if (this.state.numFacets > 105) {
			const placeHolder = translate('search.title') + ' ' + this.state.title;
			return (
					<Box safeArea={5}>
						<Input
								name="filterSearchBar"
								onChangeText={(filterByQuery) => this.setState({filterByQuery})}
								size="lg"
								autoCorrect={false}
								variant="outline"
								returnKeyType="search"
								placeholder={placeHolder}
						/>
					</Box>
			);
		} else {
			return (
					<Box pb={5}></Box>
			);
		}
	};

	preselectValues = () => {
		let values = [];
		const {category, multiSelect} = this.state;
		const cluster = _.filter(SEARCH.pendingFilters, ['field', category]);
		_.map(cluster, function(item, index, collection) {
			let facets = item['facets'];
			if (_.size(facets) > 0) {
				_.forEach(facets, function(value, key) {
					console.log(multiSelect);
					if (multiSelect) {
						values = _.concat(values, value);
					} else {
						values = value;
					}
				});
			}
		});
		this.setState({
			values: values,
			valuesDefault: values,
		});
	};

	updateSearch = (resetFacetGroup = false, toFilters = false) => {
		const params = buildParamsForUrl();
		SEARCH.hasPendingChanges = false;
		const {navigation} = this.props;
		if (toFilters) {
			navigation.navigate('Filters', {
				term: SEARCH.term,
			});
		} else {
			navigation.navigate('SearchResults', {
				term: SEARCH.term,
				pendingParams: params,
			});
		}

	};

	updateLocalValues = (group, values) => {
		SEARCH.hasPendingChanges = true;
		this.updateGlobal(group, values);
		this.setState({
			values: values,
		});
	};

	updateGlobal = (group, values) => {
		const multiSelect = this.state.multiSelect;
		const prevSelections = this.state.values;
		addAppliedFilter(group, values, multiSelect);
		if (multiSelect) {
			const difference = _.difference(prevSelections, values);
			if (difference) {
				removeAppliedFilter(group, difference);
			}
		}
	};

	discardChanges = () => {
		SEARCH.hasPendingChanges = true;
		const {values, category, valuesDefault} = this.state;
		const difference = _.difference(values, valuesDefault);
		if (difference) {
			removeAppliedFilter(category, difference);
		}
		this.setState({
			values: [],
		});
	};

	resetCluster = () => {
		SEARCH.hasPendingChanges = true;
		const {values, category} = this.state;
		removeAppliedFilter(category, values);
		this.setState({
			values: [],
		});
	};

	actionButtons = () => {
		return (
				<Box safeArea={3} _light={{bg: 'coolGray.50'}} _dark={{bg: 'coolGray.700'}} shadow={1}>
					<Center>
						<Button.Group size="lg">
							<Button variant="unstyled"
											onPress={() => this.resetCluster()}>{translate('general.reset')}</Button>
							<Button isLoading={this.state.isUpdating} isLoadingText={translate('general.updating')}
											onPress={() => {
												this.updateSearch();
											}}>{translate('general.update')}</Button>
						</Button.Group>
					</Center>
				</Box>
		);
	};

	render() {
		const {facets, category, multiSelect} = this.state;

		if (this.state.isLoading) {
			return (loadingSpinner());
		}

		if (category === 'publishDate' || category === 'birthYear' || category === 'deathYear' || category === 'publishDateSort') {
			return (
					<View style={{flex: 1}}>
						<ScrollView>
							<Box safeArea={5}>
								<Facet_Year category={category} updater={this.updateLocalValues} data={facets}/>
							</Box>
						</ScrollView>
						{this.actionButtons()}
					</View>
			);
		} else if (category === 'rating_facet') {
			return (
					<View style={{flex: 1}}>
						<ScrollView>
							<Box safeArea={5}>
								<Facet_Rating category={category} updater={this.updateLocalValues} data={facets}/>
							</Box>
						</ScrollView>
						{this.actionButtons()}
					</View>
			);
		} else if (category === 'lexile_score' || category === 'accelerated_reader_point_value' || category === 'accelerated_reader_reading_level') {
			return (
					<View style={{flex: 1}}>
						<ScrollView>
							<Box safeArea={5}>
								<Facet_Slider category={category} data={facets} updater={this.updateLocalValues}/>
							</Box>
						</ScrollView>
						{this.actionButtons()}
					</View>
			);
		} else if (multiSelect) {
			return (
					<View style={{flex: 1}}>
						{this.searchBar()}
						<ScrollView>
							<Box safeAreaX={5}>
								<Checkbox.Group
										name={category}
										value={this.state.values}
										accessibilityLabel={translate('filters.filter_by')}
										onChange={values => this.updateLocalValues(category, values)}
								>
									{this.filter(facets, true).map((item, index, array) => (
											<Facet_Checkbox key={index} data={item}/>
									))}
								</Checkbox.Group>
							</Box>
						</ScrollView>
						{this.actionButtons()}
					</View>
			);
		} else {
			return (
					<View style={{flex: 1}}>
						{this.searchBar()}
						<ScrollView>
							<Box safeAreaX={5}>
								<Facet_RadioGroup data={facets} category={category} title={this.state.title}
																	applied={this.state.values} updater={this.updateLocalValues}/>
							</Box>
						</ScrollView>
						{this.actionButtons()}
					</View>
			);
		}
	}
}