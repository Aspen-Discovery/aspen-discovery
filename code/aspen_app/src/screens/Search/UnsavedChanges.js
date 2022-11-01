import React from 'react';
import {AlertDialog, Button, Center, ChevronLeftIcon, CloseIcon, Pressable} from 'native-base';
import {CommonActions, useNavigation} from '@react-navigation/native';
import {SEARCH} from '../../util/search';
import {translate} from '../../translations/translations';

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
						<AlertDialog.Header>{translate('filters.unsaved_changes')}</AlertDialog.Header>
						<AlertDialog.Body>
							{translate('filters.unsaved_changes_body_back')}
						</AlertDialog.Body>
						<AlertDialog.Footer>
							<Button.Group space={2}>
								<Button variant="unstyled" colorScheme="coolGray" onPress={updateClose} ref={cancelRef}>
									{translate('filters.update_filters')}
								</Button>
								<Button colorScheme="danger" onPress={forceClose}>
									{translate('filters.continue_anyway')}
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
						<AlertDialog.Header>{translate('filters.unsaved_changes')}</AlertDialog.Header>
						<AlertDialog.Body>
							{translate('filers.unsaved_changes_body_exit')}
						</AlertDialog.Body>
						<AlertDialog.Footer>
							<Button.Group space={2}>
								<Button variant="unstyled" colorScheme="coolGray" onPress={updateClose} ref={cancelRef}>
									{translate('filters.update_filters')}
								</Button>
								<Button colorScheme="danger" onPress={forceClose}>
									{translate('filters.continue_anyway')}
								</Button>
							</Button.Group>
						</AlertDialog.Footer>
					</AlertDialog.Content>
				</AlertDialog>
			</Center>
	);
};