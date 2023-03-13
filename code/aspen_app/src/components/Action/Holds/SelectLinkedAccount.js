import { FormControl, Select, CheckIcon } from 'native-base';
import React from 'react';
import {getTermFromDictionary} from '../../../translations/TranslationService';

const SelectLinkedAccount = (props) => {
	const { user, isPlacingHold, activeAccount, setActiveAccount, accounts, language } = props;

	return (
		<>
			<FormControl>
				<FormControl.Label>{isPlacingHold ? getTermFromDictionary(language, 'linked_place_hold_for_account') : getTermFromDictionary(language, 'linked_checkout_to_account')}</FormControl.Label>
				<Select
					name="linkedAccount"
					selectedValue={activeAccount}
					minWidth="200"
					accessibilityLabel={isPlacingHold ? getTermFromDictionary(language, 'linked_place_hold_for_account') : getTermFromDictionary(language, 'linked_checkout_to_account')}
					_selectedItem={{
						bg: 'tertiary.300',
						endIcon: <CheckIcon size="5" />,
					}}
					mt={1}
					mb={3}
					onValueChange={(itemValue) => setActiveAccount(itemValue)}>
					<Select.Item label={user.displayName} value={user.id} />
					{accounts.map((item, index) => {
						return <Select.Item label={item.displayName} value={item.id} key={index} />;
					})}
				</Select>
			</FormControl>
		</>
	);
};

export default SelectLinkedAccount;