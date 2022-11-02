import React, {Component} from 'react';
import {Checkbox, HStack, Pressable, Text} from 'native-base';

export default class Facet_Checkbox extends Component {
	render() {
		const item = this.props.data;
		return (
				<Pressable py={4}>
					<HStack align="center" space={3}>
						<Checkbox value={item.value} accessibilityLabel={item.display} defaultIsChecked={item.isApplied}/>
						<Text>{item.display} ({item.count})</Text>
					</HStack>
				</Pressable>
		);
	}
}