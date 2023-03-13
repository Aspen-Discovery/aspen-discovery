import { FormControl, Select, CheckIcon } from 'native-base';
import React from 'react';
import {getTermFromDictionary} from '../../../translations/TranslationService';

export const SelectPickupLocation = (props) => {
	const { locations, location, setLocation, language } = props;
	return (
		<>
			<FormControl>
				<FormControl.Label>{getTermFromDictionary(language, 'pickup_at')}</FormControl.Label>
				<Select
					name="pickupLocations"
					selectedValue={location}
					minWidth="200"
					accessibilityLabel={getTermFromDictionary(language, 'select_pickup_location')}
					_selectedItem={{
						bg: 'tertiary.300',
						endIcon: <CheckIcon size="5" />,
					}}
					mt={1}
					mb={2}
					onValueChange={(itemValue) => setLocation(itemValue)}>
					{locations.map((location, index) => {
						return <Select.Item label={location.name} value={location.code} key={index} />;
					})}
				</Select>
			</FormControl>
		</>
	);
};

export default SelectPickupLocation;