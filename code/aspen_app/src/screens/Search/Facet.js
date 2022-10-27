import React, {Component} from "react";
import {Box, Button, Center, Checkbox, Input, View} from "native-base";
import _ from "lodash";

// custom components and helper files
import Facet_Checkbox from "./Facets/Checkbox";
import Facet_RadioGroup from "./Facets/RadioGroup";
import Facet_Slider from "./Facets/Slider";
import Facet_Year from "./Facets/Year";
import Facet_Rating from "./Facets/Rating";
import {ScrollView} from "react-native";
import {GLOBALS} from "../../util/globals";
import {userContext} from "../../context/user";
import {loadingSpinner} from "../../components/loadingSpinner";
import {translate} from "../../translations/translations";
import {UnsavedChangesBack, UnsavedChangesExit} from "./UnsavedChanges";

export default class Facet extends Component {
	constructor(props, context) {
		super(props, context);
		this.state = {
			isLoading: true,
			term: this.props.route.params?.term,
			data: this.props.route.params?.data ?? [],
			title: this.props.route.params?.data.label ?? "Filter",
			facets: this.props.route.params?.data.list ?? [],
			numFacets: this.props.route.params?.data.count ?? 0,
			category: this.props.route.params?.data.category ?? "",
			applied: this.props.route.params?.data.applied ?? [],
			multiSelect: this.props.route.params?.data.multiSelect ?? false,
			pendingFilters: [],
			filterByQuery: "",
			hasPendingChanges: false,
			showWarning: false,
			isUpdating: false,
			checkboxValues: [],
		};
		this._isMounted = false;
		this._allowMultiple = false;
	}

	 componentDidMount = async () => {
		 this._isMounted = true;

		 if (this.state.multiSelect) {
			 this.defaultCheckboxValues();
		 }

		 this.setState({
			 isLoading: false,
		 })
	 }

	componentDidUpdate() {
		const {navigation} = this.props;

		// Use `setOptions` to update the button that we previously specified
		// Now the button includes an `onPress` handler to update the count
		navigation.setOptions({
			headerLeft: () => (
				<UnsavedChangesBack updateSearch={this.updateSearch}/>
			),
			headerRight: () => (
				<UnsavedChangesExit updateSearch={this.updateSearch}/>
			)
		});


	}

	componentWillUnmount() {
		this._isMounted = false;
		this._showPendingAlert = false;
		GLOBALS.hasPendingChanges = false;
	}

	filter(list) {
		const filterByQuery = this.state.filterByQuery;
		//todo: add method to use api endpoint to get more results to filter through by query
		return _.filter(list, function (facet) {
			return facet.label.indexOf(filterByQuery) > -1
		})
	}

