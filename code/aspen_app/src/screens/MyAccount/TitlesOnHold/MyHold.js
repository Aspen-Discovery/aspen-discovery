import { HoldsContext, LibrarySystemContext, UserContext } from '../../../context/initialContext';
import { translate } from '../../../translations/translations';
import { formatDiscoveryVersion } from '../../../util/loadLibrary';
import { getAuthor, getBadge, getCleanTitle, getExpirationDate, getFormat, getOnHoldFor, getPickupLocation, getPosition, getStatus, getTitle, getType } from '../../../helpers/item';
import { cancelHold, cancelHolds, cancelVdxRequest, thawHold, thawHolds } from '../../../util/accountActions';
import { SelectThawDate } from './SelectThawDate';
import { SelectPickupLocation } from './SelectPickupLocation';
import _ from 'lodash';
import React from 'react';
import { useNavigation } from '@react-navigation/native';
import { Actionsheet, Box, Button, Center, Icon, Pressable, Text, HStack, VStack, Image, Checkbox, useDisclose } from 'native-base';
import { MaterialCommunityIcons, MaterialIcons } from '@expo/vector-icons';
import { navigateStack } from '../../../helpers/RootNavigator';

export const MyHold = (props) => {
     const hold = props.data;
     const resetGroup = props.resetGroup;
     const pickupLocations = props.pickupLocations;
     const navigation = useNavigation();
     const { isOpen, onOpen, onClose } = useDisclose();
     const { user } = React.useContext(UserContext);
     const { library } = React.useContext(LibrarySystemContext);
     const { holds, updateHolds } = React.useContext(HoldsContext);
     const [cancelling, startCancelling] = React.useState(false);
     const [thawing, startThawing] = React.useState(false);
     let label, method, icon, canCancel;

     if (hold.canFreeze === true) {
          if (hold.frozen === true) {
               label = translate('holds.thaw_hold');
               method = 'thawHold';
               icon = 'play';
          } else {
               label = translate('holds.freeze_hold');
               method = 'freezeHold';
               icon = 'pause';
               if (hold.available) {
                    label = translate('overdrive.delay_checkout');
                    method = 'freezeHold';
                    icon = 'pause';
               }
          }
     }

     if (!hold.available && hold.source !== 'ils') {
          canCancel = hold.cancelable;
     } else canCancel = !hold.available && hold.source === 'ils';

     let allowLinkedAccountAction = true;
     const discoveryVersion = formatDiscoveryVersion(library.discoveryVersion);
     if (discoveryVersion < '22.05.00') {
          if (hold.userId !== user.id) {
               allowLinkedAccountAction = false;
          }
     }

     const openGroupedWork = (item, title) => {
          navigateStack('AccountScreenTab', 'MyHold', {
               id: item,
               title: getCleanTitle(title),
               url: library.baseUrl,
               userContext: user,
               libraryContext: library,
          });
     };

     const initializeLeftColumn = () => {
          if (hold.coverUrl && hold.source !== 'vdx') {
               return (
                    <VStack>
                         <Image
                              source={{ uri: hold.coverUrl }}
                              borderRadius="md"
                              size={{
                                   base: '80px',
                                   lg: '120px',
                              }}
                              alt={hold.title}
                         />
                         {(hold.allowFreezeHolds || canCancel) && allowLinkedAccountAction ? (
                              <Center>
                                   <Checkbox value={method + '|' + hold.recordId + '|' + hold.cancelId + '|' + hold.source + '|' + hold.userId} my={3} size="md" accessibilityLabel="Check item" />
                              </Center>
                         ) : null}
                    </VStack>
               );
          } else {
               return (
                    <Center>
                         <Checkbox value={method + '|' + hold.recordId + '|' + hold.cancelId + '|' + hold.source + '|' + hold.userId} my={3} size="md" accessibilityLabel="Check item" />
                    </Center>
               );
          }
     };

     const createOpenGroupedWorkAction = () => {
          if (hold.groupedWorkId) {
               return (
                    <Actionsheet.Item
                         startIcon={<Icon as={MaterialIcons} name="search" color="trueGray.400" mr="1" size="6" />}
                         onPress={() => {
                              openGroupedWork(hold.groupedWorkId, hold.title);
                              onClose(onClose);
                         }}>
                         {translate('grouped_work.view_item_details')}
                    </Actionsheet.Item>
               );
          } else {
               return null;
          }
     };

     const createCancelHoldAction = () => {
          if (canCancel && allowLinkedAccountAction) {
               let label = translate('holds.cancel_hold');
               if (hold.type === 'interlibrary_loan') {
                    label = translate('holds.cancel_request');
               }

               if (hold.source !== 'vdx') {
                    return (
                         <Actionsheet.Item
                              isLoading={cancelling}
                              isLoadingText="Cancelling..."
                              startIcon={<Icon as={MaterialIcons} name="cancel" color="trueGray.400" mr="1" size="6" />}
                              onPress={() => {
                                   startCancelling(true);
                                   cancelHold(hold.cancelId, hold.recordId, hold.source, library.baseUrl, hold.userId).then((r) => {
                                        resetGroup();
                                        onClose(onClose);
                                        startCancelling(false);
                                   });
                              }}>
                              {label}
                         </Actionsheet.Item>
                    );
               } else {
                    return (
                         <Actionsheet.Item
                              isLoading={cancelling}
                              isLoadingText="Cancelling..."
                              startIcon={<Icon as={MaterialIcons} name="cancel" color="trueGray.400" mr="1" size="6" />}
                              onPress={() => {
                                   startCancelling(true);
                                   cancelVdxRequest(library.baseUrl, hold.sourceId, hold.cancelId).then((r) => {
                                        resetGroup();
                                        onClose(onClose);
                                        startCancelling(false);
                                   });
                              }}>
                              {label}
                         </Actionsheet.Item>
                    );
               }
          } else {
               return null;
          }
     };

     const createFreezeHoldAction = () => {
          if (hold.allowFreezeHolds === '1' && allowLinkedAccountAction) {
               if (hold.frozen) {
                    return (
                         <Actionsheet.Item
                              isLoading={thawing}
                              isLoadingText="Thawing..."
                              startIcon={<Icon as={MaterialCommunityIcons} name={icon} color="trueGray.400" mr="1" size="6" />}
                              onPress={() => {
                                   startThawing(true);
                                   thawHold(hold.cancelId, hold.recordId, hold.source, library.baseUrl, hold.userId).then((r) => {
                                        resetGroup();
                                        onClose(onClose);
                                        startThawing(false);
                                   });
                              }}>
                              {label}
                         </Actionsheet.Item>
                    );
               } else {
                    return <SelectThawDate libraryContext={library} holdsContext={updateHolds} onClose={onClose} freezeId={hold.cancelId} recordId={hold.recordId} source={hold.source} libraryUrl={library.baseUrl} userId={hold.userId} resetGroup={resetGroup} />;
               }
          } else {
               return null;
          }
     };

     const createUpdatePickupLocationAction = (canUpdate, available) => {
          if (canUpdate && !available) {
               return <SelectPickupLocation libraryContext={library} holdsContext={updateHolds} locations={pickupLocations} onClose={onClose} userId={hold.userId} currentPickupId={hold.pickupLocationId} holdId={hold.cancelId} resetGroup={resetGroup} />;
          } else {
               return null;
          }
     };

     return (
          <>
               <Pressable onPress={onOpen} borderBottomWidth="1" _dark={{ borderColor: 'gray.600' }} borderColor="coolGray.200" pl="4" pr="20" py="2">
                    <HStack space={3} maxW="95%">
                         {initializeLeftColumn()}
                         <VStack>
                              {getTitle(hold.title)}
                              {getBadge(hold.status, hold.frozen, hold.available, hold.source)}
                              {getAuthor(hold.author)}
                              {getFormat(hold.format)}
                              {getType(hold.type)}
                              {getOnHoldFor(hold.user)}
                              {getPickupLocation(hold.currentPickupName, hold.source)}
                              {getExpirationDate(hold.expirationDate, hold.available)}
                              {getPosition(hold.position, hold.available)}
                              {getStatus(hold.status, hold.source)}
                         </VStack>
                    </HStack>
               </Pressable>
               <Actionsheet isOpen={isOpen} onClose={onClose} size="full">
                    <Actionsheet.Content>
                         <Box w="100%" h={60} px={4} justifyContent="center">
                              <Text
                                   fontSize="18"
                                   color="gray.500"
                                   _dark={{
                                        color: 'gray.300',
                                   }}>
                                   {getTitle(hold.title)}
                              </Text>
                         </Box>
                         {createOpenGroupedWorkAction()}
                         {createCancelHoldAction()}
                         {createFreezeHoldAction()}
                         {createUpdatePickupLocationAction(hold.locationUpdateable ?? false, hold.available)}
                    </Actionsheet.Content>
               </Actionsheet>
          </>
     );
};

