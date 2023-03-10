import React from 'react';
import * as Linking from 'expo-linking';
import {Button, AlertDialog} from 'native-base';
import {LanguageContext} from '../context/initialContext';
import {getTermFromDictionary} from '../translations/TranslationService';

export const PermissionsPrompt = (data) => {
	const {promptTitle, promptBody, setShouldRequestPermissions} = data;
	const { language } = React.useContext(LanguageContext);
	const [isOpen, setIsOpen] = React.useState(true);
	const onClose = () => {
		setShouldRequestPermissions(false);
		setIsOpen(false);
	};
	const cancelRef = React.useRef(null);
	return (
		<AlertDialog leastDestructiveRef={cancelRef} isOpen={isOpen} onClose={onClose}>
			<AlertDialog.Content>
				<AlertDialog.Header>{getTermFromDictionary(language, promptTitle)}</AlertDialog.Header>
				<AlertDialog.Body>
					{getTermFromDictionary(language, promptBody)}
				</AlertDialog.Body>
				<AlertDialog.Footer>
					<Button.Group space={2}>
						<Button variant="unstyled" colorScheme="coolGray" onPress={onClose} ref={cancelRef}>
							{getTermFromDictionary(language, 'permissions_cancel')}
						</Button>
						<Button colorScheme="danger" onPress={() => {
							onClose();
							Linking.openSettings();
						}}>
							{getTermFromDictionary(language, 'permissions_update_settings')}
						</Button>
					</Button.Group>
				</AlertDialog.Footer>
			</AlertDialog.Content>
		</AlertDialog>
	)
}