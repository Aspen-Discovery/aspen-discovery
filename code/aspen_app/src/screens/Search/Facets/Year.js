import React, {Component} from "react";
import {ScrollView} from "react-native";
import {Box, Text, Input, Button, FormControl, HStack, Container} from "native-base";
import _ from "lodash";
import moment from "moment";

// custom components and helper files
import {userContext} from "../../../context/user";
import {loadingSpinner} from "../../../components/loadingSpinner";
import {translate} from "../../../translations/translations";

export default class Facet_Year extends Component {
	constructor(props, context) {
		super(props, context);
		this.state = {
			isLoading: true,
			yearFrom: "",
			yearTo: "",
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
		})
	}

	componentWillUnmount() {
		this._isMounted = false;
	}

	_updateYearTo = (jump) => {
		const jumpTo = moment().subtract(jump, 'years');
		const year = moment(jumpTo).format('YYYY');
		this.setState({
			yearFrom: year
		})
	}

	updateValue = (type, value) => {
		const {category, updater} = this.state;

		this.setState({
			[type]: value
		})

		updater(category, value, false);
	}

	static contextType = userContext;

	render() {
		const {item, category} = this.state;

		if(this.state.isLoading) {
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
								onChangeText={(value) => {this.updateValue('yearFrom', value)}}
								w="50%"
							/>
							<Input
								size="lg"
								placeholder={translate('filters.year_to')}
								accessibilityLabel={translate('filters.year_to')}
								onChangeText={(value) => {this.updateValue('yearTo', value)}}
								w="50%"
							/>
						</HStack>
					</FormControl>
					{category === "publishDate" || category === "publishDateSort" ? (
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
		)
	}
}