import { FormControl, Select, CheckIcon, Radio } from 'native-base';
import React from 'react';
import _ from 'lodash';
import {getTermFromDictionary} from '../../../translations/TranslationService';

export const SelectItem = (props) => {
	const { id, copies, item, setItem, holdType, setHoldType, showModal, holdTypeForFormat, language, url } = props;

	return (
		<>
			{holdTypeForFormat === 'either' ? (
					<FormControl>
						<Radio.Group
							name="holdTypeGroup"
							accessibilityLabel="">
							<Radio value="item" my={1} size="sm">
								{getTermFromDictionary(language, 'first_available')}
							</Radio>
							<Radio value="volume" my={1} size="sm">
								{getTermFromDictionary(language, 'specific_volume')}
							</Radio>
						</Radio.Group>
					</FormControl>
			) : null}
			{_.isArray(copies) && holdTypeForFormat === 'item' ? (
				<FormControl>
					<FormControl.Label>{getTermFromDictionary(language, 'select_volume')}</FormControl.Label>
					<Select
						name="itemForHold"
						selectedValue={item}
						minWidth="200"
						accessibilityLabel={getTermFromDictionary(language, 'select_volume')}
						_selectedItem={{
							bg: 'tertiary.300',
							endIcon: <CheckIcon size="5" />,
						}}
						mt={1}
						mb={2}
						onValueChange={(itemValue) => setItem(itemValue)}>
						{_.map(copies, function (item, index, array) {
							return <Select.Item label={item.location} value={item.id} key={index} />;
						})}
					</Select>
				</FormControl>
			) : null}
		</>
	)
}