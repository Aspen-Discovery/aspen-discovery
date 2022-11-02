import React, {Component} from 'react';
import {Box, Button, Center, ChevronRightIcon, HStack, Pressable, ScrollView, Text, View, VStack} from 'native-base';

// custom components and helper files
import {userContext} from '../../context/user';
import {loadingSpinner} from '../../components/loadingSpinner';
import {buildParamsForUrl, SEARCH} from '../../util/search';
import _ from 'lodash';
import {translate} from '../../translations/translations';
import {UnsavedChangesExit} from './UnsavedChanges';

export default class Filters extends Component {
	static contextType = userContext;

	constructor(props, context) {
		super(props, context);
		this.state = {
			isLoading: true,
			term: '',
			facets: SEARCH.availableFacets.data ? Object.keys(SEARCH.availableFacets.data) : [],
		};
		this._isMounted = false;
	}

	componentDidMount = async () => {
		this._isMounted = true;
		this.setState({
			isLoading: false,
		});
	};

	componentWillUnmount() {
		this._isMounted = false;
	}

	componentDidUpdate() {
		const {navigation} = this.props;
		navigation.setOptions({
			/* headerLeft: () => (
			 <UnsavedChangesBack updateSearch={this.updateSearch}/>
			 ), */
			headerRight: () => (
					<UnsavedChangesExit updateSearch={this.updateSearch}/>
			),
		});
	}

	renderFilter = (label) => {
		return (
				<Pressable borderBottomWidth="1" _dark={{borderColor: 'gray.600'}} borderColor="coolGray.200" py="5" onPress={() => this._openFacetCluster(label)}>
					<VStack alignContent="center">
						<HStack justifyContent="space-between" align="center">
							<Text bold>{label}</Text>
							<ChevronRightIcon/>
						</HStack>
						{this._appliedFacet(label)}
					</VStack>
				</Pressable>
		);
	};

	_appliedFacet = (cluster) => {
		const appliedFacets = _.filter(SEARCH.availableFacets.data, 'hasApplied');
		const pendingFacets = SEARCH.pendingFilters;
		let text = '';
		if (_.size(appliedFacets) > 0) {
			const obj = _.filter(appliedFacets, ['label', cluster]);
			if (_.size(obj) > 0) {
				_.forEach(obj[0]['facets'], function(value, key) {
					if (value['isApplied']) {
						if (text.length === 0) {
							text = text.concat(_.toString(value['display']));
						} else {
							text = text.concat(', ', _.toString(value['display']));
						}
					}
				});
			} else {
				text = text.concat(_.toString(obj['display']));
			}
		}
		if (!_.isEmpty(text)) {
			return (
					<Text>{text}</Text>
			);
		} else {
			return null;
		}
	};

	_openFacetCluster = (cluster) => {
		const obj = SEARCH.availableFacets.data[cluster];
		const navigation = this.props.navigation;
		navigation.navigate('Facet', {
			data: cluster,
			defaultValues: [],
			title: obj['label'],
			key: obj['value'],
			term: this.state.term,
			facets: obj.facets,
			pendingUpdates: [],
			extra: obj,
		});
	};

	updateSearch = () => {
		const params = buildParamsForUrl();
		SEARCH.hasPendingChanges = false;
		const {navigation} = this.props;
		navigation.navigate('SearchResults', {
			term: SEARCH.term,
			pendingParams: params,
		});
	};

	clearSelections = () => {
		SEARCH.hasPendingChanges = false;
		this.props.navigation.navigate('SearchResults', {
			term: SEARCH.term,
			pendingParams: '',
		});
	};

	actionButtons = () => {
		return (
				<Box safeArea={3} _light={{bg: 'coolGray.50'}} _dark={{bg: 'coolGray.700'}} shadow={1}>
					<Center>
						<Button.Group size="lg">
							<Button variant="unstyled"
											onPress={() => this.clearSelections()}>{translate('general.reset_all')}</Button>
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
		const user = this.context.user;
		const location = this.context.location;
		const library = this.context.library;
		const facets = this.state.facets;

		if (this.state.isLoading) {
			return (loadingSpinner());
		}

		return (
				<View style={{flex: 1}}>
					<ScrollView>
						<Box safeArea={5}>
							{facets.map((item, index, array) => (
									this.renderFilter(item)
							))}
						</Box>
					</ScrollView>
					{this.actionButtons()}
				</View>
		);
	}
}