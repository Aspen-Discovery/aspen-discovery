import React from 'react';
import {HoldsContext, LanguageContext, LibrarySystemContext, UserContext} from '../../../context/initialContext';
import { Button, Modal, Heading } from 'native-base';
import _ from 'lodash';
import {completeAction} from '../../../screens/GroupedWork/Record';
import {refreshProfile} from '../../../util/api/user';
import SelectPickupLocation from './SelectPickupLocation';
import SelectLinkedAccount from './SelectLinkedAccount';
import {SelectVolume} from './SelectVolume';
import {HoldNotificationPreferences} from './HoldNotificationPreferences';
import {getTermFromDictionary, getTranslationsWithValues} from '../../../translations/TranslationService';

export const HoldPrompt = (props) => {
	const {id, title, action, volumeInfo, prevRoute, isEContent, response, setResponse, responseIsOpen, setResponseIsOpen, onResponseClose, cancelResponseRef} = props;
	const [loading, setLoading] = React.useState(false);
	const [showModal, setShowModal] = React.useState(false);

	const { user, updateUser, accounts, locations } = React.useContext(UserContext);
	const { library } = React.useContext(LibrarySystemContext);
	const { updateHolds } = React.useContext(HoldsContext);
	const { language } = React.useContext(LanguageContext);

	const isPlacingHold = action.includes('hold');
	let promptForHoldNotifications = user.promptForHoldNotifications ?? false;
	let holdNotificationInfo = user.holdNotificationInfo ?? [];

	let defaultEmailNotification = false;
	let defaultPhoneNotification = false;
	let defaultSMSNotification = false;
	if(promptForHoldNotifications && holdNotificationInfo?.preferences?.opac_hold_notify?.value) {
		const preferences = holdNotificationInfo.preferences.opac_hold_notify.value;
		defaultEmailNotification = getNotificationPreference(preferences, 'email');
		defaultPhoneNotification = getNotificationPreference(preferences, 'phone');
		defaultSMSNotification = getNotificationPreference(preferences, 'sms');
	}

	const [emailNotification, setEmailNotification] = React.useState(defaultEmailNotification);
	const [phoneNotification, setPhoneNotification] = React.useState(defaultPhoneNotification);
	const [smsNotification, setSMSNotification] = React.useState(defaultSMSNotification);
	const [smsCarrier, setSMSCarrier] = React.useState(holdNotificationInfo.preferences?.opac_default_sms_carrier?.value ?? -1);
	const [smsNumber, setSMSNumber] = React.useState(holdNotificationInfo.preferences?.opac_default_sms_notify?.value ?? null);
	const [phoneNumber, setPhoneNumber] = React.useState(holdNotificationInfo.preferences?.opac_default_phone?.value ?? null);
	const [emailNotificationLabel, setEmailNotificationLabel] = React.useState('Yes, by email');
	const holdNotificationPreferences = {
		'emailNotification': emailNotification,
		'phoneNotification': phoneNotification,
		'smsNotification': smsNotification,
		'phoneNumber': phoneNumber,
		'smsNumber': smsNumber,
		'smsCarrier': smsCarrier
	}

	React.useEffect(() => {
		async function fetchTranslations() {
			if(user.email) {
				await getTranslationsWithValues('hold_email_notification', user.email, language, library.baseUrl).then(result => {
					setEmailNotificationLabel(result);
				});
			}
		}
		fetchTranslations()
	}, []);

	let promptForHoldType = false;
	let typeOfHold = 'default';

	if(volumeInfo.numItemsWithVolumes >= 1) {
		typeOfHold = 'item';
		promptForHoldType = true;
		if(volumeInfo.majorityOfItemsHaveVolumes) {
			typeOfHold = 'volume';
			promptForHoldType = true;
		}
		if (_.isEmpty(volumeInfo.hasItemsWithoutVolumes) || !volumeInfo.hasItemsWithoutVolumes === false) {
			typeOfHold = 'volume';
			promptForHoldType = false;
		}
	}

	const [holdType, setHoldType] = React.useState(typeOfHold);
	const [volume, setVolume] = React.useState('');

	const [activeAccount, setActiveAccount] = React.useState(user.id);

	const userPickupLocation = _.filter(locations, { 'locationId': user.pickupLocationId });
	let pickupLocation = '';
	if(!_.isUndefined(userPickupLocation && !_.isEmpty(userPickupLocation))) {
		pickupLocation = userPickupLocation[0];
		if(_.isObject(pickupLocation)) {
			pickupLocation = pickupLocation.code;
		}
	}

	const [location, setLocation] = React.useState(pickupLocation);

	const selectPickupLocation = getTermFromDictionary(language ?? 'en', 'select_pickup_location');

	return (
		<>
			<Button onPress={() => setShowModal(true)}>{title}</Button>
			<Modal isOpen={showModal} onClose={() => setShowModal(false)} closeOnOverlayClick={false} size="lg">
				<Modal.Content maxWidth="90%" bg="white" _dark={{ bg: 'coolGray.800' }}>
					<Modal.CloseButton/>
					<Modal.Header><Heading size="md">{isPlacingHold ? getTermFromDictionary(language, 'hold_options') : getTermFromDictionary(language, 'checkout_options')}</Heading></Modal.Header>
					<Modal.Body>
						{promptForHoldNotifications ? (
							<HoldNotificationPreferences
								user={user}
								language={language}
								emailNotification={emailNotification}
								setEmailNotification={setEmailNotification}
								emailNotificationLabel={emailNotificationLabel}
								phoneNotification={phoneNotification}
								setPhoneNotification={setPhoneNotification}
								smsNotification={smsNotification}
								setSMSNotification={setSMSNotification}
								smsCarrier={smsCarrier}
								setSMSCarrier={setSMSCarrier}
								smsNumber={smsNumber}
								setSMSNumber={setSMSNumber}
								phoneNumber={phoneNumber}
								setPhoneNumber={setPhoneNumber}
							/>
						) : null}
						{promptForHoldType || holdType === 'volume' ? (
							<SelectVolume
								id={id}
								language={language}
								volume={volume}
								setVolume={setVolume}
								promptForHoldType={promptForHoldType}
								holdType={holdType}
								setHoldType={setHoldType}
								showModal={showModal}
								url={library.baseUrl}
							/>
						) : null}
						{_.size(locations) > 1 && !isEContent ? (
							<SelectPickupLocation
								locations={locations}
								location={location}
								setLocation={setLocation}
								text={selectPickupLocation}
							/>
						) : null}
						{_.size(accounts) > 0 ? (
							<SelectLinkedAccount
								activeAccount={activeAccount}
								accounts={accounts}
								setActiveAccount={setActiveAccount}
								isPlacingHold={isPlacingHold}
								user={user}
								language={language}
							/>
						) : null}
					</Modal.Body>
					<Modal.Footer>
						<Button.Group space={2} size="md">
							<Button
								colorScheme="muted"
								variant="outline"
								onPress={() => {
									setShowModal(false);
									setLoading(false);
								}}>
								{getTermFromDictionary(language, 'close_window')}
							</Button>
							<Button
								isLoading={loading}
								isLoadingText={isPlacingHold ? getTermFromDictionary(language, 'placing_hold', true) : getTermFromDictionary(language, 'checking_out', true)}
								onPress={async () => {
									setLoading(true);
									await completeAction(id, action, activeAccount, '', '', location, library.baseUrl, volume, holdType, holdNotificationPreferences).then(async (result) => {
										setResponse(result);
										setShowModal(false);
										if(result) {
											setResponseIsOpen(true);
											if(result.success) {
												await refreshProfile(library.baseUrl).then((profile) => {
													updateUser(profile);
												});
											}
										}
									});
									setLoading(false);
								}}>
								{title}
							</Button>
						</Button.Group>
					</Modal.Footer>
				</Modal.Content>
			</Modal>
		</>
	)
}

function getNotificationPreference(haystack, needle) {
	if(_.includes(haystack, needle)) {
		return true;
	}
	return false;
}