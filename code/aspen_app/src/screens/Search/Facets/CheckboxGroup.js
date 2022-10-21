import React, {Component} from "react";
import {Checkbox} from "native-base";
import _ from "lodash";

import Facet_Checkbox from "./Checkbox";

export default class Facet_CheckboxGroup extends Component {
	constructor(props, context) {
		super(props, context);
		this.state = {
			isLoading: true,
			title: this.props.title,
			items: this.props.data,
			category: this.props.category,
			updater: this.props.updater,
			applied: this.props.applied,
			value: null,
		};
		this._isMounted = false;
	}

	componentDidMount = async () => {
		this._isMounted = true;

		if(_.isObject(this.state.applied[0])) {
			if(typeof this.state.applied[0].value !== "undefined") {
				this.setState({
					value: this.state.applied[0].value,
				})
			}
		}

		this.setState({
			isLoading: false,
		})

	}

	componentWillUnmount() {
		this._isMounted = false;
	}

	render() {
		const {items, category, title, updater, applied} = this.state;
		const name = category + "_group";

		return(
			<Checkbox.Group
				name={name}
				accessibilityLabel={title}
				space={4}
				defaultValue={this.state.value}
				onChange={values => {updater(category, values, true)}}
			>
				{items.map((facet, index) => (
					<Facet_Checkbox key={index} data={facet} category={category} />
				))}
			</Checkbox.Group>
		)
	}
}