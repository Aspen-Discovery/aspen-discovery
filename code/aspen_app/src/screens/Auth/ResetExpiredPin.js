import React from 'react';
import { Ionicons } from '@expo/vector-icons';
import { AlertDialog, Button, Center, FormControl, Input, Icon, WarningOutlineIcon } from 'native-base';
import _ from 'lodash';
import { create } from 'apisauce';
import {GLOBALS} from '../../util/globals';
import {createAuthTokens, getHeaders} from '../../util/apiAuth';
import {popAlert} from '../../components/loadError';
import { AuthContext } from '../../components/navigation';

export const ResetExpiredPin = (props) => {
	const { signIn } = React.useContext(AuthContext);
	const { resetToken, url, pinValidationRules, setExpiredPin, patronsLibrary } = props;
	const [isOpen, setIsOpen] = React.useState(true);
	const onClose = () => {
		setExpiredPin(false);
		setIsOpen(false);
	};
	const cancelRef = React.useRef(null);

	const [pin, setPin] = React.useState('');
	const [pinConfirmed, setPinConfirmed] = React.useState('');
	const [errors, setErrors] = React.useState({});

	// show:hide data from password fields
	const [showPin, setShowPin] = React.useState(false);
	const [showPinConfirmed, setShowPinConfirmed] = React.useState(false);
	const toggleShowPin = () => setShowPin(!showPin);
	const toggleShowPinConfirmed = () => setShowPinConfirmed(!showPinConfirmed);

	const pinConfirmedRef = React.useRef();

	const validatePin = () => {
		if(pin === undefined) {
			setErrors({...errors, pin: 'Pin is required'})
			return false;
		} else if(_.size(pin) < pinValidationRules.minLength) {
			setErrors({...errors, pin: 'Pin should be greater than ' + pinValidationRules.minLength + ' characters'})
			return false;
		} else if (_.size(pin) > pinValidationRules.maxLength) {
			setErrors({...errors, pin: 'Pin should be less than ' + pinValidationRules.maxLength + ' characters'})
			return false;
		} else if (pin !== pinConfirmed) {
			setErrors({...errors, pin: 'Pins should match.'})
			return false;
		}
		setErrors({});
		return true;
	}

	const validatePinConfirmed = () => {
		if(pinConfirmed === undefined) {
			setErrors({...errors, pinConfirmed: 'Pin is required'})
			return false;
		} else if(_.size(pinConfirmed) < pinValidationRules.minLength) {
			setErrors({...errors, pinConfirmed: 'Pin should be greater than ' + pinValidationRules.minLength + ' characters'})
			return false;
		} else if (_.size(pinConfirmed) > pinValidationRules.maxLength) {
			setErrors({...errors, pinConfirmed: 'Pin should be less than ' + pinValidationRules.maxLength + ' characters'})
			return false;
		} else if (pinConfirmed !== pin) {
			setErrors({...errors, pinConfirmed: 'Pins should match.'})
			return false;
		}
		setErrors({});
		return true;
	}

	const updatePIN = async () => {
		if(validatePin() && validatePinConfirmed()) {
			await resetExpiredPin(pin, pinConfirmed, resetToken, url).then((result) => {
				if(result.success) {
					popAlert('Updated', result.message, 'success')
					signIn(patronsLibrary);
					setExpiredPin(false);
					setIsOpen(false);
				} else {
					popAlert('Error', result.message ?? 'Unable to update pin', 'error')
				}
			});
		} else {
			console.log(errors);
		}
	}

	return (
		<Center>
			<AlertDialog leastDestructiveRef={cancelRef} isOpen={isOpen} onClose={onClose} avoidKeyboard>
				<AlertDialog.Content>
					<AlertDialog.Header>Reset My PIN</AlertDialog.Header>
					<AlertDialog.CloseButton/>
					<AlertDialog.Body>Your PIN has expired, enter a new PIN below.
						<FormControl isRequired isInvalid={'pin' in errors}>
							<FormControl.Label
								_text={{
									fontSize: 'sm',
									fontWeight: 600,
								}}>
								New PIN/Password
							</FormControl.Label>
							<Input
								keyboardType={pinValidationRules.onlyDigitsAllowed === '1' ? 'numeric' : 'default'}
								maxLength={pinValidationRules.maxLength}
								autoCapitalize="none"
								size="xl"
								autoCorrect={false}
								type={showPin ? 'text' : 'password'}
								variant="filled"
								id="pin"
								returnKeyType="next"
								textContentType="password"
								required
								onChangeText={(text) => setPin(text)}
								InputRightElement={<Icon as={<Ionicons name={showPin ? 'eye-outline' : 'eye-off-outline'} />} size="md" ml={1} mr={3} onPress={toggleShowPin} roundedLeft={0} roundedRight="md" />}
								onSubmitEditing={() => pinConfirmedRef.current.focus()}
								blurOnSubmit={false}
							/>
							{'pin' in errors? <FormControl.ErrorMessage leftIcon={<WarningOutlineIcon size="xs" />}>
									{errors.pin}
							</FormControl.ErrorMessage> : null}
						</FormControl>
						<FormControl isRequired isInvalid={'pinConfirmed' in errors}>
							<FormControl.Label
								_text={{
									fontSize: 'sm',
									fontWeight: 600,
								}}>
								Re-enter New PIN/Password
							</FormControl.Label>
							<Input
								keyboardType={pinValidationRules.onlyDigitsAllowed === '1' ? 'numeric' : 'default'}
								maxLength={pinValidationRules.maxLength}
								autoCapitalize="none"
								size="xl"
								autoCorrect={false}
								type={showPinConfirmed ? 'text' : 'password'}
								variant="filled"
								id="pinConfirmed"
								enterKeyHint="send"
								textContentType="password"
								required
								onChangeText={(text) => setPinConfirmed(text)}
								InputRightElement={<Icon as={<Ionicons name={showPinConfirmed ? 'eye-outline' : 'eye-off-outline'} />} size="md" ml={1} mr={3} onPress={toggleShowPinConfirmed} roundedLeft={0} roundedRight="md" />}
								blurOnSubmit={false}
							/>
							{'pinConfirmed' in errors? <FormControl.ErrorMessage leftIcon={<WarningOutlineIcon size="xs" />}>
								{errors.pinConfirmed}
							</FormControl.ErrorMessage> : null}
						</FormControl>
					</AlertDialog.Body>

					<AlertDialog.Footer>
						<Button.Group space={3}>
							<Button onPress={onClose}>Cancel</Button>
							<Button colorScheme="primary" onPress={() => updatePIN()}>
								Update
							</Button>
						</Button.Group>
					</AlertDialog.Footer>
				</AlertDialog.Content>
			</AlertDialog>
		</Center>
	);
};

async function resetExpiredPin(pin1, pin2, token, url) {
	console.log(url);
	const postBody = new FormData();
	postBody.append('pin1', pin1);
	postBody.append('pin2', pin2);
	postBody.append('token', token);
	const discovery = create({
		baseURL: url + '/API',
		timeout: GLOBALS.timeoutFast,
		headers: getHeaders(true),
		auth: createAuthTokens(),
	});
	const results = await discovery.post('/UserAPI?method=resetExpiredPin', postBody);
	console.log(results);
	if (results.ok) {
		return results.data.result;
	} else {
		return {
			success: false,
			message: 'Unable to connect to library'
		}
	}
}