import React, {Component} from "react";
import {ScrollView} from "react-native";
import {Box, Text, Input, FormControl, HStack, Slider} from "native-base";

import _ from "lodash";

// custom components and helper files
import {userContext} from "../../../context/user";
import {loadingSpinner} from "../../../components/loadingSpinner";
import {translate} from "../../../translations/translations";

export default class Facet_Slider extends Component {
	constructor(props, context) {
		super(props, context);
		this.state = {
			isLoading: true,
			startValue: 0.0,
			endValue: 5.0,
			item: this.props.data,
			category: this.props.category,
			updater: this.props.updater,
		};
		this._isMounted = false;
	}

	componentDidMount = async () => {
		this._isMounted = true;

		this.appliedStartValue();
		this.appliedEndValue();

		this.setState({
			isLoading: false,
		})
	}

	componentWillUnmount() {
		this._isMounted = false;
	}

	updateValue = (type, value) => {
		const {category, updater} = this.state;

		this.setState({
			[type]: value
		})

		updater(category, value, false);
	}

	appliedStartValue = () => {
		const {item, category} = this.state;
		let value = 0.0;
		let appliedStartValue = "";

		if(_.find(item, ['isApplied', true])) {
			let appliedFilterObj = _.find(item, ['isApplied', true]);
			value = appliedFilterObj['value'];
			appliedStartValue = "&filter[]=" + encodeURI(category + ':' + value)
		}

		this.setState({
			startValue: value,
			appliedStartValue: appliedStartValue,
		})
	}

	appliedEndValue = () => {
		const {item, category} = this.state;
		let value = 5.0;
		let appliedEndValue = "";

		if(_.find(item, ['isApplied', true])) {
			let appliedFilterObj = _.find(item, ['isApplied', true]);
			value = appliedFilterObj['value'];
			appliedEndValue = "&filter[]=" + encodeURI(category + ':' + value)
		}

		this.setState({
			endValue: value,
			appliedEndValue: appliedEndValue,
		})
	}

	static contextType = userContext;

	render() {
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
								placeholder={translate('filters.from')}
								accessibilityLabel={translate('filters.from')}
								defaultValue={this.state.startValue}
								value={this.state.startValue}
								onChangeText={(value) => {this.updateValue('startValue', value)}}
								w="50%"
							/>
							<Input
								size="lg"
								placeholder={translate('filters.to')}
								accessibilityLabel={translate('filters.to')}
								defaultValue={this.state.endValue}
								value={this.state.endValue}
								onChangeText={(value) => {this.updateValue('endValue', value)}}
								w="50%"
							/>
						</HStack>
					</FormControl>
				</Box>
			</ScrollView>
		)
	}
}