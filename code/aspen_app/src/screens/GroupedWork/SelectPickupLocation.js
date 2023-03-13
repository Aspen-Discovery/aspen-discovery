import _ from 'lodash';
import { Button, FormControl, Modal, Select, CheckIcon, Heading, AlertDialog, Center } from 'native-base';
import React, { useState } from 'react';
import { translate } from '../../translations/translations';
import { completeAction } from './Record';
import {HoldsContext, LibrarySystemContext, UserContext} from '../../context/initialContext';
import {refreshProfile} from '../../util/api/user';
import {reloadHolds} from '../../util/loadPatron';
import {navigate, navigateStack} from '../../helpers/RootNavigator';
import {SelectVolume} from './SelectVolume';

const SelectPickupLocation = (props) => {
     const { id, action, title, volumeInfo, prevRoute, response, setResponse, responseIsOpen, setResponseIsOpen, onResponseClose, cancelResponseRef } = props;
     const [loading, setLoading] = React.useState(false);
     const [showModal, setShowModal] = useState(false);
     const [volume, setVolume] = React.useState(null);
     const { user, updateUser, accounts, locations } = React.useContext(UserContext);
     const { updateHolds } = React.useContext(HoldsContext);
     const { library } = React.useContext(LibrarySystemContext);
     const { language } = React.useContext(LanguageContext);

     const isPlacingHold = action.includes('hold');

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
                              <Heading size="md">{isPlacingHold ? translate('grouped_work.hold_options') : translate('grouped_work.checkout_options')}</Heading>
                         </Modal.Header>
                         <Modal.Body>
                              {shouldDisplayVolumes ? (
                                  <SelectVolume language={language} id={id} holdType={holdType} setHoldType={setHoldType} volume={volume} setVolume={setVolume} promptForHoldType={promptForHoldType}/>
                              ) : null}
                              {_.size(accounts) > 1 ? (
                                   <FormControl>
                                        <FormControl.Label>{isPlacingHold ? translate('linked_accounts.place_hold_for_account') : translate('linked_accounts.checkout_to_account')}</FormControl.Label>
                                        <Select
                                             name="linkedAccount"
                                             selectedValue={activeAccount}
                                             minWidth="200"
                                             accessibilityLabel={isPlacingHold ? translate('linked_accounts.place_hold_for_account') : translate('linked_accounts.checkout_to_account')}
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
                                        isLoadingText={isPlacingHold ? "Placing hold..." : "Checking out..."}
                                        onPress={async () => {
                                             setLoading(true);
                                             await completeAction(id, action, activeAccount, null, null, location, library.baseUrl, volume, holdType).then(async (result) => {
                                                  setResponse(result);
                                                  setShowModal(false);
                                                  if(result) {
                                                       setResponseIsOpen(true);
                                                       if(result.success) {
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