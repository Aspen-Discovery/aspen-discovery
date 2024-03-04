import _ from 'lodash';
import { Button, CheckIcon, FormControl, Heading, Modal, Select } from 'native-base';
import React, { useState } from 'react';
import { Platform } from 'react-native';
import { HoldsContext, LibrarySystemContext, UserContext } from '../../context/initialContext';
import { getTermFromDictionary } from '../../translations/TranslationService';
import { refreshProfile } from '../../util/api/user';
import { completeAction } from '../../util/recordActions';
import { SelectVolume } from './SelectVolume';

const SelectPickupLocation = (props) => {
     const { id, action, title, volumeInfo, prevRoute, response, setResponse, responseIsOpen, setResponseIsOpen, onResponseClose, cancelResponseRef, language } = props;
     const [loading, setLoading] = React.useState(false);
     const [showModal, setShowModal] = useState(false);
     const [volume, setVolume] = React.useState(null);
     const { user, updateUser, accounts, locations } = React.useContext(UserContext);
     const { updateHolds } = React.useContext(HoldsContext);
     const { library } = React.useContext(LibrarySystemContext);

     const isPlacingHold = action.includes('hold');

     let shouldDisplayVolumes = false;
     let typeOfHold = 'default';
     let promptForHoldType = false;

     if (volumeInfo.numItemsWithVolumes > 0) {
          typeOfHold = 'item';
          shouldDisplayVolumes = true;
          promptForHoldType = true;

          if (volumeInfo.majorityOfItemsHaveVolumes) {
               typeOfHold = 'volume';
          }

          if (_.isEmpty(volumeInfo.hasItemsWithoutVolumes) || !volumeInfo.hasItemsWithoutVolumes === false) {
               typeOfHold = 'volume';
               promptForHoldType = false;
          }
     }

     const [holdType, setHoldType] = React.useState(typeOfHold);

     let userPickupLocationId = user.pickupLocationId ?? user.homeLocationId;
     if (_.isNumber(user.pickupLocationId)) {
          userPickupLocationId = _.toString(user.pickupLocationId);
     }

     let pickupLocation = '';
     if (_.size(locations) > 1) {
          const userPickupLocation = _.filter(locations, { locationId: userPickupLocationId });
          if (!_.isUndefined(userPickupLocation && !_.isEmpty(userPickupLocation))) {
               pickupLocation = userPickupLocation[0];
               if (_.isObject(pickupLocation)) {
                    pickupLocation = pickupLocation.code;
               }
          }
     } else {
          pickupLocation = locations[0];
          if (_.isObject(pickupLocation)) {
               pickupLocation = pickupLocation.code;
          }
     }

     //console.log(pickupLocation);

     const [location, setLocation] = React.useState(pickupLocation);

     const [activeAccount, setActiveAccount] = React.useState(user.id);

     let availableAccounts = [];
     if (_.size(accounts) > 0) {
          availableAccounts = Object.values(accounts);
     }

     return (
          <>
               <Button
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
               <Modal isOpen={showModal} onClose={() => setShowModal(false)} closeOnOverlayClick={false} size="lg">
                    <Modal.Content maxWidth="90%" bg="white" _dark={{ bg: 'coolGray.800' }}>
                         <Modal.CloseButton />
                         <Modal.Header>
                              <Heading size="md">{isPlacingHold ? getTermFromDictionary(language, 'hold_options') : getTermFromDictionary(language, 'checkout_options')}</Heading>
                         </Modal.Header>
                         <Modal.Body>
                              {shouldDisplayVolumes ? <SelectVolume language={language} id={id} holdType={holdType} setHoldType={setHoldType} volume={volume} setVolume={setVolume} promptForHoldType={promptForHoldType} /> : null}
                              {_.size(accounts) > 1 ? (
                                   <FormControl>
                                        <FormControl.Label>{isPlacingHold ? getTermFromDictionary(language, 'linked_place_hold_for_account') : getTermFromDictionary(language, 'linked_checkout_to_account')}</FormControl.Label>
                                        <Select
                                             isReadOnly={Platform.OS === 'android'}
                                             name="linkedAccount"
                                             selectedValue={activeAccount}
                                             minWidth="200"
                                             accessibilityLabel={isPlacingHold ? getTermFromDictionary(language, 'linked_place_hold_for_account') : getTermFromDictionary(language, 'linked_checkout_to_account')}
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
                                   <FormControl.Label>{getTermFromDictionary(language, 'select_pickup_location')}</FormControl.Label>
                                   <Select
                                        isReadOnly={Platform.OS === 'android'}
                                        name="pickupLocations"
                                        selectedValue={location}
                                        minWidth="200"
                                        accessibilityLabel={getTermFromDictionary(language, 'select_pickup_location')}
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
                                        {getTermFromDictionary(language, 'close_button')}
                                   </Button>
                                   <Button
                                        isLoading={loading}
                                        isLoadingText={isPlacingHold ? getTermFromDictionary(language, 'placing_hold', true) : getTermFromDictionary(language, 'checking_out', true)}
                                        onPress={async () => {
                                             setLoading(true);
                                             await completeAction(id, action, activeAccount, null, null, location, library.baseUrl, volume, holdType).then(async (result) => {
                                                  setResponse(result);
                                                  setShowModal(false);
                                                  if (result) {
                                                       setResponseIsOpen(true);
                                                       if (result.success) {
                                                            await refreshProfile(library.baseUrl).then((profile) => {
                                                                 updateUser(profile);
                                                            });
                                                       }
                                                  }
                                             });
                                             setLoading(false);
                                        }}>
                                        {title}
                                   </Button>
                              </Button.Group>
                         </Modal.Footer>
                    </Modal.Content>
               </Modal>
          </>
     );
};

export default SelectPickupLocation;