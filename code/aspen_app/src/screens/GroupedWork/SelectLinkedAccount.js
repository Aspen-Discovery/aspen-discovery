import { Button, Center, Modal, FormControl, Select, Radio, Heading, CheckIcon, AlertDialog } from 'native-base';
import React from 'react';
import { useQuery } from '@tanstack/react-query';
import { translate } from '../../translations/translations';
import { completeAction } from './Record';
import {HoldsContext, LibrarySystemContext, UserContext} from '../../context/initialContext';
import {refreshProfile} from '../../util/api/user';
import _ from 'lodash';
import {reloadHolds} from '../../util/loadPatron';
import {navigate, navigateStack} from '../../helpers/RootNavigator';
import {SelectVolume} from './SelectVolume';

const SelectLinkedAccount = (props) => {
     const { id, action, title, volumeInfo, prevRoute, isEContent } = props;
     const [loading, setLoading] = React.useState(false);
     const [showModal, setShowModal] = React.useState(false);

     const [isOpen, setIsOpen] = React.useState(false);
     const onClose = () => setIsOpen(false);
     const cancelRef = React.useRef(null);
     const [response, setResponse] = React.useState('');

     const { user, updateUser, accounts, locations } = React.useContext(UserContext);
     const { library } = React.useContext(LibrarySystemContext);
     const { updateHolds } = React.useContext(HoldsContext);

     let shouldDisplayVolumes = false;
     let typeOfHold = 'default';
     let promptForHoldType = false;

     if(volumeInfo.numItemsWithVolumes > 0) {
          typeOfHold = 'item';
          shouldDisplayVolumes = true;
          promptForHoldType = true;

          if(volumeInfo.majorityOfItemsHaveVolumes) {
               typeOfHold = 'volume';
          }

          if (_.isEmpty(volumeInfo.hasItemsWithoutVolumes) || !volumeInfo.hasItemsWithoutVolumes === false) {
               typeOfHold = 'volume';
               promptForHoldType = false;
          }
     }

     const [holdType, setHoldType] = React.useState(typeOfHold);
     const [volume, setVolume] = React.useState(null);

     const userPickupLocation = _.filter(locations, { 'locationId': user.pickupLocationId });
     let pickupLocation = '';
     if(!_.isUndefined(userPickupLocation && !_.isEmpty(userPickupLocation))) {
          pickupLocation = userPickupLocation[0];
          if(_.isObject(pickupLocation)) {
               pickupLocation = pickupLocation.code;
          }
     }

     const [location, setLocation] = React.useState(pickupLocation);

     const [activeAccount, setActiveAccount] = React.useState(user.id);
     const availableAccounts = Object.values(accounts);

     const handleNavigation = (action) => {
          if (prevRoute === 'Discovery' || prevRoute === 'SearchResults') {
               if (action.includes('Checkouts')) {
                    navigateStack('AccountScreenTab', 'MyCheckouts', {});
               } else {
                    navigateStack('AccountScreenTab', 'MyHolds', {});
               }
          } else {
               if (action.includes('Checkouts')) {
                    navigate('MyCheckouts', {});
               } else {
                    navigate('MyHolds', {});
               }
          }
     };

     return (
          <Center>
               <Button
                    size="md"
                    colorScheme="primary"
                    variant="solid"
                    _text={{
                         padding: 0,
                         textAlign: 'center',
                    }}
                    onPress={() => setShowModal(true)}>
                    {title}
               </Button>
               <Modal isOpen={showModal} onClose={() => setShowModal(false)} size="lg">
                    <Modal.Content maxWidth="90%" bg="white" _dark={{ bg: 'coolGray.800' }}>
                         <Modal.CloseButton />
                         <Modal.Header>
                              <Heading size="md">{translate('grouped_work.checkout_options')}</Heading>
                         </Modal.Header>
                         <Modal.Body>
                              {shouldDisplayVolumes ? (
                                  <SelectVolume id={id} holdType={holdType} setHoldType={setHoldType} volume={volume} setVolume={setVolume} promptForHoldType={promptForHoldType}/>
                              ) : null}
                              {_.size(locations) > 1 && !isEContent ? (
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
                              ) : null}
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
                              <Button.Group space={2} size="md">
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
                                        isLoadingText="Placing hold..."
                                        onPress={async () => {
                                             setLoading(true);
                                             await completeAction(id, action, activeAccount, null, null, location, library.baseUrl, volume, holdType).then(async (response) => {
                                                  setResponse(response);
                                                  if(response.success) {
                                                       await reloadHolds(library.baseUrl).then((result) => {
                                                            updateHolds(result);
                                                       })
                                                       await refreshProfile(library.baseUrl).then((result) => {
                                                            updateUser(result);
                                                       });
                                                  }
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
                                             <AlertDialog.Header>{response?.title}</AlertDialog.Header>
                                             <AlertDialog.Body>{response?.message}</AlertDialog.Body>
                                             <AlertDialog.Footer>
                                                  <Button.Group space={3}>
                                                       {response?.action ? <Button onPress={() => handleNavigation(response.action)}>{response.action}</Button> : null}
                                                       <Button variant="outline" colorScheme="primary" ref={cancelRef} onPress={() => setIsOpen(false)}>
                                                            {translate('general.button_ok')}
                                                       </Button>
                                                  </Button.Group>
                                             </AlertDialog.Footer>
                                        </AlertDialog.Content>
                                   </AlertDialog>
                              </Center>
                         </Modal.Footer>
                    </Modal.Content>
               </Modal>
          </Center>
     );
};

export default SelectLinkedAccount;