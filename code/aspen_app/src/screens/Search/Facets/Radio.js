import React, {Component} from "react";
import {Radio} from "native-base";

export default class Facet_Radio extends Component {

	render() {
		const {data, category} = this.props;
		if(category !== "sort_by") {
			const numOfResults = "(" + data.numResults + ")";
			return (
				<Radio value={data.value} accessibilityLabel={data.label}>
					{data.label} {numOfResults}
				</Radio>
			)
		}
		return (
			<Radio value={data.value} accessibilityLabel={data.label}>
				{data.label}
			</Radio>
		)
	}
}