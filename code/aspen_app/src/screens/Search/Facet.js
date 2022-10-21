import React, {Component} from "react";
import {Box, Button, Checkbox, Center, Input, View} from "native-base";
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
import Facet_CheckboxGroup from "./Facets/CheckboxGroup";
import {translate} from "../../translations/translations";

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
			appliedValues: this.props.route.params?.data.applied ?? [],
			applied: this.props.route.params?.data.applied ?? [],
			multiSelect: this.props.route.params?.data.multiSelect ?? false,
			pendingFilters: [],
			filterByQuery: "",
			hasPendingChanges: false,
			isUpdating: false,
		};
		this._isMounted = false;
		this._allowMultiple = false;
	}

	 componentDidMount = async () => {
		 this._isMounted = true;

		 if(this.state.multiSelect) {
			 this.defaultCheckboxValues();
		 }

		 this.setState({
			 isLoading: false,
		 })
	 }

	componentWillUnmount() {
		this._isMounted = false;
	}

	filter(list) {
		const filterByQuery = this.state.filterByQuery;

		return _.filter(list, function(facet) {
			return facet.label.indexOf(filterByQuery) > -1
		})
	}

	searchBar = () => {
		if(this.state.numFacets > 10) {
			const placeHolder = translate('search.title') + " " + this.state.title;
			return(
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
		this._allowMultiple = true;
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
	}

	updateSearch = () => {
		const pendingFilters = this.state.pendingFilters;
		const allowMultiple = this._allowMultiple;

		_.forEach(pendingFilters, function(tempValue, tempKey) {
			if(_.find(GLOBALS.pendingSearchFilters, tempKey)) {
				if(allowMultiple) {
					GLOBALS.pendingSearchFilters = _.merge(GLOBALS.pendingSearchFilters[0][tempKey], tempValue);
				} else {
					const i = _.findIndex(GLOBALS.pendingSearchFilters, tempKey)
					_.update(GLOBALS.pendingSearchFilters, [i], function(n) { return n = {[tempKey]:tempValue}; });
				}
			} else {
				GLOBALS.pendingSearchFilters = _.concat(GLOBALS.pendingSearchFilters, pendingFilters);
			}
		});

		let params = this.buildParams();
		params = _.join(params, '');

		const { navigation } = this.props;
		navigation.navigate("SearchResults", {
			term: this.state.term,
			pendingParams: params,
			pendingUpdates: pendingFilters,
		});

	}

	backToFilters = () => {
		const { navigation } = this.props;
		navigation.navigate("Filters", {
			pendingUpdates: this.state.pendingFilters,
		})
	}

	resetSearch = () => {
		this.setState({
			pendingFilters: [],
			facets: this.props.route.params?.facets,
			hasPendingChanges: false,
		})

		const { navigation } = this.props;
		navigation.navigate("SearchResults", {
			term: this.state.query,
			pendingFilters: []
		});
	}

	buildParams = () => {
		let params = [];
		_.map(GLOBALS.pendingSearchFilters, function(n) {
			const key = _.findKey(n);
			const value = _.get(n, key);
			if(_.isObject(value)) {
				_.forEach(value, function(tempValue, tempKey) {
					if(key === "sort_by") {
						params = params.concat('&sort=' + encodeURI(tempValue))
					} else {
						params = params.concat('&filter[]=' + encodeURI(key + ':' + tempValue))
					}
				})
			} else {
				if(key === "sort_by") {
					params = params.concat('&sort=' + encodeURI(value))
				} else {
					params = params.concat('&filter[]=' + encodeURI(key + ':' + value))
				}
			}
		})
		return params;
	}

	actionButtons = () => {
		return(
			<Box safeArea={5} _light={{ bg: 'coolGray.50' }} _dark={{ bg: 'coolGray.700'}} shadow={4}>
				<Center pb={2}>
					<Button.Group size="lg">
						<Button variant="outline" onPress={() => this.resetSearch()}>{translate('general.reset')}</Button>
						<Button isLoading={this.state.isUpdating} isLoadingText={translate('general.updating')}
						        onPress={() => {
									this.setState({isUpdating: true});
									this.updateSearch();
									this.setState({isUpdating: false})
						}}>{translate('general.update')}</Button>
					</Button.Group>
				</Center>
			</Box>
		)
	}

	static contextType = userContext;

	render() {
		const {facets, category} = this.state;

		if(this.state.isLoading) {
			return (loadingSpinner());
		}

		if (category === "publishDate" || category === "birthYear" || category === "deathYear" || category === "publishDateSort") {
			return (
				<View style={{ flex: 1 }}>
					<ScrollView>
						<Box safeArea={5}>
							<Facet_Year category={category} updater={this.updateFilters} data={facets} />
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
							<Facet_RadioGroup data={facets} category={category} title={this.state.title} applied={this.state.applied} updater={this.updateFilters} />
						</Box>
					</ScrollView>
					{this.actionButtons()}
				</View>
			)
		}
	}
}