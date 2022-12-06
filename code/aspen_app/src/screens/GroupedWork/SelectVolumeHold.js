import { Button, FormControl, Modal, Select, CheckIcon, Radio } from 'native-base';
import React, { useState } from 'react';

import { translate } from '../../translations/translations';
import { completeAction } from './Record';
import { refreshProfile, reloadProfile } from '../../util/api/user';
import { LibrarySystemContext, UserContext } from '../../context/initialContext';

const SelectVolumeHold = (props) => {
     const { label, action, record, patron, showAlert, libraryUrl, linkedAccounts, linkedAccountsCount, user, volumes, updateProfile, majorityOfItemsHaveVolumes, hasItemsWithoutVolumes, volumeCount } = props;
     const [loading, setLoading] = React.useState(false);
     const [showModal, setShowModal] = useState(false);
     const [volume, setVolume] = React.useState('');
     const { updateUser } = React.useContext(UserContext);
     const { library } = React.useContext(LibrarySystemContext);

     let typeOfHold = 'bib';
     if (majorityOfItemsHaveVolumes) {
          typeOfHold = 'volume';
     }

     let shouldAskHoldType = false;
     if (!majorityOfItemsHaveVolumes && volumeCount >= 1) {
          shouldAskHoldType = true;
     }

     const [holdType, setHoldType] = React.useState(typeOfHold);
     const [activeAccount, setActiveAccount] = React.useState(user.id);

     const availableAccounts = Object.values(linkedAccounts);

     return (
          <>
               <Button onPress={() => setShowModal(true)} colorScheme="primary" size="md">
                    {label}
               </Button>
               <Modal isOpen={showModal} onClose={() => setShowModal(false)} closeOnOverlayClick={false}>
                    <Modal.Content>
                         <Modal.CloseButton />
                         <Modal.Header>{label}</Modal.Header>
                         <Modal.Body>
                              {shouldAskHoldType ? (
                                   <Radio.Group
                                        name="holdTypeGroup"
                                        defaultValue={holdType}
                                        value={holdType}
                                        onChange={(nextValue) => {
                                             setHoldType(nextValue);
                                        }}
                                        accessibilityLabel="">
                                        <Radio value="bib" my={1} size="sm">
                                             {translate('grouped_work.first_available')}
                                        </Radio>
                                        <Radio value="volume" my={1} size="sm">
                                             {translate('grouped_work.specific_volume')}
                                        </Radio>
                                   </Radio.Group>
                              ) : null}
                              {linkedAccountsCount > 0 ? (
                                   <FormControl>
                                        <FormControl.Label>{translate('linked_accounts.place_hold_for_account')}</FormControl.Label>
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
                                             <Select.Item label={user.displayName} value={patron} />
                                             {availableAccounts.map((item, index) => {
                                                  return <Select.Item label={item.displayName} value={item.id} key={index} />;
                                             })}
                                        </Select>
                                   </FormControl>
                              ) : null}
                              {holdType === 'volume' ? (
                                   <FormControl>
                                        <FormControl.Label>{translate('grouped_work.select_volume')}</FormControl.Label>
                                        <Select
                                             name="volumeForHold"
                                             selectedValue={volume}
                                             minWidth="200"
                                             accessibilityLabel="Select a Volume"
                                             _selectedItem={{
                                                  bg: 'tertiary.300',
                                                  endIcon: <CheckIcon size="5" />,
                                             }}
                                             mt={1}
                                             mb={2}
                                             onValueChange={(itemValue) => setVolume(itemValue)}>
                                             {volumes.map((item, index) => {
                                                  return <Select.Item label={item.displayLabel} value={item.volumeId} key={index} />;
                                             })}
                                        </Select>
                                   </FormControl>
                              ) : null}
                         </Modal.Body>
                         <Modal.Footer>
                              <Button.Group space={2} size="md">
                                   <Button
                                        colorScheme="muted"
                                        variant="outline"
                                        onPress={() => {
                                             setShowModal(false);
                                             setLoading(false);
                                        }}>
                                        {translate('general.close_window')}
                                   </Button>
                                   <Button
                                        isLoading={loading}
                                        isLoadingText="Placing hold..."
                                        onPress={async () => {
                                             setLoading(true);
                                             await completeAction(record, action, activeAccount, '', '', '', libraryUrl, volume, holdType).then(async (response) => {
                                                  updateProfile();
                                                  await refreshProfile(library.baseUrl).then((result) => {
                                                       updateUser(result);
                                                  });
                                                  setLoading(false);
                                                  setShowModal(false);
                                                  showAlert(response);
                                             });
                                             setShowModal(false);
                                        }}>
                                        {label}
                                   </Button>
                              </Button.Group>
                         </Modal.Footer>
                    </Modal.Content>
               </Modal>
          </>
     );
};

export default SelectVolumeHold;