	searchBar = () => {
		//todo: add searchbar to >10 results when able to filter thru every facet properly
		if (this.state.numFacets > 200) {
			const placeHolder = translate('search.title') + " " + this.state.title;
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
			)
		} else {
			return(
				<Box pb={5}></Box>
			)
		}
	}

	defaultCheckboxValues = () => {
		const applied = this.state.applied;
		let values = [];
		_.map(applied, function(item, index, collection) {
			values = _.concat(values, item.value)
		});

		this.setState({
			checkboxValues: values,
		})

		return values;
	}

	updateCheckboxValues = (group, values, allowMultiple) => {
		this._allowMultiple = allowMultiple;
		if(_.isArray(values) || _.isObject(values)) {
			values.forEach(value => {
				this.setState((prevState) => ({
					...prevState,
					pendingFilters: {
						...prevState.pendingFilters,
						[group]: {
							...prevState.pendingFilters[group],
							[value]: value,
						}
					},
					hasPendingChanges: true,
				}))
			})
		} else {
			this.setState((prevState) => ({
				...prevState,
				pendingFilters: {
					...prevState.pendingFilters,
					[group]: values,
				},
				hasPendingChanges: true,
			}))
		}

		GLOBALS.hasPendingChanges = true;
	}

	updateFilters = (group, value, allowMultiple) => {
		const pending = value;
		this._allowMultiple = allowMultiple;
		if(allowMultiple) {
			this.setState((prevState) => ({
				...prevState,
				pendingFilters: {
					...prevState.pendingFilters,
					[group]: {
						...prevState.pendingFilters[group],
						[pending]: pending,
					},
				},
				hasPendingChanges: true,
			}))

		} else {
			this.setState((prevState) => ({
				...prevState,
				pendingFilters: {
					...prevState.pendingFilters,
					[group]: pending,
				},
				hasPendingChanges: true,
			}))
		}

		GLOBALS.hasPendingChanges = true;
	}

	updateSearch = (resetFacetGroup = false, toFilters = false) => {
		const pendingFilters = this.state.pendingFilters;
		const allowMultiple = this._allowMultiple;

		if (_.isObjectLike(pendingFilters) || _.isArrayLike(pendingFilters)) {
			_.forEach(pendingFilters, function (tempValue, tempKey) {
				if (_.find(GLOBALS.pendingSearchFilters, tempKey) && (tempKey !== "undefined" || tempValue !== "undefined")) {
					if (allowMultiple) {
						if (resetFacetGroup) {
							console.log("try to remove " + tempKey)
						} else {
							GLOBALS.pendingSearchFilters = _.merge(GLOBALS.pendingSearchFilters[0][tempKey], tempValue);
						}
					} else {
						const i = _.findIndex(GLOBALS.pendingSearchFilters, tempKey);
						if (resetFacetGroup) {
							console.log("try to remove " + tempValue)
						} else {
							_.update(GLOBALS.pendingSearchFilters, [i], function (n) {
								return n = {[tempKey]: tempValue};
							});
						}
					}
				} else {
					if (resetFacetGroup) {
						console.log("skip adding it")
					} else {
						GLOBALS.pendingSearchFilters = _.concat(GLOBALS.pendingSearchFilters, pendingFilters);
					}
				}
			});
		}

		let params = "";
		params = this.buildParams();
		params = _.join(params, '');

		GLOBALS.hasPendingChanges = false;
		const {navigation} = this.props;
		if (toFilters) {
			navigation.navigate("Filters", {
				term: this.state.term
			});
		} else {
			navigation.navigate("SearchResults", {
				term: this.state.term,
				pendingParams: params,
			});
		}

	}

	clearSelections = () => {
		const {applied, category} = this.state;
		const allowMultiple = this._allowMultiple;
		applied.forEach(item => {
			this.updateFilters(category, item.value, allowMultiple);
		})
		this.updateSearch(true, false);
	}

	buildParams = () => {
		let params = [];
		_.map(GLOBALS.pendingSearchFilters, function (n) {
			const key = _.findKey(n);
			const value = _.get(n, key);

			if (_.isObject(value)) {
				_.forEach(value, function (tempValue, tempKey) {
					if (key === "sort_by") {
						params = params.concat('&sort=' + encodeURI(tempValue))
					} else {
						params = params.concat('&filter[]=' + encodeURI(key + ':' + tempValue))
					}
				})
			} else {
				if (key === "sort_by") {
					params = params.concat('&sort=' + encodeURI(value))
				} else {
					params = params.concat('&filter[]=' + encodeURI(key + ':' + value))
				}
			}
		})
		return params;
	}

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
		)
	}

	static contextType = userContext;

	render() {
		const {facets, category} = this.state;

		if (this.state.isLoading) {
			return (loadingSpinner());
		}

		if (category === "publishDate" || category === "birthYear" || category === "deathYear" || category === "publishDateSort") {
			return (
				<View style={{flex: 1}}>
					<ScrollView>
						<Box safeArea={5}>
							<Facet_Year category={category} updater={this.updateFilters} data={facets}/>
						</Box>
					</ScrollView>
					{this.actionButtons()}
				</View>
			)
		} else if (category === "rating_facet") {
			return (
				<View style={{ flex: 1 }}>
					<ScrollView>
						<Box safeArea={5}>
							<Facet_Rating category={category} updater={this.updateFilters} data={facets} />
						</Box>
					</ScrollView>
					{this.actionButtons()}
				</View>
			)
		} else if (category === "lexile_score" || category === "accelerated_reader_point_value" || category === "accelerated_reader_reading_level") {
			return (
				<View style={{ flex: 1 }}>
					<ScrollView>
						<Box safeArea={5}>
							<Facet_Slider category={category} data={facets} updater={this.updateFilters} />
						</Box>
					</ScrollView>
					{this.actionButtons()}
				</View>
			)
		} else if (this.state.multiSelect) {
			return(
				<View style={{ flex: 1 }}>
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
									<Facet_Checkbox key={index} data={item} />
								))}
							</Checkbox.Group>
						</Box>
					</ScrollView>
					{this.actionButtons()}
				</View>
			)
		} else {
			return(
				<View style={{ flex: 1 }}>
					{this.searchBar()}
					<ScrollView>
						<Box safeAreaX={5}>
							<Facet_RadioGroup data={facets} category={category} title={this.state.title}
							                  applied={this.state.applied} updater={this.updateFilters}/>
						</Box>
					</ScrollView>
					{this.actionButtons()}
				</View>
			)
		}
	}
}