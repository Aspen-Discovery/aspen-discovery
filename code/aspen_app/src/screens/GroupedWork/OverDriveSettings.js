import {
  Button,
  Checkbox,
  FormControl,
  Input,
  Modal,
  Stack,
} from "native-base";
import React from "react";

// custom components and helper files
import { translate } from "../../translations/translations";
import { updateOverDriveEmail } from "../../util/accountActions";

export const GetOverDriveSettings = (props) => {
  const {
    promptTitle,
    promptItemId,
    promptSource,
    promptPatronId,
    promptForOverdriveEmail,
    libraryUrl,
    showOverDriveSettings,
    handleOverDriveSettings,
    showAlert,
    setEmail,
    setRememberPrompt,
    overdriveEmail,
  } = props;

  return (
    <Modal
      isOpen={showOverDriveSettings}
      onClose={() => handleOverDriveSettings(false)}
      avoidKeyboard
      closeOnOverlayClick={false}
    >
      <Modal.Content>
        <Modal.CloseButton />
        <Modal.Header>{promptTitle}</Modal.Header>
        <Modal.Body mt={4}>
          <FormControl>
            <Stack>
              <FormControl.Label>
                {translate("overdrive.email_field")}
              </FormControl.Label>
              <Input
                autoCapitalize="none"
                autoCorrect={false}
                id="overdriveEmail"
                onChangeText={(text) => setEmail(text)}
              />
              <Checkbox
                value="yes"
                my={2}
                id="promptForOverdriveEmail"
                onChange={(isSelected) => setRememberPrompt(isSelected)}
              >
                {translate("user_profile.remember_settings")}
              </Checkbox>
            </Stack>
          </FormControl>
        </Modal.Body>
        <Modal.Footer>
          <Button.Group space={2} size="md">
            <Button
              colorScheme="primary"
              variant="ghost"
              onPress={() => handleOverDriveSettings(false)}
            >
              {translate("general.close_window")}
            </Button>
            <Button
              onPress={async () => {
                await updateOverDriveEmail(
                  promptItemId,
                  promptSource,
                  promptPatronId,
                  overdriveEmail,
                  promptForOverdriveEmail,
                  libraryUrl
                ).then((response) => {
                  showAlert(response);
                });
              }}
            >
              {translate("holds.place_hold")}
            </Button>
          </Button.Group>
        </Modal.Footer>
      </Modal.Content>
    </Modal>
  );
};