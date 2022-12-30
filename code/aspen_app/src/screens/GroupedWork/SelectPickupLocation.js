import _ from 'lodash';
import { Button, FormControl, Modal, Select, CheckIcon, Radio, Heading, AlertDialog, Center } from 'native-base';
import React, { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { translate } from '../../translations/translations';
import { completeAction } from './Record';
import { LibrarySystemContext, UserContext } from '../../context/initialContext';
import { reloadProfile } from '../../util/api/user';
import { getVolumes } from '../../util/api/item';

const SelectPickupLocation = (props) => {
     const { id, action, title, volumeInfo } = props;
     const [loading, setLoading] = React.useState(false);
     const [showModal, setShowModal] = useState(false);
     const [volume, setVolume] = React.useState(null);
     const { user, updateUser, accounts, locations } = React.useContext(UserContext);
     const { library } = React.useContext(LibrarySystemContext);

     const [isOpen, setIsOpen] = React.useState(false);
     const onClose = () => setIsOpen(false);
     const cancelRef = React.useRef(null);
     const [response, setResponse] = React.useState('');

     let typeOfHold = 'item';
     if (volumeInfo.majorityOfItemsHaveVolumes) {
          typeOfHold = 'volume';
     }

     let shouldAskHoldType = false;
     if (!volumeInfo.majorityOfItemsHaveVolumes && volumeInfo.numItemsWithVolumes >= 1) {
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

     let availableAccounts = [];
     if (_.size(accounts > 0)) {
          availableAccounts = Object.values(accounts);
     }

     const { status, data, error, isFetching } = useQuery({
          queryKey: ['volumes', id, library.baseUrl],
          queryFn: () => getVolumes(id, library.baseUrl),
     });

     return (
          <>
               <Button
                    size="md"
                    colorScheme="primary"
                    variant="solid"
                    _text={{
                         padding: 0,
                         textAlign: 'center',
                    }}
                    onPress={() => setShowModal(true)}
                    colorScheme="primary"
                    size="md">
                    {title}
               </Button>
               <Modal isOpen={showModal} onClose={() => setShowModal(false)} closeOnOverlayClick={false}>
                    <Modal.Content maxWidth="90%" bg="white" _dark={{ bg: 'coolGray.800' }}>
                         <Modal.CloseButton />
                         <Modal.Header>
                              <Heading size="md">{translate('grouped_work.checkout_options')}</Heading>
                         </Modal.Header>
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
                                             {data.volumes.map((volume, index) => {
                                                  return <Select.Item label={volume.displayLabel} value={volume.volumeId} key={index} />;
                                             })}
                                        </Select>
                                   </FormControl>
                              ) : null}
                              {_.size(accounts) > 0 ? (
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
                                   <Button variant="outline" onPress={() => setShowModal(false)}>
                                        {translate('general.cancel')}
                                   </Button>
                                   <Button
                                        isLoading={loading}
                                        isLoadingText="Placing hold..."
                                        onPress={async () => {
                                             setLoading(true);
                                             await completeAction(id, action, activeAccount, null, null, location, library.baseUrl, volume, holdType).then(async (response) => {
                                                  setResponse(response);
                                                  await reloadProfile(library.baseUrl).then((result) => {
                                                       updateUser(result);
                                                  });
                                                  setLoading(false);
                                             });
                                             setShowModal(false);
                                             setIsOpen(true);
                                        }}>
                                        {title}
                                   </Button>
                              </Button.Group>
                              <Center>
                                   <AlertDialog leastDestructiveRef={cancelRef} isOpen={isOpen} onClose={onClose}>
                                        <AlertDialog.Content>
                                             <AlertDialog.Header>{response?.success ? 'Success' : 'Error'}</AlertDialog.Header>
                                             <AlertDialog.Body>{response?.message}</AlertDialog.Body>
                                             <AlertDialog.Footer>
                                                  <Button.Group space={3}>
                                                       <Button colorScheme="primary" ref={cancelRef} onPress={() => setIsOpen(false)}>
                                                            OK
                                                       </Button>
                                                  </Button.Group>
                                             </AlertDialog.Footer>
                                        </AlertDialog.Content>
                                   </AlertDialog>
                              </Center>
                         </Modal.Footer>
                    </Modal.Content>
               </Modal>
          </>
     );
};

export default SelectPickupLocation;