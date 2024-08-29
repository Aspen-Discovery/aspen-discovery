import { MaterialCommunityIcons, MaterialIcons } from '@expo/vector-icons';
import { useNavigation } from '@react-navigation/native';
import DateTimePickerModal from 'react-native-modal-datetime-picker';
import { Image } from 'expo-image';
import _ from 'lodash';
import { Actionsheet, Box, Button, Center, Checkbox, HStack, Icon, Pressable, Text, useDisclose, VStack, useToken, useColorModeValue } from 'native-base';
import React from 'react';
import { popAlert } from '../../../components/loadError';
import { HoldsContext, LanguageContext, LibrarySystemContext, UserContext } from '../../../context/initialContext';
import { getAuthor, getBadge, getCleanTitle, getExpirationDate, getFormat, getOnHoldFor, getPickupLocation, getPosition, getStatus, getTitle, getType } from '../../../helpers/item';
import { navigateStack } from '../../../helpers/RootNavigator';
import { getTermFromDictionary } from '../../../translations/TranslationService';
import { cancelHold, cancelHolds, cancelVdxRequest, freezeHold, freezeHolds, thawHold, thawHolds } from '../../../util/accountActions';
import { formatDiscoveryVersion } from '../../../util/loadLibrary';
import { checkoutItem } from '../../../util/recordActions';
import { SelectPickupLocation } from './SelectPickupLocation';
import { SelectThawDate } from './SelectThawDate.js';

const blurhash = 'MHPZ}tt7*0WC5S-;ayWBofj[K5RjM{ofM_';