export const ManageSelectedHolds = (props) => {
     const { selectedValues, onAllDateChange, selectedReactivationDate, resetGroup, context } = props;
     const navigation = useNavigation();
     const { user, updateUser } = React.useContext(UserContext);
     const { library } = React.useContext(LibrarySystemContext);
     const { holds, updateHolds } = React.useContext(HoldsContext);
     const { isOpen, onOpen, onClose } = useDisclose();
     const [cancelling, startCancelling] = React.useState(false);
     const [thawing, startThawing] = React.useState(false);

     let titlesToFreeze = [];
     let titlesToThaw = [];
     let titlesToCancel = [];

     let numToCancel = 0;
     let numToFreeze = 0;
     let numToThaw = 0;
     let numSelected = 0;

     if (_.isArray(selectedValues)) {
          _.map(selectedValues, function (item, index, collection) {
               if (item.includes('freeze')) {
                    const arr = item.split('|');
                    titlesToFreeze.push({
                         action: arr[0],
                         recordId: arr[1],
                         cancelId: arr[2],
                         source: arr[3],
                         patronId: arr[4],
                    });
               }
               if (item.includes('thaw')) {
                    const arr = item.split('|');
                    titlesToThaw.push({
                         action: arr[0],
                         recordId: arr[1],
                         cancelId: arr[2],
                         source: arr[3],
                         patronId: arr[4],
                    });
               }

               const arr = item.split('|');
               titlesToCancel.push({
                    action: arr[0],
                    recordId: arr[1],
                    cancelId: arr[2],
                    source: arr[3],
                    patronId: arr[4],
               });
          });

          numToCancel = titlesToCancel.length;
          numToFreeze = titlesToFreeze.length;
          numToThaw = titlesToThaw.length;
          numSelected = selectedValues.length;
     }

     const cancelActionItem = () => {
          if (numToCancel > 0) {
               return (
                    <Actionsheet.Item
                         onPress={() => {
                              startCancelling(true);
                              cancelHolds(titlesToCancel, library.baseUrl).then((r) => {
                                   resetGroup();
                                   onClose(onClose);
                                   startCancelling(false);
                              });
                         }}
                         isLoading={cancelling}
                         isLoadingText={translate('general.canceling')}>
                         {translate('holds.cancel_holds_count', { num: numToCancel })}
                    </Actionsheet.Item>
               );
          } else {
               return <Actionsheet.Item isDisabled>{translate('holds.cancel_holds_count', { num: numToCancel })}</Actionsheet.Item>;
          }
     };

     const thawActionItem = () => {
          if (numToThaw > 0) {
               return (
                    <Actionsheet.Item
                         onPress={() => {
                              startThawing(true);
                              thawHolds(titlesToThaw, library.baseUrl).then((r) => {
                                   resetGroup();
                                   onClose(onClose);
                                   startThawing(false);
                              });
                         }}
                         isLoading={thawing}
                         isLoadingText={translate('holds.thawing')}>
                         {translate('holds.thaw_holds_count', { num: numToThaw })}
                    </Actionsheet.Item>
               );
          } else {
               return <Actionsheet.Item isDisabled>{translate('holds.thaw_holds')}</Actionsheet.Item>;
          }
     };

     return (
          <Center>
               <Button onPress={onOpen} size="sm" variant="solid" mr={1}>
                    {translate('holds.manage_selected', { num: numSelected })}
               </Button>
               <Actionsheet isOpen={isOpen} onClose={onClose}>
                    <Actionsheet.Content>
                         {cancelActionItem()}
                         <SelectThawDate holdsContext={updateHolds} libraryContext={library} resetGroup={resetGroup} onClose={onClose} count={numToFreeze} numSelected={numSelected} data={titlesToFreeze} />
                         {thawActionItem()}
                    </Actionsheet.Content>
               </Actionsheet>
          </Center>
     );
};

