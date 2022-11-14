import React, {Component} from 'react';
import {ScrollView} from 'react-native';
import {Box, Button, Container, FormControl, HStack, Input, Text} from 'native-base';
import moment from 'moment';
import _ from 'lodash';

// custom components and helper files
import {userContext} from '../../../context/user';
import {loadingSpinner} from '../../../components/loadingSpinner';
import {translate} from '../../../translations/translations';
import {addAppliedFilter} from '../../../util/search';

export default class Facet_Year extends Component {
	static contextType = userContext;

	constructor(props, context) {
		super(props, context);
		this.state = {
			isLoading: true,
			yearFrom: '',
			yearTo: '',
			value: '',
			item: this.props.data,
			category: this.props.category,
			updater: this.props.updater,
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

	_updateYearTo = (jump) => {
		const jumpTo = moment().subtract(jump, 'years');
		const year = moment(jumpTo).format('YYYY');
		this.setState({
			yearFrom: year,
			yearTo: '*',
			value: years,
		});
		const years = '[' + year + '+TO+*]';
		addAppliedFilter(this.state.category, years, false);
		this.props.updater(this.state.category, years);
	};

	updateValue = (type, value) => {
		this.setState({
			[type]: value,
		}, () => {
			if (_.size(value) === 4) {
				this.updateFacet();
			}
		});
	};

	updateFacet = () => {
		const {category} = this.state;
		let yearFrom = this.state.yearFrom;
		let yearTo = this.state.yearTo;
		if (_.isEmpty(this.state.yearFrom)) {
			yearFrom = '*';
		}
		if (_.isEmpty(this.state.yearTo)) {
			yearTo = '*';
		}
		const years = '[' + yearFrom + '+TO+' + yearTo + ']';
		addAppliedFilter(category, years, false);
		this.props.updater(category, years);
	};

	render() {
		const {item, category} = this.state;

		if (this.state.isLoading) {
			return (loadingSpinner());
		}

		return (
				<ScrollView>
					<Box safeArea={5}>
						<FormControl mb={2}>
							<HStack space={3} justifyContent="center">
								<Input
										size="lg"
										placeholder={translate('filters.year_from')}
										accessibilityLabel={translate('filters.year_from')}
										value={this.state.yearFrom}
										onChangeText={(value) => {
											this.updateValue('yearFrom', value);
										}}
										w="50%"
								/>
								<Input
										size="lg"
										placeholder={translate('filters.year_to')}
										accessibilityLabel={translate('filters.year_to')}
										onChangeText={(value) => {
											this.updateValue('yearTo', value);
										}}
										w="50%"
								/>
							</HStack>
						</FormControl>
						{category === 'publishDate' || category === 'publishDateSort' ? (
								<Container>
									<Text>{translate('filters.published_in_the_last')}</Text>
									<Button.Group variant="subtle">
										<Button onPress={() => this._updateYearTo(1)}>{translate('filters.year')}</Button>
										<Button onPress={() => this._updateYearTo(5)}>{translate('filters.years', {num: 5})}</Button>
										<Button onPress={() => this._updateYearTo(10)}>{translate('filters.years', {num: 10})}</Button>
									</Button.Group>
								</Container>
						) : null}
					</Box>
				</ScrollView>
		);
	}
}