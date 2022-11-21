import React, {Component} from 'react';
import {HStack, Icon, Pressable, Text, VStack} from 'native-base';
import {MaterialIcons} from '@expo/vector-icons';
import _ from 'lodash';
import {addAppliedFilter, removeAppliedFilter, SEARCH} from '../../../util/search';

export default class Facet_RadioGroup extends Component {
	constructor(props, context) {
		super(props, context);
		this.state = {
			isLoading: true,
			title: this.props.title,
			items: this.props.data,
			category: this.props.category,
			updater: this.props.updater,
			applied: this.props.applied,
			pending: SEARCH.pendingFilters,
			value: '',
		};
		this._isMounted = false;
	}

	componentDidMount = async () => {
		this._isMounted = true;
		let facets = this.state.items;

		if (_.isObject(facets)) {
			const facet = _.filter(facets, 'isApplied');
			if (!_.isEmpty(facet)) {
				this.setState({
					value: facet[0]['value'] ?? '',
				});
			}
		}

		this.setState({
			isLoading: false,
		});

	};

	componentDidUpdate(prevProps, prevState) {
		if (prevState.value !== this.state.applied) {
			this.renderValue();
		}
	}

	componentWillUnmount() {
		this._isMounted = false;
	}

	renderValue = () => {
		this.setState({
			value: this.state.applied,
		});
	};

	updateValue = (payload) => {
		const {category, value} = this.state;
		if (category !== 'sort_by') {
			console.log('payload > ', payload);
			if (payload === value) {
				removeAppliedFilter(category, payload);
				this.setState({
					value: '',
				});
			} else {
				addAppliedFilter(category, payload, false);
				this.setState({
					value: payload,
				});
			}
		} else {
			console.log('payload > ', payload);
			console.log('value > ', value);
			if (payload === value) {
				this.setState({
					value: 'relevance',
				});
			} else {
				this.setState({
					value: payload,
				});
				SEARCH.sortMethod = payload;
			}
			addAppliedFilter(category, payload, false);
			//console.log(SEARCH.pendingFilters);
		}

		this.props.updater(category, payload);
	};

	render() {
		const {items, category, title, updater, applied} = this.state;
		const name = category + '_group';

		if (category === 'sort_by') {
			return (
					<VStack space={2}>
						{items.map((facet, index) => (
								<Pressable onPress={() => this.updateValue(facet.value)} p={0.5} py={2}>
									{this.state.value === facet.value ?
											<HStack space={3} justifyContent="flex-start" alignItems="center">
												<Icon as={MaterialIcons} name="radio-button-checked" size="lg" color="primary.600"/>
												<Text color="darkText" ml={2}>{facet.display}</Text>
											</HStack>
											:
											<HStack space={3} justifyContent="flex-start" alignItems="center">
												<Icon as={MaterialIcons} name="radio-button-unchecked" size="lg" color="muted.400"/>
												<Text color="darkText" ml={2}>{facet.display}</Text>
											</HStack>
									}
								</Pressable>
						))}
					</VStack>
			);
		}

		return (
				<VStack space={2}>
					{items.map((facet, index) => (
							<Pressable onPress={() => this.updateValue(facet.value)} p={0.5} py={2}>
								{this.state.value === facet.value ?
										<HStack space={3} justifyContent="flex-start" alignItems="center">
											<Icon as={MaterialIcons} name="radio-button-checked" size="lg" color="primary.600"/>
											<Text color="darkText" ml={2}>{facet.display} ({facet.count})</Text>
										</HStack>
										:
										<HStack space={3} justifyContent="flex-start" alignItems="center">
											<Icon as={MaterialIcons} name="radio-button-unchecked" size="lg" color="muted.400"/>
											<Text color="darkText" ml={2}>{facet.display} ({facet.count})</Text>
										</HStack>
								}
							</Pressable>
					))}
				</VStack>
		);
	}
}