export const ManageAllHolds = (props) => {
     const { resetGroup } = props;
     const { holds, updateHolds } = React.useContext(HoldsContext);
     const { library } = React.useContext(LibrarySystemContext);
     const { isOpen, onOpen, onClose } = useDisclose();
     const [cancelling, startCancelling] = React.useState(false);
     const [thawing, startThawing] = React.useState(false);

     let titlesToFreeze = [];
     let titlesToThaw = [];
     let titlesToCancel = [];

     if (_.isArray(holds.holds)) {
          _.map(holds.holds, function (item, index, collection) {
               if (item.source !== 'vdx') {
                    if (item.canFreeze) {
                         if (item.frozen) {
                              titlesToThaw.push({
                                   recordId: item.recordId,
                                   cancelId: item.cancelId,
                                   source: item.source,
                                   patronId: item.userId,
                              });
                         } else {
                              titlesToFreeze.push({
                                   recordId: item.recordId,
                                   cancelId: item.cancelId,
                                   source: item.source,
                                   patronId: item.userId,
                              });
                         }
                    }

                    if (item.cancelable) {
                         titlesToCancel.push({
                              recordId: item.recordId,
                              cancelId: item.cancelId,
                              source: item.source,
                              patronId: item.userId,
                         });
                    }
               }
          });
     }

     let numToCancel = titlesToCancel.length;
     let numToFreeze = titlesToFreeze.length;
     let numToThaw = titlesToThaw.length;

     let numToManage = numToCancel + numToFreeze + numToThaw;

     if (numToManage >= 1) {
          return (
               <Center>
                    <Button size="sm" variant="solid" mr={1} onPress={onOpen}>
                         {translate('holds.manage_all')}
                    </Button>
                    <Actionsheet isOpen={isOpen} onClose={onClose}>
                         <Actionsheet.Content>
                              <Actionsheet.Item
                                   isLoading={cancelling}
                                   isLoadingText={translate('general.canceling')}
                                   onPress={() => {
                                        startCancelling(true);
                                        cancelHolds(titlesToCancel, library.baseUrl).then((r) => {
                                             resetGroup();
                                             onClose(onClose);
                                             startCancelling(false);
                                        });
                                   }}>
                                   {translate('holds.cancel_all_holds_count', { num: numToCancel })}
                              </Actionsheet.Item>
                              <SelectThawDate holdsContext={updateHolds} libraryContext={library} resetGroup={resetGroup} onClose={onClose} count={numToFreeze} numSelected={numToManage} data={titlesToFreeze} />
                              <Actionsheet.Item
                                   isLoading={thawing}
                                   isLoadingText={translate('holds.thawing')}
                                   onPress={() => {
                                        startThawing(true);
                                        thawHolds(titlesToThaw, library.baseUrl).then((r) => {
                                             resetGroup();
                                             onClose(onClose);
                                             startThawing(false);
                                        });
                                   }}>
                                   {translate('holds.thaw_all_holds_count', { num: numToThaw })}
                              </Actionsheet.Item>
                         </Actionsheet.Content>
                    </Actionsheet>
               </Center>
          );
     }

     return null;
};