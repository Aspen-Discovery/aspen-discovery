import { MaterialIcons } from "@expo/vector-icons";
import { Button, Center, FlatList, Icon, Modal } from "native-base";
import React from "react";

// custom components and helper files
import { translate } from "../../translations/translations";

export const SelectYourLibrary = (props) => {
  const libraryName = props.libraryName;
  const renderListItem = props.renderListItem;
  return (
    <Center>
      <Button
        colorScheme="primary"
        m={5}
        onPress={() => props.handleModal(true)}
        size="md"
        startIcon={<Icon as={MaterialIcons} name="place" size={5} />}
      >
        {libraryName ? libraryName : translate("login.select_your_library")}
      </Button>
      <Modal
        isOpen={props.showModal}
        onClose={() => props.handleModal(false)}
        size="lg"
        avoidKeyboard
      >
        <Modal.Content bg="white" _dark={{ bg: "coolGray.800" }} maxH="350">
          <Modal.CloseButton />
          <Modal.Header>{translate("login.find_your_library")}</Modal.Header>

          <FlatList
            data={props.uniqueLibraries}
            stickyHeaderIndices={[0]}
            renderItem={({ item }) => renderListItem(item)}
            keyExtractor={(item, index) => index.toString()}
            ListHeaderComponent={props.renderListHeader}
            refreshing={props.isRefreshing}
            extraData={props.extraData}
          />
        </Modal.Content>
      </Modal>
    </Center>
  );
};