import React from 'react';
import { FormControl, Select, Input, Checkbox, Text, CheckIcon } from 'native-base';
import _ from 'lodash';
import {getTermFromDictionary, getTranslationsWithValues} from '../../../translations/TranslationService';

export const HoldNotificationPreferences = (props) => {
	const {user, url, language, emailNotification, setEmailNotification, phoneNotification, setPhoneNotification, smsNotification, setSMSNotification, smsCarrier, setSMSCarrier, smsNumber, setSMSNumber, phoneNumber, setPhoneNumber} = props;

	const holdNotificationInfo = user.holdNotificationInfo;
	const smsCarriers = holdNotificationInfo.smsCarriers;

	const [emailNotificationLabel, setEmailNotificationLabel] = React.useState('Yes, by email');
	React.useEffect(() => {
		async function fetchTranslations() {
			await getTranslationsWithValues('hold_email_notification', user.email ?? null, language, url).then(result => {
				setEmailNotificationLabel(_.toString(result));
			});
		}
		fetchTranslations()
	}, [language]);

	return (
		<>
			<Text>{getTermFromDictionary(language, 'hold_notify_for_pickup')}</Text>
			{user.email ? (
				<FormControl pb={2}>
					<Checkbox
						name="emailNotification"
						defaultIsChecked={emailNotification}
						accessibilityLabel={emailNotificationLabel}
						onChange={(value) => {setEmailNotification(value)}}
					>
						<Text>{emailNotificationLabel}</Text>
					</Checkbox>
				</FormControl>
			) : null}
			<FormControl>
				<Checkbox
					name="phoneNotification"
					defaultIsChecked={phoneNotification}
					accessibilityLabel={getTermFromDictionary(language, 'hold_phone_notification')}
					onChange={(value) => {setPhoneNotification(value)}}
				>
					<Text>{getTermFromDictionary(language, 'hold_phone_notification')}</Text>
				</Checkbox>
			</FormControl>
			{phoneNotification ? (
			<>
				<FormControl>
					<FormControl.Label>{getTermFromDictionary(language, 'hold_phone_number')}</FormControl.Label>
					<Input
						name="phoneNumber"
						defaultValue={phoneNumber}
						accessibilityLabel={getTermFromDictionary(language, 'hold_phone_number')}
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
							accessibilityLabel={getTermFromDictionary(language, 'hold_sms_notification')}
							onChange={(value) => {setSMSNotification(value)}}
						>
							<Text>{getTermFromDictionary(language, 'hold_sms_notification')}</Text>
						</Checkbox>
					</FormControl>
					{smsNotification ? (
						<>
							<FormControl>
								<FormControl.Label>{getTermFromDictionary(language, 'hold_sms_carrier')}</FormControl.Label>
								<Select
									name="smsCarrier"
									selectedValue={smsCarrier}
									accessibilityLabel={getTermFromDictionary(language, 'hold_sms_select_carrier')}
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
									{getTermFromDictionary(language, 'hold_sms_charges')}
								</FormControl.HelperText>
							</FormControl>
							<FormControl>
								<FormControl.Label>{getTermFromDictionary(language, 'hold_sms_number')}</FormControl.Label>
								<Input
									name="smsNumber"
									defaultValue={smsNumber}
									accessibilityLabel={getTermFromDictionary(language, 'hold_sms_number')}
									onChangeText={(value) => setSMSNumber(value)}
								/>
								<FormControl.HelperText>
									{getTermFromDictionary(language, 'hold_sms_format')}
								</FormControl.HelperText>
							</FormControl>
						</>
						) : null}
				</>
				) : null}
		</>
	)
}