export const MyHold = (props) => {
     const hold = props.data;
     const resetGroup = props.resetGroup;
     const pickupLocations = props.pickupLocations;
     const section = props.section;
     const { isOpen, onOpen, onClose } = useDisclose();
     const { user } = React.useContext(UserContext);
     const { library } = React.useContext(LibrarySystemContext);
     const { holds, updateHolds } = React.useContext(HoldsContext);
     const { language } = React.useContext(LanguageContext);
     const [cancelling, startCancelling] = React.useState(false);
     const [checkingOut, startCheckingOut] = React.useState(false);
     const [thawing, startThawing] = React.useState(false);
     let label, method, icon, canCancel;
     const version = formatDiscoveryVersion(library.discoveryVersion);
     const [usesHoldPosition, setUsesHoldPosition] = React.useState(false);
     const [holdPosition, setHoldPosition] = React.useState(null);

     React.useEffect(() => {
          if (hold.holdQueueLength) {
               let tmp = getTermFromDictionary(language, 'hold_position_with_queue');
               if (hold.holdQueueLength && hold.position) {
                    tmp = tmp.replace('%1%', hold.position);
                    tmp = tmp.replace('%2%', hold.holdQueueLength);
                    console.log(tmp);
                    setUsesHoldPosition(true);
                    setHoldPosition(tmp);
               }
          }
     }, [language]);

     if (hold.canFreeze === true) {
          if (hold.frozen === true) {
               label = getTermFromDictionary(language, 'thaw_hold');
               method = 'thawHold';
               icon = 'play';
          } else {
               label = getTermFromDictionary(language, 'freeze_hold');
               method = 'freezeHold';
               icon = 'pause';
               if (hold.available) {
                    label = getTermFromDictionary(language, 'overdrive_delay_checkout');
                    method = 'freezeHold';
                    icon = 'pause';
               }
          }
     }

     if (!hold.available && hold.source !== 'ils') {
          canCancel = hold.cancelable;
          if (hold.source === 'axis360') {
               canCancel = true;
          }
     } else {
          canCancel = hold.cancelable;
     }

     let isPendingCancellation = false;
     if (hold.pendingCancellation) {
          canCancel = !hold.pendingCancellation;
          isPendingCancellation = hold.pendingCancellation;
     }

     let allowLinkedAccountAction = true;
     const discoveryVersion = formatDiscoveryVersion(library.discoveryVersion);
     if (discoveryVersion < '22.05.00') {
          if (hold.userId !== user.id) {
               allowLinkedAccountAction = false;
          }
     }

     const freezingHoldLabel = getTermFromDictionary(language, 'freezing_hold');
     const freezeHoldLabel = getTermFromDictionary(language, 'freeze_hold');

     const openGroupedWork = (item, title) => {
          navigateStack('AccountScreenTab', 'MyHold', {
               id: item,
               title: getCleanTitle(title),
               url: library.baseUrl,
               userContext: user,
               libraryContext: library,
               prevRoute: 'MyHolds',
          });
     };

     const initializeLeftColumn = () => {
          const key = 'medium_' + hold.source + '_' + hold.groupedWorkId;
          if (hold.coverUrl && hold.source !== 'vdx') {
               let url = library.baseUrl + '/bookcover.php?id=' + hold.source + ':' + hold.recordId + '&size=medium';
               if (hold.upc) {
                    url = url + '&upc=' + hold.upc;
               }
               return (
                    <VStack>
                         <Image
                              alt={hold.title}
                              source={url}
                              style={{
                                   width: 100,
                                   height: 150,
                                   borderRadius: 4,
                              }}
                              placeholder={blurhash}
                              transition={1000}
                              contentFit="cover"
                         />
                         {(hold.allowFreezeHolds || canCancel) && allowLinkedAccountAction && section === 'Pending' ? (
                              <Center>
                                   <Checkbox value={method + '|' + hold.recordId + '|' + hold.cancelId + '|' + hold.source + '|' + hold.userId} my={3} size="md" accessibilityLabel="Check item" />
                              </Center>
                         ) : null}
                    </VStack>
               );
          } else {
               if (section === 'Pending') {
                    return (
                         <Center>
                              <Checkbox value={method + '|' + hold.recordId + '|' + hold.cancelId + '|' + hold.source + '|' + hold.userId} my={3} size="md" accessibilityLabel="Check item" />
                         </Center>
                    );
               }
          }

          return null;
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
                         {getTermFromDictionary(language, 'view_item_details')}
                    </Actionsheet.Item>
               );
          } else {
               return null;
          }
     };

     const createCheckoutHoldAction = () => {
          if (hold.source === 'overdrive' && hold.available) {
               return (
                    <Actionsheet.Item
                         isLoading={checkingOut}
                         isLoadingText={getTermFromDictionary(language, 'checking_out', true)}
                         onPress={async () => {
                              startCheckingOut(true);
                              await checkoutItem(library.baseUrl, hold.recordId, hold.source, hold.userId, '', '', '', language).then((result) => {
                                   popAlert(result.title, result.message, result.success ? 'success' : 'error');
                                   resetGroup();
                                   onClose();
                                   startCheckingOut(false);
                              });
                         }}
                         startIcon={<Icon as={MaterialIcons} name="book" color="trueGray.400" mr="1" size="6" />}>
                         {getTermFromDictionary(language, 'checkout_title')}
                    </Actionsheet.Item>
               );
          }

          return null;
     };

     const createCancelHoldAction = () => {
          if (canCancel && allowLinkedAccountAction) {
               let label = getTermFromDictionary(language, 'cancel_hold');
               if (hold.type === 'interlibrary_loan') {
                    label = getTermFromDictionary(language, 'ill_cancel_request');
               }

               if (hold.source !== 'vdx') {
                    return (
                         <Actionsheet.Item
                              isLoading={cancelling}
                              isLoadingText={getTermFromDictionary(language, 'canceling', true)}
                              startIcon={<Icon as={MaterialIcons} name="cancel" color="trueGray.400" mr="1" size="6" />}
                              onPress={() => {
                                   startCancelling(true);
                                   cancelHold(hold.cancelId, hold.recordId, hold.source, library.baseUrl, hold.userId, language).then((r) => {
                                        resetGroup();
                                        onClose();
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
                                   cancelVdxRequest(library.baseUrl, hold.sourceId, hold.cancelId, language).then((r) => {
                                        resetGroup();
                                        onClose();
                                        startCancelling(false);
                                   });
                              }}>
                              {label}
                         </Actionsheet.Item>
                    );
               }
          } else if (hold.pendingCancellation) {
               return <Actionsheet.Item>{getTermFromDictionary(language, 'pending_cancellation')}</Actionsheet.Item>;
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
                              isLoadingText={getTermFromDictionary(language, 'thawing_hold', true)}
                              startIcon={<Icon as={MaterialCommunityIcons} name={icon} color="trueGray.400" mr="1" size="6" />}
                              onPress={() => {
                                   startThawing(true);
                                   thawHold(hold.cancelId, hold.recordId, hold.source, library.baseUrl, hold.userId, language).then((r) => {
                                        resetGroup();
                                        onClose(onClose);
                                        startThawing(false);
                                   });
                              }}>
                              {label}
                         </Actionsheet.Item>
                    );
               } else {
                    return <SelectThawDate isOpen={isOpen} label={null} freezeLabel={freezeHoldLabel} freezingLabel={freezingHoldLabel} language={language} libraryContext={library} holdsContext={updateHolds} onClose={onClose} freezeId={hold.cancelId} recordId={hold.recordId} source={hold.source} libraryUrl={library.baseUrl} userId={hold.userId} resetGroup={resetGroup} />;
               }
          } else {
               return null;
          }
     };

     const createUpdatePickupLocationAction = (canUpdate, available) => {
          if (canUpdate && !available) {
               return <SelectPickupLocation isOpen={isOpen} language={language} libraryContext={library} holdsContext={updateHolds} locations={pickupLocations} onClose={onClose} userId={hold.userId} currentPickupId={hold.pickupLocationId} holdId={hold.cancelId} resetGroup={resetGroup} />;
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
                              {getBadge(hold.status, hold.frozen, hold.available, hold.source, hold.statusMessage ?? '')}
                              {getAuthor(hold.author)}
                              {getFormat(hold.format)}
                              {getType(hold.type)}
                              {getOnHoldFor(hold.user)}
                              {getPickupLocation(hold.currentPickupName, hold.source)}
                              {getExpirationDate(hold.expirationDate, hold.available)}
                              {getPosition(hold.position, hold.available, hold.holdQueueLength, holdPosition, usesHoldPosition)}
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
                         {createCheckoutHoldAction()}
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
     const { language } = React.useContext(LanguageContext);
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
          numSelected = _.toString(selectedValues.length);
     }

     const numToCancelLabel = getTermFromDictionary(language, 'cancel_selected_holds') + ' (' + numToCancel + ')';
     const numToFreezeLabel = getTermFromDictionary(language, 'freeze_selected_holds') + ' (' + numToFreeze + ')';
     const numToThawLabel = getTermFromDictionary(language, 'thaw_selected_holds') + ' (' + numToThaw + ')';
     const numSelectedLabel = getTermFromDictionary(language, 'manage_selected') + ' (' + numSelected + ')';
     const freezingHoldLabel = getTermFromDictionary(language, 'freezing_hold');
     const freezeHoldLabel = getTermFromDictionary(language, 'freeze_hold');

     const cancelActionItem = () => {
          if (numToCancel > 0) {
               return (
                    <Actionsheet.Item
                         onPress={() => {
                              startCancelling(true);
                              cancelHolds(titlesToCancel, library.baseUrl, language).then((r) => {
                                   resetGroup();
                                   onClose(onClose);
                                   startCancelling(false);
                              });
                         }}
                         isLoading={cancelling}
                         isLoadingText={getTermFromDictionary(language, 'canceling', true)}>
                         {numToCancelLabel}
                    </Actionsheet.Item>
               );
          } else {
               return <Actionsheet.Item isDisabled>{getTermFromDictionary(language, 'cancel_holds')}</Actionsheet.Item>;
          }
     };

     const thawActionItem = () => {
          if (numToThaw > 0) {
               return (
                    <Actionsheet.Item
                         onPress={() => {
                              startThawing(true);
                              thawHolds(titlesToThaw, library.baseUrl, language).then((r) => {
                                   resetGroup();
                                   onClose(onClose);
                                   startThawing(false);
                              });
                         }}
                         isLoading={thawing}
                         isLoadingText={getTermFromDictionary(language, 'thawing_hold', true)}>
                         {numToThawLabel}
                    </Actionsheet.Item>
               );
          } else {
               return <Actionsheet.Item isDisabled>{numToThawLabel}</Actionsheet.Item>;
          }
     };

     return (
          <Center>
               <Button onPress={onOpen} size="sm" variant="solid" mr={1}>
                    {numSelectedLabel}
               </Button>
               <Actionsheet isOpen={isOpen} onClose={onClose}>
                    <Actionsheet.Content>
                         {cancelActionItem()}
                         <SelectThawDate isOpen={isOpen} label={numToFreezeLabel} freezeLabel={freezeHoldLabel} freezingLabel={freezingHoldLabel} language={language} holdsContext={updateHolds} libraryContext={library} resetGroup={resetGroup} onClose={onClose} count={numToFreeze} numSelected={numSelected} data={titlesToFreeze} />
                         {thawActionItem()}
                    </Actionsheet.Content>
               </Actionsheet>
          </Center>
     );
};

