import { FormControl, Select, CheckIcon } from 'native-base';
import React from 'react';
import { translate } from '../../../translations/translations';

const SelectLinkedAccount = (props) => {
	const { user, isPlacingHold, activeAccount, setActiveAccount, accounts } = props;

	return (
		<>
			<FormControl>
				<FormControl.Label>{isPlacingHold ? translate('linked_accounts.place_hold_for_account') : translate('linked_accounts.checkout_to_account')}</FormControl.Label>
				<Select
					name="linkedAccount"
					selectedValue={activeAccount}
					minWidth="200"
					accessibilityLabel={isPlacingHold ? translate('linked_accounts.place_hold_for_account') : translate('linked_accounts.checkout_to_account')}
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