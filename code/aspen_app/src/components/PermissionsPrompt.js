import React from 'react';
import * as Linking from 'expo-linking';
import {Button, AlertDialog} from 'native-base';
import {LanguageContext} from '../context/initialContext';
import {getTermFromDictionary} from '../translations/TranslationService';

const PermissionsPrompt = (promptTitle, promptBody, dialogToggles) => {
	const {cancelRef, isOpen, onClose} = dialogToggles;
	const { language } = React.useContext(LanguageContext);
	return (
		<AlertDialog leastDestructiveRef={cancelRef} isOpen={isOpen} onClose={onClose}>
			<AlertDialog.Content>
				<AlertDialog.CloseButton />
				<AlertDialog.Header>{getTermFromDictionary(language, promptTitle)}</AlertDialog.Header>
				<AlertDialog.Body>
					{getTermFromDictionary(language, promptBody)}
				</AlertDialog.Body>
				<AlertDialog.Footer>
					<Button.Group space={2}>
						<Button variant="unstyled" colorScheme="coolGray" onPress={onClose} ref={cancelRef}>
							{getTermFromDictionary(language, 'cancel')}
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