import React, {Component} from "react";
import {Radio} from "native-base";
import _ from "lodash";

import Facet_Radio from "./Radio";

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

	updateValue = (payload) => {
		this.setState({
			value: payload
		})
	}

	render() {
		const {items, category, title, updater, applied} = this.state;
		const name = category + "_group";

		if(category === "sort_by") {
			return(
				<Radio.Group
					name={name}
					accessibilityLabel={title}
					space={4}
					defaultValue={applied[0]}
					value={this.state.value}
					onChange={nextValue => {this.updateValue(nextValue); updater(category, nextValue, false)}}
				>
					{items.map((facet, index) => (
						<Facet_Radio key={index} data={facet} category={category} />
					))}
				</Radio.Group>
			)
		}

		return(
			<Radio.Group
				name={name}
				accessibilityLabel={title}
				space={4}
				defaultValue={applied[0]}
				value={this.state.value}
				onChange={nextValue => {this.updateValue(nextValue); updater(category, nextValue, false)}}
			>
				{items.map((facet, index) => (
					<Facet_Radio key={index} data={facet} category={category} />
				))}
			</Radio.Group>
		)
	}
}