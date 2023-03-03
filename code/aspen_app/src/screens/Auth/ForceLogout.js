import { AlertDialog, Button, Center } from 'native-base';
import React from 'react';

import {AuthContext} from '../../components/navigation';
import {translate} from '../../translations/translations';

export const ForceLogout = () => {
	const { signOut } = React.useContext(AuthContext);
	const [isOpen, setIsOpen] = React.useState(true);
	const onClose = () => setIsOpen(false);
	const cancelRef = React.useRef(null);

	return (
		<Center>
			<AlertDialog leastDestructiveRef={cancelRef} isOpen={isOpen} onClose={onClose}>
				<AlertDialog.Content>
					<AlertDialog.Header>{translate('error.title')}</AlertDialog.Header>
					<AlertDialog.Body>{translate('error.invalid_session')}</AlertDialog.Body>
					<AlertDialog.Footer>
						<Button.Group space={3}>
							<Button colorScheme="primary" onPress={signOut} ref={cancelRef}>
								{translate('general.button_ok')}
							</Button>
						</Button.Group>
					</AlertDialog.Footer>
				</AlertDialog.Content>
			</AlertDialog>
		</Center>
	);
};