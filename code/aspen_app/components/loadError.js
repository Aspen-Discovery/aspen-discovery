import React from "react";
import { Alert } from "react-native";
import { Center, HStack, Icon, Heading, Button, Text, Toast, AlertDialog } from "native-base";
import { MaterialIcons, Entypo, Ionicons } from "@expo/vector-icons";

export function loadError(error, reloadAction) {
    return (
        <Center flex={1}>
            <HStack>
                <Icon as={MaterialIcons} name="error" size="md" mt={.5} mr={1} color="error.500" />
                <Heading color="error.500" mb={2}>Error</Heading>
            </HStack>
            <Text bold w="75%" textAlign="center">There was an error loading results from the library.</Text>
            {reloadAction ?
                <Button
                    mt={5}
                    colorScheme="primary"
                    onPress={reloadAction}
                    startIcon={<Icon as={MaterialIcons} name="refresh" size={5} />}
                >
                    Reload
                </Button>
             : null}
            <Text fontSize="xs" w="75%" mt={5} color="muted.500" textAlign="center">ERROR: {error}</Text>
        </Center>
    );
}

export function badServerConnectionToast() {
    return (
        Toast.show({
            title: "Server connection error",
            description: "We're unable to connect to the library. Please try again later.",
            status: "error",
            isClosable: true,
            duration: 5000,
            accessibilityAnnouncement: "We're unable to connect to the library. Please try again later.",
            zIndex: 9999,
            placement: "top"
        })
    );
}

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

export function popAlertNative(title, message, action = null) {

    Alert.alert(
      title,
      message,
      [
        { text: "OK", onPress: () => console.log("OK Pressed") }
      ]
    );

}

export const PopAlert = (props) => {

  const { data } = props;

  console.log("popAlert:");
  console.log(props.data);

  const [isOpen, setIsOpen] = React.useState(false);
  const onClose = () => setIsOpen(false);
  const cancelRef = React.useRef();
  return (
    <Center>
      <AlertDialog
        leastDestructiveRef={cancelRef}
        isOpen={isOpen}
        onClose={onClose}
        motionPreset={"fade"}
      >
        <AlertDialog.Content>
          <AlertDialog.Header fontSize="lg" fontWeight="bold">
            Delete Customer
          </AlertDialog.Header>
          <AlertDialog.Body>
            Are you sure? You can't undo this action afterwards.
          </AlertDialog.Body>
          <AlertDialog.Footer>
            <Button ref={cancelRef} onPress={onClose}>
              Cancel
            </Button>
            <Button colorScheme="red" onPress={onClose} ml={3}>
              Delete
            </Button>
          </AlertDialog.Footer>
        </AlertDialog.Content>
      </AlertDialog>
    </Center>
  );


}

export const AlertDialogComponent = (props) => {

  console.log("AlertDialogComponent:");
  console.log(props);

  const inputRef = React.useRef();
  const { status, message, title } = props;
  const [isOpen, setIsOpen] = React.useState(false);
  const onClose = () => setIsOpen(false);
  const cancelRef = React.useRef();

  return (
    <Center>
      <AlertDialog
        leastDestructiveRef={cancelRef}
        isOpen={isOpen}
      >
        <AlertDialog.Content>
          <AlertDialog.Header fontSize="lg" fontWeight="bold">
            {title}
          </AlertDialog.Header>
          <AlertDialog.Body>
            {message}
          </AlertDialog.Body>
          <AlertDialog.Footer>
            <Button ref={cancelRef} onPress={onClose}>
              Cancel
            </Button>
            <Button colorScheme="red" onPress={onClose} ml={3}>
              Delete
            </Button>
          </AlertDialog.Footer>
        </AlertDialog.Content>
      </AlertDialog>
    </Center>
  );
}