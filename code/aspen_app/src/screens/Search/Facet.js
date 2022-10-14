import React, {Component} from "react";
import {Box, Input, FormControl, Checkbox, Radio, Button, ScrollView} from "native-base";
import AsyncStorage from '@react-native-async-storage/async-storage';

import _ from "lodash";

// custom components and helper files
import {userContext} from "../../context/user";
import {loadingSpinner} from "../../components/loadingSpinner";
import Facet_Standard from "./Facets/Standard";
import Facet_Year from "./Facets/Year";
import Facet_Rating from "./Facets/Rating";
import Facet_Slider from "./Facets/Slider";
import Facet_Checkbox from "./Facets/Checkbox";
import Facet_RadioGroup from "./Facets/RadioGroup";
import {GLOBALS} from "../../util/globals";

export default class Facet extends Component {
	constructor(props, context) {
		super(props, context);
		this.state = {
			isLoading: true,
			facets: this.props.route.params?.facets ?? [],
			numFacets: this.props.route.params?.numFacets ?? 0,
			title: this.props.route.params?.title ?? "Filter",
			data: this.props.route.params?.data ?? [],
			type: this.props.route.params?.type ?? "",
			query: "",
			isSearching: false,
			pendingFilters: [],
			multiSelect: this.props.route.params?.multiSelect ?? false,
			appliedFilters: this.props.route.params?.appliedValues ?? [],
			unsavedData: false,
			defaultValues: this.props.route.params?.defaultValues ?? [],
		};
		this._isMounted = false;
		this._allowMultiple = false;
	}

	componentDidMount = async () => {
		this._isMounted = true;

		this.setState({
			isLoading: false,
		});
	}

	componentWillUnmount() {
		this._isMounted = false;
	}

	filteredFacets(cluster) {
		const query = this.state.query;
		const type = this.state.type;
		if(type !== "sort_by") {
			return _.filter(cluster, function(facet) {
				return facet.display.indexOf(query) > -1
			});
		} else {
			return _.filter(cluster, function(facet) {
				return facet.label.indexOf(query) > -1
			});
		}
	}

	_clearFilters = () => {
		this.setState({
			query: "",
			pendingFilters: [],
			facets: this.props.route.params?.facets,
		})
	}

	setGroupValues = (group, values) => {
		const value = this.buildParam(group, values);
		console.log(values);
		this.setState({
			pendingFilters: values,
		})
	}

	setValue = (group, value, allowMultiple) => {
		const pendingFilter = value;
		this._allowMultiple = allowMultiple;
		if(allowMultiple) {
			this.setState((prevState) => ({
				...prevState,
				pendingFilters: {
					...prevState.pendingFilters,
					[group]: {
						...prevState.pendingFilters[group],
						[pendingFilter]: pendingFilter,
					},
				},
				unsavedData: true,
			}));
		} else {
			this.setState((prevState) => ({
				...prevState,
				pendingFilters: {
					...prevState.pendingFilters,
					[group]: pendingFilter
				},
				unsavedData: true,
			}));
		}
	}

	buildParam = (group, value) => {
		if(group === "sort_by") {
			return '&sort=' + encodeURI(value)
		}
		return '&filter[]=' + encodeURI(group + ':' + value)
	}

	goBackToFilters = () => {
		if(this.state.unsavedData) {
			this.updateGlobalFilters();
			this.setState({
				unsavedData: false,
			})
		}
		const navigation = this.props.navigation;
		navigation.navigate('Filters', {
			pendingFilters: this.state.pendingFilters,
		})
	}

	updateGlobalFilters = () => {
		console.log("Allow multiple? ", this._allowMultiple);
		const pendingFilters = this.state.pendingFilters;
		if(this._allowMultiple) {
			GLOBALS.pendingSearchFilters = _.concat(GLOBALS.pendingSearchFilters, pendingFilters);
		} else {
			_.forEach(pendingFilters, function(tempValue, tempKey) {
				if(_.find(GLOBALS.pendingSearchFilters, tempKey)) {
					const i = _.findIndex(GLOBALS.pendingSearchFilters, tempKey)
					_.update(GLOBALS.pendingSearchFilters, [i], function(n) { return n = {[tempKey]:tempValue}; });
				} else {
					GLOBALS.pendingSearchFilters = _.concat(GLOBALS.pendingSearchFilters, pendingFilters);
				}
			})
		}

	}

