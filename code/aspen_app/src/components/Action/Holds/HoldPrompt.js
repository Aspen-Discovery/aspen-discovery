import React from 'react';
import {HoldsContext, LibrarySystemContext, UserContext} from '../../../context/initialContext';
import { Button, Modal, Heading, Radio } from 'native-base';
import {translate} from '../../../translations/translations';
import _ from 'lodash';
import {completeAction} from '../../../screens/GroupedWork/Record';
import {refreshProfile} from '../../../util/api/user';
import SelectPickupLocation from './SelectPickupLocation';
import SelectLinkedAccount from './SelectLinkedAccount';
import {SelectVolume} from './SelectVolume';
import {HoldNotificationPreferences} from './HoldNotificationPreferences';

export const HoldPrompt = (props) => {
	const {id, title, action, volumeInfo, prevRoute, isEContent, response, setResponse, responseIsOpen, setResponseIsOpen, onResponseClose, cancelResponseRef} = props;
	const [loading, setLoading] = React.useState(false);
	const [showModal, setShowModal] = React.useState(false);

	const { user, updateUser, accounts, locations } = React.useContext(UserContext);
	const { library } = React.useContext(LibrarySystemContext);
	const { updateHolds } = React.useContext(HoldsContext);

	const isPlacingHold = action.includes('hold');
	let promptForHoldNotifications = user.promptForHoldNotifications ?? false;
	let holdNotificationInfo = user.holdNotificationInfo ?? [];

	const [emailNotification, setEmailNotification] = React.useState(holdNotificationInfo.preferences?.emailNotification ?? 0);
	const [phoneNotification, setPhoneNotification] = React.useState(holdNotificationInfo.preferences?.phoneNotification ?? 0);
	const [smsNotification, setSMSNotification] = React.useState(holdNotificationInfo.preferences?.smsNotification ?? 0);
	const [smsCarrier, setSMSCarrier] = React.useState(holdNotificationInfo.preferences?.smsCarrier ?? '');
	const [smsNumber, setSMSNumber] = React.useState(holdNotificationInfo.preferences?.smsNumber ?? '');

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

	return (
		<>
			<Button onPress={() => setShowModal(true)}>{title}</Button>
			<Modal isOpen={showModal} onClose={() => setShowModal(false)} closeOnOverlayClick={false} size="lg">
				<Modal.Content maxWidth="90%" bg="white" _dark={{ bg: 'coolGray.800' }}>
					<Modal.CloseButton/>
					<Modal.Header><Heading size="md">{isPlacingHold ? translate('grouped_work.hold_options') : translate('grouped_work.checkout_options')}</Heading></Modal.Header>
					<Modal.Body>
						{promptForHoldNotifications ? (
							<HoldNotificationPreferences
								emailNotification={emailNotification}
								setEmailNotification={setEmailNotification}
								phoneNotification={phoneNotification}
								setPhoneNotification={setPhoneNotification}
								smsNotifciation={smsNotification}
								setSMSNotification={setSMSNotification}
								smsCarrier={smsCarrier}
								setSMSCarrier={setSMSCarrier}
								smsNumber={smsNumber}
								setSMSNumber={setSMSNumber}
							/>
						) : null}
						{promptForHoldType || holdType === 'volume' ? (
							<SelectVolume
								id={id}
								volume={volume}
								setVolume={setVolume}
								promptForHoldType={promptForHoldType}
								holdType={holdType}
								setHoldType={setHoldType}
								showModal={showModal}
							/>
						) : null}
						{_.size(locations) > 1 && !isEContent ? (
							<SelectPickupLocation
								locations={locations}
								location={location}
								setLocation={setLocation}
							/>
						) : null}
						{_.size(accounts) > 0 ? (
							<SelectLinkedAccount
								activeAccount={activeAccount}
								accounts={accounts}
								setActiveAccount={setActiveAccount}
								isPlacingHold={isPlacingHold}
								user={user}
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
								{translate('general.close_window')}
							</Button>
							<Button
								isLoading={loading}
								isLoadingText={isPlacingHold ? "Placing hold..." : "Checking out..."}
								onPress={async () => {
									setLoading(true);
									await completeAction(id, action, activeAccount, '', '', location, library.baseUrl, volume, holdType).then(async (result) => {
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