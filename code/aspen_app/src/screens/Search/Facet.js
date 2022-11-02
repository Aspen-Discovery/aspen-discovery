import React, {Component} from 'react';
import {Box, Button, Center, Checkbox, Input, View} from 'native-base';
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
import {addAppliedFilter, buildParamsForUrl, getPendingFacets, removeAppliedFilter, SEARCH} from '../../util/search';

export default class Facet extends Component {
	static contextType = userContext;

	constructor(props, context) {
		super(props, context);
		this.state = {
			isLoading: true,
			term: this.props.route.params?.term,
			data: this.props.route.params?.data ?? [],
			title: this.props.route.params?.extra['display'] ?? 'Filter',
			facets: this.props.route.params?.facets ?? [],
			numFacets: this.props.route.params?.extra['count'] ?? 0,
			category: this.props.route.params?.extra['field'] ?? [],
			applied: [],
			multiSelect: this.props.route.params?.extra['multiSelect'] ?? false,
			pendingFilters: [],
			filterByQuery: '',
			hasPendingChanges: false,
			showWarning: false,
			isUpdating: false,
			checkboxValues: [],
			oldCheckboxValues: [],
		};
		this._isMounted = false;
	}

	componentDidMount = async () => {
		this._isMounted = true;

		let multiSelect = this.props.route.params?.extra['multiSelect'];
		if (multiSelect === '1' || multiSelect === 1 || multiSelect === true || multiSelect === 'true') {
			this.setState({
				multiSelect: true,
			});
		} else {
			this.setState({
				multiSelect: false,
			});
		}

		this.setSelectedValues();
		this.setAppliedFacets();

		this.setState({
			isLoading: false,
		});
	};

	componentDidUpdate() {
		const {navigation} = this.props;

		// Use `setOptions` to update the button that we previously specified
		// Now the button includes an `onPress` handler to update the count
		navigation.setOptions({
			/* headerLeft: () => (
			 <UnsavedChangesBack updateSearch={this.updateSearch}/>
			 ), */
			headerRight: () => (
					<UnsavedChangesExit updateSearch={this.updateSearch}/>
			),
		});

	}

	componentWillUnmount() {
		this._isMounted = false;
	}

	filter(list) {
		const filterByQuery = this.state.filterByQuery;
		//todo: add method to use api endpoint to get more results to filter through by query
		return _.filter(list, function(facet) {
			return facet.display.indexOf(filterByQuery) > -1;
		});
	}

	searchBar = () => {
		//todo: add searchbar to >10 results when able to filter thru every facet properly
		if (this.state.numFacets > 200) {
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

	setAppliedFacets = () => {
		let appliedFacets = getPendingFacets(this.state.category);
		this.setState({
			appliedFacets: appliedFacets['facets'],
		});
	};

	setSelectedValues = () => {
		const facets = this.props.route.params?.facets;
		const applied = _.filter(facets, 'isApplied');
		let values = [];

		_.map(applied, function(item, index, collection) {
			values = _.concat(values, item.value);
		});

		this.setState({
			checkboxValues: values,
		});

		return values;
	};

	updateCheckboxValues = (group, values) => {
		const oldValues = this.state.checkboxValues;
		const toRemove = _.difference(oldValues, values);
		if (addAppliedFilter(group, values, true)) {
			this.setState({checkboxValues: values});
		}
		removeAppliedFilter(group, toRemove);
		this.setAppliedFacets();
		SEARCH.hasPendingChanges = true;
		buildParamsForUrl();
	};

	updateFilters = (group, value, allowMultiple, type = 'add') => {
		if (addAppliedFilter(group, value, allowMultiple)) {
			this.setAppliedFacets();
		}
		SEARCH.hasPendingChanges = true;
	};

	updateSearch = (resetFacetGroup = false, toFilters = false) => {
		const params = buildParamsForUrl();
		console.log('params >', params);
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

	clearSelections = () => {
		const {applied, category} = this.state;
		if (_.isEmpty(this.state.checkboxValues)) {
			removeAppliedFilter(category, applied);
		} else {
			removeAppliedFilter(category, this.state.checkboxValues);
		}

		this.setState({
			checkboxValues: [],
			appliedFacets: [],
			applied: [],
		});
	};

	actionButtons = () => {
		return (
				<Box safeArea={3} _light={{bg: 'coolGray.50'}} _dark={{bg: 'coolGray.700'}} shadow={1}>
					<Center>
						<Button.Group size="lg">
							<Button variant="unstyled"
											onPress={() => this.clearSelections()}>{translate('general.reset')}</Button>
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
								<Facet_Year category={category} updater={this.setAppliedFacets} data={facets}/>
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
								<Facet_Rating category={category} updater={this.setAppliedFacets} data={facets}/>
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
								<Facet_Slider category={category} data={facets} updater={this.updateFilters}/>
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
										defaultValue={this.state.checkboxValues}
										accessibilityLabel={translate('filters.filter_by')}
										onChange={values => this.updateCheckboxValues(category, values, true)}
								>
									{this.filter(facets).map((item, index, array) => (
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
																	applied={this.state.applied} updater={this.setAppliedFacets}/>
							</Box>
						</ScrollView>
						{this.actionButtons()}
					</View>
			);
		}
	}
}