import React from 'react';
import {LibrarySystemContext, UserContext} from '../../../context/initialContext';
import { FormControl, Select, Input, Checkbox, Text, CheckIcon } from 'native-base';
import {translate} from '../../../translations/translations';
import _ from 'lodash';

export const HoldNotificationPreferences = (props) => {
	const {emailNotification, setEmailNotification, phoneNotification, setPhoneNotification, smsNotification, setSMSNotification, smsCarrier, setSMSCarrier, smsNumber, setSMSNumber} = props;
	const { user } = React.useContext(UserContext);

	// these will later be loaded in with the user profile
	const backupHoldNotificationInfo = {
		primaryEmail: user.email,
		preferences: {
			emailNotification: 1,
			phoneNotification: 0,
			smsNotification: 0,
			smsCarrier: 'Verizon',
			smsNumber: '188812345678'
		},
		smsCarriers: [{
			verizon: "Verizon",
			tmobile: "T Mobile",
		}]
	}

	const holdNotificationInfo = user.holdNotificationInfo ?? backupHoldNotificationInfo;
	const smsCarriers = holdNotificationInfo.smsCarriers;

	return (
		<>
			<Text>{translate('holds.notify_for_pickup')}</Text>
			<FormControl>
				<Checkbox
					name="emailNotification"
					defaultIsChecked={emailNotification}
					accessibilityLabel={translate('holds.email_notification')}
					onChange={(value) => {setEmailNotification(value)}}
				>
					{translate('holds.email_notification', {email: user.email})}
				</Checkbox>
			</FormControl>
			<FormControl>
				<Checkbox
					name="phoneNotification"
					defaultIsChecked={phoneNotification}
					accessibilityLabel={translate('holds.phone_notification')}
					onChange={(value) => {setPhoneNotification(value)}}
				>
					{translate('holds.phone_notification')}
				</Checkbox>
			</FormControl>
			<FormControl>
				<Checkbox
					name="smsNotification"
					defaultIsChecked={smsNotification}
					accessibilityLabel={translate('holds.sms_notification')}
					onChange={(value) => {setSMSNotification(value)}}
				>
					{translate('holds.sms_notification')}
				</Checkbox>
			</FormControl>
			{!_.isEmpty(smsCarriers) ? (
				<>
					<FormControl>
						<FormControl.Label>{translate('holds.sms_carrier')}</FormControl.Label>
						<Select
							name="smsCarrier"
							selectedValue={smsCarrier}
							accessibilityLabel={translate('holds.sms_select_carrier')}
							_selectedItem={{
								bg: 'tertiary.300',
								endIcon: <CheckIcon size="5" />,
							}}
							onValueChange={(itemValue) => setSMSCarrier(itemValue)}>
							{_.map(smsCarriers, function(item, index, array) {
								return <Select.Item key={index} label="label" value="value" />
							})}
						</Select>
						<FormControl.HelperText>
							{translate('holds.sms_charges')}
						</FormControl.HelperText>
					</FormControl>
					<FormControl>
						<FormControl.Label>{translate('holds.sms_number')}</FormControl.Label>
						<Input
							name="smsNumber"
							defaultValue={smsNumber}
							accessibilityLabel={translate('holds.sms_number')}
							onChangeText={(value) => setSMSNumber(value)}
						/>
						<FormControl.HelperText>
							{translate('holds.sms_format')}
						</FormControl.HelperText>
					</FormControl>
				</>
				) : null}
		</>
	)
}