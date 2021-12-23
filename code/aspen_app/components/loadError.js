import React from "react";
import {Alert, Button, Center, Heading, HStack, Icon, ScrollView, Text, Toast, VStack} from "native-base";
import {MaterialIcons} from "@expo/vector-icons";

// custom components and helper files
import {translate} from "../util/translations";

/**
 * Catch an error and display it to the user
 * <ul>
 *     <li>error - The error array that contains title and message objects</li>
 *     <li>reloadAction - The name of the component that would result in a reload of the screen (optional)</li>
 * </ul>
 * @param {string} error
 * @param {string} reloadAction
 **/
export function loadError(error, reloadAction) {
	return (
		<Center flex={1}>
			<HStack>
				<Icon as={MaterialIcons} name="error" size="md" mt={.5} mr={1} color="error.500"/>
				<Heading color="error.500" mb={2}>{translate('error.title')}</Heading>
			</HStack>
			<Text bold w="75%" textAlign="center">{translate('error.message')}</Text>
			{reloadAction ?
				<Button
					mt={5}
					colorScheme="primary"
					onPress={reloadAction}
					startIcon={<Icon as={MaterialIcons} name="refresh" size={5}/>}
				>
					{translate('error.reload_button')}
				</Button>
				: null}
			<Text fontSize="xs" w="75%" mt={5} color="muted.500" textAlign="center">ERROR: {error}</Text>
		</Center>
	);
}

/**
 * Display a toast if Aspen LiDA is unable to connect to the server when fetching data
 **/
export function badServerConnectionToast() {
	return (
		Toast.show({
			title: translate('error.no_server_connection'),
			description: translate('error.no_library_connection'),
			status: "error",
			isClosable: true,
			duration: 5000,
			accessibilityAnnouncement: translate('error.no_library_connection'),
			zIndex: 9999,
			placement: "top"
		})
	);
}

/**
 * <b>Toast: low priority messages</b>
 *
 * <ul>
 * <li>Use Case: A brief error or update regarding an app process</li>
 * <li>User Action: Optional and minimal</li>
 * <li>Closes On: Disappears automatically, should be brief</li>
 * <li>Example Use: Bad API fetches or server connection troubles/timeouts</li>
 * </ul>
 * - - - -
 * Available statuses:
 * <ul>
 * <li>Success</li>
 * <li>Error</li>
 * <li>Info</li>
 * <li>Warning</li>
 * </ul>
 * @param {string} title
 * @param {string} description
 * @param {string} status
 **/
export function popToast(title, description, status) {
	return (
		Toast.show({
			title: title,
			description: description,
			status: status,
			isClosable: true,
			duration: 5000,
			accessibilityAnnouncement: description,
			zIndex: 9999,
			placement: "top"
		})
	);
}

/**
 * <b>Alert: prominent, medium priority messages</b>
 *
 * <ul>
 * <li>Use Case: An error or notice occurs because of an action that a user has taken</li>
 * <li>User Action: Optional, buttons do not need to be displayed</li>
 * <li>Closes On: When dismissed or the state that caused the alert is resolved</li>
 * <li>Example Use: Checkout renewal, freeze or thaw hold, or hold cancelled</li>
 * </ul>
 * - - - -
 * Available statuses:
 * <ul>
 * <li>Success</li>
 * <li>Error</li>
 * <li>Info</li>
 * <li>Warning</li>
 * </ul>
 * @param {string} title
 * @param {string} description
 * @param {string} status
 **/
export function popAlert(title, description, status) {
	return (
		Toast.show({
			duration: 5000,
			render: () => {
				return (
					<ScrollView px="30" my="15">
						<Alert w="100%" colorScheme={status} status={status} variant="left-accent">
							<VStack space={2} flexShrink={1} w="100%">
								<HStack flexShrink={1} space={2} alignItems="center" justifyContent="space-between">
									<HStack space={2} flexShrink={1} alignItems="center">
										<Alert.Icon/>
										<Alert.Title>
											{title}
										</Alert.Title>
									</HStack>
								</HStack>
								<Alert.Description>
									{description}
								</Alert.Description>
							</VStack>
						</Alert>
					</ScrollView>
				)
			},
		})
	);
}