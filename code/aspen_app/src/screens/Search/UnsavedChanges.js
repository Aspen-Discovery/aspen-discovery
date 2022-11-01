import React from 'react';
import {AlertDialog, Button, Center, ChevronLeftIcon, CloseIcon, Pressable} from 'native-base';
import {CommonActions, useNavigation} from '@react-navigation/native';
import {SEARCH} from '../../util/search';

export const UnsavedChangesBack = (props) => {
	const {updateSearch} = props;
	const navigation = useNavigation();
	const [isOpen, setIsOpen] = React.useState(false);
	const onClose = () => setIsOpen(false);
	const cancelRef = React.useRef(null);

	function getStatus() {
		const hasPendingChanges = SEARCH.hasPendingChanges;
		if (hasPendingChanges) {
			// if pending changes found, pop alert to confirm close
			setIsOpen(true);
		} else {
			// if no pending changes, just close it
			navigation.dispatch(CommonActions.goBack());
		}
	}

	// update parameters, then go to search results screen
	const updateClose = () => {
		updateSearch(false, true);
		SEARCH.hasPendingChanges = false;
	};

	// remove pending parameters, then go back to original search results screen
	const forceClose = () => {
		setIsOpen(false);
		SEARCH.hasPendingChanges = false;
		navigation.getParent().pop();
	};

	return (
			<Center>
				<Pressable onPress={() => getStatus()}><ChevronLeftIcon color="primary.baseContrast"/></Pressable>
				<AlertDialog leastDestructiveRef={cancelRef} isOpen={isOpen} onClose={onClose}>
					<AlertDialog.Content>
						<AlertDialog.CloseButton/>
						<AlertDialog.Header>Unsaved Changes</AlertDialog.Header>
						<AlertDialog.Body>
							Do you want to save your selections before going back?
						</AlertDialog.Body>
						<AlertDialog.Footer>
							<Button.Group space={2}>
								<Button variant="unstyled" colorScheme="coolGray" onPress={updateClose} ref={cancelRef}>
									Update filters
								</Button>
								<Button colorScheme="danger" onPress={forceClose}>
									Continue anyway
								</Button>
							</Button.Group>
						</AlertDialog.Footer>
					</AlertDialog.Content>
				</AlertDialog>
			</Center>
	);
};

export const UnsavedChangesExit = (props) => {
	const {updateSearch} = props;
	const navigation = useNavigation();
	const [isOpen, setIsOpen] = React.useState(false);
	const onClose = () => setIsOpen(false);
	const cancelRef = React.useRef(null);

	function getStatus() {
		const hasPendingChanges = SEARCH.hasPendingChanges;
		if (hasPendingChanges) {
			// if pending changes found, pop alert to confirm close
			setIsOpen(true);
		} else {
			// if no pending changes, just close it
			navigation.dispatch(CommonActions.goBack());
		}
	}

	// update parameters, then go to search results screen
	const updateClose = () => {
		updateSearch(false);
		SEARCH.hasPendingChanges = false;
	};

	// remove pending parameters, then go back to original search results screen
	const forceClose = () => {
		setIsOpen(false);
		SEARCH.hasPendingChanges = false;
		navigation.getParent().pop();
	};

	return (
			<Center>
				<Pressable onPress={() => getStatus()}><CloseIcon color="primary.baseContrast"/></Pressable>
				<AlertDialog leastDestructiveRef={cancelRef} isOpen={isOpen} onClose={onClose}>
					<AlertDialog.Content>
						<AlertDialog.CloseButton/>
						<AlertDialog.Header>Unsaved changes</AlertDialog.Header>
						<AlertDialog.Body>
							Do you want to save your selections and view updated search results?
						</AlertDialog.Body>
						<AlertDialog.Footer>
							<Button.Group space={2}>
								<Button variant="unstyled" colorScheme="coolGray" onPress={updateClose} ref={cancelRef}>
									Update search
								</Button>
								<Button colorScheme="danger" onPress={forceClose}>
									Continue anyway
								</Button>
							</Button.Group>
						</AlertDialog.Footer>
					</AlertDialog.Content>
				</AlertDialog>
			</Center>
	);
};