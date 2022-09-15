import React, {Component} from "react";
import {Box, Button, Checkbox, CheckIcon, FlatList, FormControl, Input, Select, Text, TextArea} from "native-base";
import _ from "lodash";
import {loadingSpinner} from "../../components/loadingSpinner";
import {userContext} from "../../context/user";
import {submitVdxRequest} from "../../util/recordActions";

class CreateVDXRequest extends Component {
	static contextType = userContext;
	constructor(props, context) {
		super(props, context);
		this.state = {
			isLoading: true,
			options: [],
			fields: [],
			request: {
				'title': this.props.route.params?.title ?? null,
				'author': this.props.route.params?.author ?? null,
				'publisher': this.props.route.params?.publisher ?? null,
				'isbn': null,
				'acceptFee': false,
				'maximumFeeAmount': 5.00,
				'note': null,
				'catalogKey': this.props.route.params?.catalogKey ?? null,
				'pickupLocation': this.props.route.params?.pickupLocation ?? null,
			},
		};
	}

	componentDidMount = async () => {
		const { navigation, route } = this.props;
		const vdxOptions = route.params?.vdxOptions ?? null;

		if(vdxOptions) {
			this.setState({
				options: vdxOptions,
				fields: _.values(vdxOptions['fields']),
				isLoading: false,
			})
		} else {
			console.log("Error")
		}
	}

	updateValue = (field, value) => {
		let newValue = value;
		if(field === "showMaximumFee") {
			newValue = this.formatCurrency(value);
		}

		let currentValues = [this.state.request];
		_.set(currentValues[0], field, newValue);
	}

	getValue = (field) => {
		return this.state.request[field];
	}

	returnField = (field, key) => {
		let currentFields = [this.state.fields];
		let matchedField = _.find(currentFields[0], _.matchesProperty('property', field));
		return matchedField[key];
	}

	formatCurrency = (value) => {
		return Number.parseFloat(value).toFixed(2);
	}

	getPlaceholder = (field) => {
		return this.props.route.params?.[field] ?? '';
	}

	onSubmit = async () => {
		await submitVdxRequest(this.context.library.baseUrl, this.state.request)
	}

	_renderField = (field) => {
		if(field.type === "input" && field.display === "show") {
			return (
				<FormControl my={2} isRequired={field.required}>
					<FormControl.Label>{field.label}</FormControl.Label>
					<Input
						name={field.property}
						defaultValue={this.getPlaceholder(field.property)}
						accessibilityLabel={field.description ?? field.label}
						onChangeText={(value) => {this.updateValue(field.property, value)}} />
				</FormControl>
			)
		}

		if(field.type === "textarea" && field.display === "show") {
			return (
				<FormControl my={2} isRequired={field.required}>
					<FormControl.Label>{field.label}</FormControl.Label>
					<TextArea
						name={field.property}
						value={this.getValue(field.property)}
						accessibilityLabel={field.description ?? field.label}
						onChangeText={(text) => {this.updateValue(field.property, text)}}
					/>

				{field.property === "title" ? (
					<FormControl.HelperText>{this.returnField("feeInformationText","label")}</FormControl.HelperText>
				) : null}

				</FormControl>
			)
		}

		if(field.type === "select" && field.display === "show") {
			if(_.isArray(field.options)) {
				const locations = field.options;
				return (
					<FormControl my={2} isRequired={field.required}>
						<FormControl.Label>{field.label}</FormControl.Label>
						<Select
							name='pickupLocation'
							defaultValue={this.getPlaceholder('pickupLocation')}
							accessibilityLabel={field.description ?? field.label}
							_selectedItem={{
								bg: "tertiary.300",
								endIcon: <CheckIcon size="5" />
							}}
							selectedValue={this.getValue('pickupLocation')}
							onValueChange={itemValue => {this.updateValue('pickupLocation', itemValue)}}
						>
							{locations.map((location, index) => {
								return <Select.Item label={location.displayName} value={location.locationId}/>;
							})}
						</Select>
					</FormControl>
				)
			}
		}

		if(field.type === "number" && field.display === "show") {
			return (
				<FormControl my={2} isRequired={field.required}>
					<FormControl.Label>{field.label}</FormControl.Label>
					<Input
						name={field.property}
						defaultValue={this.getPlaceholder(field.property)}
						accessibilityLabel={field.description ?? field.label}
						keyboardType="decimal-pad"
						onChangeText={(value) => {this.updateValue(field.property, value)}}
					/>
				</FormControl>
			)
		}

		if(field.type === "checkbox" && field.display === "show") {
			return (
				<FormControl my={2} maxW="90%" isRequired={field.required}>
					<Checkbox
						name={field.property}
						defaultValue={this.getPlaceholder(field.property)}
						accessibilityLabel={field.description ?? field.label}
						onChange={value => {this.updateValue(field.property, value)}}
						value={true}>
							{field.label}
					</Checkbox>
				</FormControl>
			)
		}

		if(field.type === "number" && field.display === "show") {
			return (
				<FormControl my={2} isRequired={field.required}>
					<FormControl.Label>{field.label}</FormControl.Label>
					<Input defaultValue={5.00} keyboardType="decimal-pad" />
				</FormControl>
			)
		}

		if(field.property === "catalogKey" && field.display === "show") {
			return (
				<FormControl my={2} isDisabled isRequired={field.required}>
					<FormControl.Label>{field.label}</FormControl.Label>
					<Input
						name={field.property}
						defaultValue={this.getPlaceholder(field.property)}
						accessibilityLabel={field.description ?? field.label} />
				</FormControl>
			)
		}
	}

	_renderHeader = () => {
		return (
			<Text fontSize="sm" pb={3}>{this.state.options.fields.introText.label}</Text>
		)
	}

	_renderFooter = () => {
		return (
			<Button.Group pt={3}>
				<Button colorScheme="secondary" onPress={() => {
					this.props.navigation.goBack();
					this.onSubmit()
				}}>{this.state.options.buttonLabel}</Button>
				<Button colorScheme="secondary" variant="outline" onPress={() => this.props.navigation.goBack()}>Cancel</Button>
			</Button.Group>
		)
	}

	render() {
		if (this.state.isLoading) {
			return ( loadingSpinner() );
		}

		return(
			<Box safeArea={5}>
				<FlatList
					data={this.state.fields}
					renderItem={({ item }) => this._renderField(item)}
					keyExtractor={(item) => item.property}
					ListHeaderComponent={this._renderHeader}
					ListFooterComponent={this._renderFooter}
				/>
			</Box>
		)
	}
}

export default CreateVDXRequest;