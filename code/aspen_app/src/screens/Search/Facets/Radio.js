import React, {Component} from 'react';
import {Radio} from 'native-base';

export default class Facet_Radio extends Component {
	constructor() {
		super();
		this.state = {checkedRadio: null};
	}

	changeRadio(e) {
		this.setState({checkedRadio: e.target.value});
	}

	render() {
		const {data, category} = this.props;
		if (category !== 'sort_by') {
			const numOfResults = '(' + data.count + ')';
			return (
					<Radio value={data.value} accessibilityLabel={data.display} name={category}>
						{data.display} {numOfResults}
					</Radio>
			);
		}
		return (
				<Radio value={data.value} accessibilityLabel={data.display} name={category} style={{bg: 'muted.300'}}>
					{data.display}
				</Radio>
		);
	}
}