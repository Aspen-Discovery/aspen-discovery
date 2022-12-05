import { Button, Center, Modal, FormControl, Select, Heading, CheckIcon } from 'native-base';
import React, { useState } from 'react';

import { translate } from '../../translations/translations';
import { completeAction } from './Record';
import { UserContext } from '../../context/initialContext';

const SelectLinkedAccount = (props) => {
     const { linkedAccounts, id, action, libraryUrl, showAlert, updateProfile } = props;
     const [loading, setLoading] = React.useState(false);
     const [showModal, setShowModal] = useState(false);
     const { user } = React.useContext(UserContext);

     const [activeAccount, setActiveAccount] = React.useState(user.id);

     const availableAccounts = Object.values(linkedAccounts);

     return (
          <Center>
               <Button onPress={() => setShowModal(true)}>{props.title}</Button>
               <Modal isOpen={showModal} onClose={() => setShowModal(false)} size="full">
                    <Modal.Content maxWidth="90%" bg="white" _dark={{ bg: 'coolGray.800' }}>
                         <Modal.CloseButton />
                         <Modal.Header>
                              <Heading size="md">{translate('grouped_work.checkout_options')}</Heading>
                         </Modal.Header>
                         <Modal.Body>
                              <FormControl pb={5}>
                                   <FormControl.Label>{translate('linked_accounts.checkout_to_account')}</FormControl.Label>
                                   <Select
                                        name="linkedAccount"
                                        selectedValue={activeAccount}
                                        minWidth="200"
                                        accessibilityLabel="Select an account to place hold for"
                                        _selectedItem={{
                                             bg: 'tertiary.300',
                                             endIcon: <CheckIcon size="5" />,
                                        }}
                                        mt={1}
                                        mb={3}
                                        onValueChange={(itemValue) => setActiveAccount(itemValue)}>
                                        <Select.Item label={user.displayName} value={user.id} />
                                        {availableAccounts.map((item, index) => {
                                             return <Select.Item label={item.displayName} value={item.id} key={index} />;
                                        })}
                                   </Select>
                              </FormControl>
                         </Modal.Body>
                         <Modal.Footer>
                              <Button.Group>
                                   <Button
                                        variant="outline"
                                        onPress={() => {
                                             setShowModal(false);
                                             setLoading(false);
                                        }}>
                                        {translate('general.cancel')}
                                   </Button>
                                   <Button
                                        isLoading={loading}
                                        onPress={async () => {
                                             setLoading(true);
                                             await completeAction(id, action, activeAccount, null, null, null, libraryUrl).then((response) => {
                                                  updateProfile();
                                                  showAlert(response);
                                                  setLoading(false);
                                             });
                                             setShowModal(false);
                                        }}>
                                        {translate('grouped_work.checkout_title')}
                                   </Button>
                              </Button.Group>
                         </Modal.Footer>
                    </Modal.Content>
               </Modal>
          </Center>
     );
};

export default SelectLinkedAccount;