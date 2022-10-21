import React, {Component} from "react";
import {Text, Pressable, HStack, Checkbox} from "native-base";
import _ from "lodash";

export default class Facet_Checkbox extends Component {
	render() {
		const item = this.props.data;
		return (
			<Pressable py={4}>
				<HStack align="center" space={3}>
					<Checkbox value={item.value} accessibilityLabel={item.label} defaultIsChecked={item.isApplied} />
					<Text>{item.label} ({item.numResults})</Text>
				</HStack>
			</Pressable>
		)
	}
}