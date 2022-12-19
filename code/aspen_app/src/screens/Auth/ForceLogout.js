import { AlertDialog, Button, Center } from 'native-base';
import React from 'react';

import {AuthContext} from '../../components/navigation';

export const ForceLogout = () => {
	const { signOut } = React.useContext(AuthContext);
	const [isOpen, setIsOpen] = React.useState(true);
	const onClose = () => setIsOpen(false);
	const cancelRef = React.useRef(null);

	return (
		<Center>
			<AlertDialog leastDestructiveRef={cancelRef} isOpen={isOpen} onClose={onClose}>
				<AlertDialog.Content>
					<AlertDialog.Header>Error</AlertDialog.Header>
					<AlertDialog.Body>There was a problem retrieving your previous session. Please log in again.</AlertDialog.Body>
					<AlertDialog.Footer>
						<Button.Group space={3}>
							<Button colorScheme="primary" onPress={signOut} ref={cancelRef}>
								OK
							</Button>
						</Button.Group>
					</AlertDialog.Footer>
				</AlertDialog.Content>
			</AlertDialog>
		</Center>
	);
};