export const ManageAllHolds = (props) => {
     const { resetGroup } = props;
     const { language } = React.useContext(LanguageContext);
     const { holds, updateHolds } = React.useContext(HoldsContext);
     const { library } = React.useContext(LibrarySystemContext);
     const { isOpen, onOpen, onClose } = useDisclose();
     const [cancelling, startCancelling] = React.useState(false);
     const [thawing, startThawing] = React.useState(false);

     let titlesToFreeze = [];
     let titlesToThaw = [];
     let titlesToCancel = [];

     const holdsNotReady = holds[1].data;

     if (_.isArray(holdsNotReady)) {
          _.map(holdsNotReady, function (item, index, collection) {
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

     const numToCancelLabel = getTermFromDictionary(language, 'cancel_all_holds') + ' (' + numToCancel + ')';
     const numToFreezeLabel = getTermFromDictionary(language, 'freeze_all_holds') + ' (' + numToFreeze + ')';
     const numToThawLabel = getTermFromDictionary(language, 'thaw_all_holds') + ' (' + numToThaw + ')';
     const freezingHoldLabel = getTermFromDictionary(language, 'freezing_hold');
     const freezeHoldLabel = getTermFromDictionary(language, 'freeze_hold');

     if (numToManage >= 1) {
          return (
               <Center>
                    <Button size="sm" variant="solid" mr={1} onPress={onOpen}>
                         {getTermFromDictionary(language, 'hold_manage_all')}
                    </Button>
                    <Actionsheet isOpen={isOpen} onClose={onClose}>
                         <Actionsheet.Content>
                              <Actionsheet.Item
                                   isLoading={cancelling}
                                   isLoadingText={getTermFromDictionary(language, 'canceling', true)}
                                   onPress={() => {
                                        startCancelling(true);
                                        cancelHolds(titlesToCancel, library.baseUrl, language).then((r) => {
                                             resetGroup();
                                             onClose();
                                             startCancelling(false);
                                        });
                                   }}>
                                   {numToCancelLabel}
                              </Actionsheet.Item>
                              <SelectThawDate label={numToFreezeLabel} freezeLabel={freezeHoldLabel} freezingLabel={freezingHoldLabel} language={language} holdsContext={updateHolds} libraryContext={library} resetGroup={resetGroup} onClose={onClose} count={numToFreeze} numSelected={numToManage} data={titlesToFreeze} />
                              <Actionsheet.Item
                                   isLoading={thawing}
                                   isLoadingText={getTermFromDictionary(language, 'thaw_hold', true)}
                                   onPress={() => {
                                        startThawing(true);
                                        thawHolds(titlesToThaw, library.baseUrl, language).then((r) => {
                                             resetGroup();
                                             onClose(onClose);
                                             startThawing(false);
                                        });
                                   }}>
                                   {numToThawLabel}
                              </Actionsheet.Item>
                         </Actionsheet.Content>
                    </Actionsheet>
               </Center>
          );
     }

     return null;
};