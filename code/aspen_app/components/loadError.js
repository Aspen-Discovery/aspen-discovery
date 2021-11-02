import React from "react";
import { ScrollView, Center, HStack, VStack, Icon, CloseIcon, Heading, Button, Text, Toast, Collapse, AlertDialog, Alert, Box, IconButton } from "native-base";
import { MaterialIcons, Entypo, Ionicons } from "@expo/vector-icons";

// custom components and helper files
import { translate } from "../util/translations";

/** status options: success, error, info, warning **/

export function loadError(error, reloadAction) {
    return (
        <Center flex={1}>
            <HStack>
                <Icon as={MaterialIcons} name="error" size="md" mt={.5} mr={1} color="error.500" />
                <Heading color="error.500" mb={2}>{translate('error.title')}</Heading>
            </HStack>
            <Text bold w="75%" textAlign="center">{translate('error.message')}</Text>
            {reloadAction ?
                <Button
                    mt={5}
                    colorScheme="primary"
                    onPress={reloadAction}
                    startIcon={<Icon as={MaterialIcons} name="refresh" size={5} />}
                >
                    {translate('error.reload_button')}
                </Button>
             : null}
            <Text fontSize="xs" w="75%" mt={5} color="muted.500" textAlign="center">ERROR: {error}</Text>
        </Center>
    );
}

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

/***
 *** TOASTS: low priority
 ***
 *** Use: A brief error or update regarding an app process
 *** Action: Optional and minimal
 *** Closes: Disappears automatically, should be brief
 *** Examples: Bad API fetches or server connection troubles/timeouts
 ***
***/

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

/***
 *** ALERTS: prominent, medium priority
 ***
 *** Use: An error or notice occurs because of an action that a user has taken
 *** Action: Optional, buttons do not need to be displayed
 *** Closes: When dismissed or the state that caused the alert is resolved
 *** Examples: Checkout renewal, freeze or thaw hold, or hold cancelled
 ***
***/

export function popAlert(title, description, status) {
  return (
      Toast.show({
        duration: 5000,
        render: () => {
          return (
            <ScrollView px="30" my="15">
                <Alert w="100%" colorScheme={status} status={status} variant="left-accent" >
                    <VStack space={2} flexShrink={1} w="100%">
                        <HStack flexShrink={1} space={2} alignItems="center" justifyContent="space-between" >
                            <HStack space={2} flexShrink={1} alignItems="center">
                                <Alert.Icon />
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



