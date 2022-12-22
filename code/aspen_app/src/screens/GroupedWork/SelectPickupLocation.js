import _ from 'lodash';
import { Button, FormControl, Modal, Select, CheckIcon, Radio, Stack } from 'native-base';
import React, { useState } from 'react';

import { translate } from '../../translations/translations';
import { completeAction } from './Record';
import { LibraryBranchContext, LibrarySystemContext, UserContext } from '../../context/initialContext';
import { refreshProfile, reloadProfile } from '../../util/api/user';

const SelectPickupLocation = (props) => {
     const { locations, label, action, record, patron, showAlert, libraryUrl, linkedAccounts, linkedAccountsCount, majorityOfItemsHaveVolumes, volumes, updateProfile, hasItemsWithoutVolumes, volumeCount } = props;
     const [loading, setLoading] = React.useState(false);
     const [showModal, setShowModal] = useState(false);
     const [volume, setVolume] = React.useState(null);
     const { user, updateUser } = React.useContext(UserContext);
     const { library } = React.useContext(LibrarySystemContext);

     let typeOfHold = 'item';
     if (majorityOfItemsHaveVolumes) {
          typeOfHold = 'volume';
     }

     let shouldAskHoldType = false;
     if (!majorityOfItemsHaveVolumes && volumeCount >= 1) {
          shouldAskHoldType = true;
     }

     const [holdType, setHoldType] = React.useState(typeOfHold);

     let pickupLocation = _.findIndex(locations, function (o) {
          return o.locationId === user.pickupLocationId;
     });
     pickupLocation = _.nth(locations, pickupLocation);
     pickupLocation = _.get(pickupLocation, 'code', '');
     const [location, setLocation] = React.useState(pickupLocation);
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
                                        <Radio value="item" my={1} size="sm">
                                             {translate('grouped_work.first_available')}
                                        </Radio>
                                        <Radio value="volume" my={1} size="sm">
                                             {translate('grouped_work.specific_volume')}
                                        </Radio>
                                   </Radio.Group>
                              ) : null}
                              {holdType === 'volume' ? (
                                   <FormControl>
                                        <FormControl.Label>{translate('grouped_work.select_volume')}</FormControl.Label>
                                        <Select
                                             name="volumeForHold"
                                             selectedValue={volume}
                                             minWidth="200"
                                             accessibilityLabel="Select a volume"
                                             _selectedItem={{
                                                  bg: 'tertiary.300',
                                                  endIcon: <CheckIcon size="5" />,
                                             }}
                                             mt={1}
                                             mb={3}
                                             onValueChange={(itemValue) => setVolume(itemValue)}>
                                             {volumes.map((volume, index) => {
                                                  return <Select.Item label={volume.displayLabel} value={volume.volumeId} key={index} />;
                                             })}
                                        </Select>
                                   </FormControl>
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
                                             <Select.Item label={user.displayName} value={user.id} />
                                             {availableAccounts.map((account, index) => {
                                                  return <Select.Item label={account.displayName} value={account.id} key={index} />;
                                             })}
                                        </Select>
                                   </FormControl>
                              ) : null}
                              <FormControl>
                                   <FormControl.Label>{translate('pickup_locations.text')}</FormControl.Label>
                                   <Select
                                        name="pickupLocations"
                                        selectedValue={location}
                                        minWidth="200"
                                        accessibilityLabel="Select a Pickup Location"
                                        _selectedItem={{
                                             bg: 'tertiary.300',
                                             endIcon: <CheckIcon size="5" />,
                                        }}
                                        mt={1}
                                        mb={2}
                                        onValueChange={(itemValue) => setLocation(itemValue)}>
                                        {locations.map((location, index) => {
                                             return <Select.Item label={location.name} value={location.code} key={index} />;
                                        })}
                                   </Select>
                              </FormControl>
                         </Modal.Body>
                         <Modal.Footer>
                              <Button.Group space={2} size="md">
                                   <Button colorScheme="muted" variant="outline" onPress={() => setShowModal(false)}>
                                        {translate('general.close_window')}
                                   </Button>
                                   <Button
                                        isLoading={loading}
                                        isLoadingText="Placing hold..."
                                        onPress={async () => {
                                             setLoading(true);
                                             await completeAction(record, action, activeAccount, '', '', location, library.baseUrl, volume, holdType).then(async (response) => {
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

export default SelectPickupLocation;