	_showSearchBar = () => {
		if(this.state.numFacets > 10) {
			const placeHolder = "Search " + this.state.title;
			return(
				<FormControl>
					<Input
						size="lg"
						variant="outline"
						autoCorrect={false}
						placeholder={placeHolder}
						mb={5}
						onChangeText={(query) => {
							this.setState({query});
						}}
						returnKeyType="search"
					/>
				</FormControl>
			)
		}
	}

	_fixedFooter = () => {
		return(
			<Button.Group alignItems="center" justifyContent="center" pt={3} pb={8} size="lg" shadow="5" _light={{ bg: 'coolGray.50' }} _dark={{ bg: 'coolGray.700'}}>
				<Button variant="outline" onPress={() => this._clearFilters()}>Reset</Button>
				<Button onPress={() => this.goBackToFilters()}>Update</Button>
			</Button.Group>
		);
	}

	static contextType = userContext;

	render() {
		const placeHolder = "Search " + this.state.title;
		const facets = this.state.facets;
		const type = this.state.type;
		const multiSelect = this.state.multiSelect;

		//console.log(type);
		//console.log("**APPLIED FILTERS**")
		//console.log(this.state.appliedFilters);
		console.log("**PENDING FILTERS**");
		console.log(this.state.pendingFilters);
		//console.log("**GLOBAL PENDING FILTERS**");
		//console.log(GLOBALS.pendingSearchFilters);


		if(this.state.isLoading) {
			return (loadingSpinner());
		}

		if(type === "publishDate" || type === "birthYear" || type === "deathYear" || type === "publishDateSort") {
			return (
				<Box flex={1}>
					<ScrollView p={5}>
						<Facet_Year filterBy={this.state.type} data={this.state.facets}/>
					</ScrollView>
					{this._fixedFooter()}
				</Box>
			)
		}else if(type === "rating_facet") {
			return (
				<Box flex={1}>
					<ScrollView p={5}>
						{this._showSearchBar()}
						<Facet_Rating data={this.state.facets} filterBy={this.state.type} />
					</ScrollView>
					{this._fixedFooter()}
				</Box>
			)
		}else if(type === "lexile_score" || type === "accelerated_reader_reading_level" || type === "accelerated_reader_point_value") {
			return (
				<Box flex={1}>
					<ScrollView p={5}>
						<Facet_Slider data={this.state.facets} filterBy={this.state.type} />
					</ScrollView>
					{this._fixedFooter()}
				</Box>
			)
		}else if(multiSelect) {
			return (
				<Box flex={1}>
					<ScrollView p={5}>
						{this._showSearchBar()}
						<Checkbox.Group
							defaultValue={this.state.defaultValues[0]}
							accessibilityLabel="Filter by"
							onChange={values => this.setGroupValues(values)}
						>
							{this.filteredFacets(facets).map((facet, index) => (
								<Facet_Checkbox key={index} data={facet} filterBy={this.state.type} value={facet.value} label={facet.display} num={facet.count} isApplied={facet.isApplied} />
							))}
						</Checkbox.Group>
					</ScrollView>
					{this._fixedFooter()}
				</Box>
			)
		} else if (type === "sort_by") {
			return (
				<Box flex={1}>
					<ScrollView p={5}>
						{this._showSearchBar()}
						<Radio.Group
							accessibilityLabel="Sort by"
							space={4}
						>
							{this.filteredFacets(facets).map((facet, index) => (
								<Facet_Standard key={index} data={facet} filterBy={this.state.type} value={facet.value} label={facet.label} num={facet.count} isApplied={facet.isApplied} defaultValues={this.state.defaultValues} />
							))}
						</Radio.Group>
					</ScrollView>
					{this._fixedFooter()}
				</Box>
			)
		} else {
			return (
				<Box flex={1}>
					<ScrollView p={5}>
						{this._showSearchBar()}
						<Facet_RadioGroup title={this.state.title} type={this.state.type} facets={this.filteredFacets(facets)} setValue={this.setValue} defaultValues={this.state.defaultValues} />
					</ScrollView>
					{this._fixedFooter()}
				</Box>
			)
		}
	}
}