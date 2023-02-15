import React from 'react';
import { FormControl, Select, Input, Checkbox, Text, CheckIcon } from 'native-base';
import {translate} from '../../../translations/translations';
import _ from 'lodash';

export const HoldNotificationPreferences = (props) => {
	const {user, emailNotification, setEmailNotification, phoneNotification, setPhoneNotification, smsNotification, setSMSNotification, smsCarrier, setSMSCarrier, smsNumber, setSMSNumber, phoneNumber, setPhoneNumber} = props;

	const holdNotificationInfo = user.holdNotificationInfo;
	const smsCarriers = holdNotificationInfo.smsCarriers;

	return (
		<>
			<Text>{translate('holds.notify_for_pickup')}</Text>
			{user.email ? (
				<FormControl pb={2}>
					<Checkbox
						name="emailNotification"
						defaultIsChecked={emailNotification}
						accessibilityLabel={translate('holds.email_notification')}
						onChange={(value) => {setEmailNotification(value)}}
					>
						<Text>{translate('holds.email_notification', {email: user.email})}</Text>
					</Checkbox>
				</FormControl>
			) : null}
			<FormControl>
				<Checkbox
					name="phoneNotification"
					defaultIsChecked={phoneNotification}
					accessibilityLabel={translate('holds.phone_notification')}
					onChange={(value) => {setPhoneNotification(value)}}
				>
					<Text>{translate('holds.phone_notification')}</Text>
				</Checkbox>
			</FormControl>
			{phoneNotification ? (
			<>
				<FormControl>
					<FormControl.Label>{translate('holds.phone_number')}</FormControl.Label>
					<Input
						name="phoneNumber"
						defaultValue={phoneNumber}
						accessibilityLabel={translate('holds.phone_number')}
						onChangeText={(value) => setPhoneNumber(value)}
					/>
				</FormControl>
			</>
			) : null}
			{!_.isEmpty(smsCarriers) ? (
				<>
					<FormControl pt={2}>
						<Checkbox
							name="smsNotification"
							defaultIsChecked={smsNotification}
							accessibilityLabel={translate('holds.sms_notification')}
							onChange={(value) => {setSMSNotification(value)}}
						>
							<Text>{translate('holds.sms_notification')}</Text>
						</Checkbox>
					</FormControl>
					{smsNotification ? (
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
										return <Select.Item key={index} label={item} value={index} />
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
				) : null}
		</>
	)
}