import { Ionicons, MaterialCommunityIcons, MaterialIcons } from '@expo/vector-icons';
import moment from 'moment';
import DateTimePicker from '@react-native-community/datetimepicker';
import _ from 'lodash';
import { ScrollView, Actionsheet, Box, Badge, Button, Center, FormControl, Select, CheckIcon, CloseIcon, FlatList, Icon, Pressable, Text, HStack, VStack, IconButton, Image, Checkbox, useDisclose } from 'native-base';
import React, { useState } from 'react';
import { Platform, SafeAreaView } from 'react-native';
import { useNavigation, useFocusEffect } from '@react-navigation/native';
import Modal from 'react-native-modal';

// custom components and helper files
import { loadingSpinner } from '../../../components/loadingSpinner';
import { translate } from '../../../translations/translations';
import { reloadHolds } from '../../../util/loadPatron';
import { HoldsContext, LibrarySystemContext, UserContext } from '../../../context/initialContext';
import { formatDiscoveryVersion, getPickupLocations } from '../../../util/loadLibrary';
import { cancelHold, cancelHolds, cancelVdxRequest, changeHoldPickUpLocation, freezeHold, freezeHolds, thawHold, thawHolds } from '../../../util/accountActions';
import { refreshProfile } from '../../../util/api/user';

export const MyHolds = () => {
     const { user, updateUser } = React.useContext(UserContext);
     const { library } = React.useContext(LibrarySystemContext);
     const { holds, updateHolds } = React.useContext(HoldsContext);
     const [isLoading, setLoading] = React.useState(true);
     const [values, setGroupValues] = React.useState([]);
     const [date, setNewDate] = React.useState();
     const [pickupLocations, setPickupLocations] = React.useState([]);

     useFocusEffect(
          React.useCallback(() => {
               const update = async () => {
                    await reloadHolds(library.baseUrl).then((result) => {
                         if (holds !== result) {
                              updateHolds(result);
                         }
                         setLoading(false);
                    });
                    await getPickupLocations(library.baseUrl).then((result) => {
                         if (pickupLocations !== result) {
                              setPickupLocations(result);
                         }
                    });
               };
               update().then(() => {
                    return () => update();
               });
          }, [])
     );

     if (isLoading) {
          return loadingSpinner();
     }

     const saveGroupValue = (data) => {
          setGroupValues(data);
     };

     const clearGroupValue = () => {
          setGroupValues([]);
     };

     const resetGroup = async () => {
          setLoading(true);
          clearGroupValue();
          await reloadHolds(library.baseUrl).then((result) => {
               if (holds !== result) {
                    updateHolds(result);
               }
               setLoading(false);
          });
          refreshProfile(library.baseUrl).then((result) => {
               updateUser(result);
          });
     };

     const handleDateChange = (date) => {
          setNewDate(date);
     };

     const noHolds = () => {
          return (
               <Center mt={5} mb={5}>
                    <Text bold fontSize="lg">
                         {translate('holds.no_holds')}
                    </Text>
               </Center>
          );
     };

     const refreshHolds = async () => {
          setLoading(true);
          await reloadHolds(library.baseUrl).then((result) => {
               if (holds !== result) {
                    updateHolds(result);
               }
               setLoading(false);
          });
     };

     const actionButtons = () => {
          let showSelectOptions = false;
          if (values.length >= 1) {
               showSelectOptions = true;
          }

          if (showSelectOptions) {
               return (
                    <HStack>
                         <ManageSelectedHolds selectedValues={values} onAllDateChange={handleDateChange} selectedReactivationDate={date} resetGroup={resetGroup} />
                         <Button size="sm" variant="outline" mr={1} onPress={() => clearGroupValue()}>
                              {translate('holds.clear_selections')}
                         </Button>
                         <Button
                              size="sm"
                              variant="outline"
                              onPress={() => {
                                   refreshHolds();
                              }}>
                              {translate('holds.reload_holds')}
                         </Button>
                    </HStack>
               );
          }

          return (
               <HStack>
                    <ManageAllHolds data={holds} onDateChange={handleDateChange} selectedReactivationDate={date} resetGroup={resetGroup} />
                    <Button
                         size="sm"
                         variant="outline"
                         onPress={() => {
                              refreshHolds();
                         }}>
                         {translate('holds.reload_holds')}
                    </Button>
               </HStack>
          );
     };

     return (
          <SafeAreaView style={{ flex: 1 }}>
               <Box safeArea={2} bgColor="coolGray.100" borderBottomWidth="1" _dark={{ borderColor: 'gray.600', bg: 'coolGray.700' }} borderColor="coolGray.200" flexWrap="nowrap">
                    <ScrollView horizontal>{actionButtons()}</ScrollView>
               </Box>
               <Checkbox.Group
                    name="Holds"
                    style={{ flex: 1 }}
                    value={values}
                    accessibilityLabel={translate('holds.multiple_holds')}
                    onChange={(newValues) => {
                         saveGroupValue(newValues);
                    }}>
                    <FlatList data={holds.holds} ListEmptyComponent={noHolds} renderItem={({ item }) => <Hold data={item} resetGroup={resetGroup} pickupLocations={pickupLocations} />} keyExtractor={(item, index) => index.toString()} contentContainerStyle={{ paddingBottom: 30 }} />
               </Checkbox.Group>
          </SafeAreaView>
     );
};

const Hold = (props) => {
     const hold = props.data;
     const resetGroup = props.resetGroup;
     const pickupLocations = props.pickupLocations;
     const navigation = useNavigation();
     const { isOpen, onOpen, onClose } = useDisclose();
     const { user } = React.useContext(UserContext);
     const { library } = React.useContext(LibrarySystemContext);
     const { holds, updateHolds } = React.useContext(HoldsContext);
     const [cancelling, startCancelling] = useState(false);
     const [thawing, startThawing] = useState(false);
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
     } else if (!hold.available && hold.source === 'ils') {
          canCancel = true;
     } else {
          canCancel = false;
     }

     let allowLinkedAccountAction = true;
     const discoveryVersion = formatDiscoveryVersion(library.discoveryVersion);
     if (discoveryVersion < '22.05.00') {
          if (hold.userId !== user.id) {
               allowLinkedAccountAction = false;
          }
     }

     const openGroupedWork = (item, title) => {
          const displayTitle = getTitle(title);
          navigation.navigate('GroupedWork', {
               id: item,
               title: displayTitle,
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

const ManageSelectedHolds = (props) => {
     const { selectedValues, onAllDateChange, selectedReactivationDate, resetGroup, context } = props;
     const navigation = useNavigation();
     const { user, updateUser } = React.useContext(UserContext);
     const { library } = React.useContext(LibrarySystemContext);
     const { holds, updateHolds } = React.useContext(HoldsContext);
     const { isOpen, onOpen, onClose } = useDisclose();
     const [cancelling, startCancelling] = useState(false);
     const [thawing, startThawing] = useState(false);

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

const ManageAllHolds = (props) => {
     const { resetGroup } = props;
     const { holds, updateHolds } = React.useContext(HoldsContext);
     const { library } = React.useContext(LibrarySystemContext);
     const { isOpen, onOpen, onClose } = useDisclose();
     const [cancelling, startCancelling] = useState(false);
     const [thawing, startThawing] = useState(false);

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

const getTitle = (title) => {
     if (title) {
          let displayTitle = title;
          const countSlash = displayTitle.split('/').length - 1;
          if (countSlash > 0) {
               displayTitle = displayTitle.substring(0, displayTitle.lastIndexOf('/'));
          }
          return (
               <Text
                    bold
                    mb={1}
                    fontSize={{
                         base: 'sm',
                         lg: 'lg',
                    }}>
                    {displayTitle}
               </Text>
          );
     } else {
          return null;
     }
};

const getAuthor = (author) => {
     if (author) {
          let displayAuthor = author;
          const countComma = displayAuthor.split(',').length - 1;
          if (countComma > 1) {
               displayAuthor = displayAuthor.substring(0, displayAuthor.lastIndexOf(','));
          }

          return (
               <Text
                    fontSize={{
                         base: 'xs',
                         lg: 'sm',
                    }}>
                    <Text bold>{translate('grouped_work.author')}:</Text> {displayAuthor}
               </Text>
          );
     }
     return null;
};

const getFormat = (format) => {
     if (format !== 'Unknown') {
          return (
               <Text
                    fontSize={{
                         base: 'xs',
                         lg: 'sm',
                    }}>
                    <Text bold>{translate('grouped_work.format')}:</Text> {format}
               </Text>
          );
     } else {
          return null;
     }
};

const getBadge = (status, frozen, available, source) => {
     if (frozen) {
          return (
               <Text>
                    <Badge colorScheme="yellow" rounded="4px" mt={-0.5}>
                         {status}
                    </Badge>
               </Text>
          );
     } else if (available) {
          let message = translate('overdrive.hold_ready');
          if (source === 'ils') {
               message = status;
          }
          return (
               <Text>
                    <Badge colorScheme="green" rounded="4px" mt={-0.5}>
                         {message}
                    </Badge>
               </Text>
          );
     } else {
          return null;
     }
};

const getStatus = (status, source) => {
     if (status) {
          if (source === 'vdx') {
               return (
                    <Text
                         fontSize={{
                              base: 'xs',
                              lg: 'sm',
                         }}>
                         <Text bold>{translate('holds.status')}:</Text> {status}
                    </Text>
               );
          }
     } else {
          return null;
     }
};

const getType = (type) => {
     if (type && type === 'interlibrary_loan') {
          type = 'Interlibrary Loan';

          return (
               <Text
                    fontSize={{
                         base: 'xs',
                         lg: 'sm',
                    }}>
                    <Text bold>{translate('holds.type')}:</Text> {type}
               </Text>
          );
     } else {
          return null;
     }
};

const getOnHoldFor = (user) => {
     if (user) {
          return (
               <Text
                    fontSize={{
                         base: 'xs',
                         lg: 'sm',
                    }}>
                    <Text bold>{translate('holds.on_hold_for')}:</Text> {user}
               </Text>
          );
     }
     return null;
};

const getPickupLocation = (location, source) => {
     if (location && source === 'ils') {
          return (
               <Text
                    fontSize={{
                         base: 'xs',
                         lg: 'sm',
                    }}>
                    <Text bold>{translate('holds.pickup_at')}:</Text> {location}
               </Text>
          );
     } else {
          return null;
     }
};

const getPosition = (position, available) => {
     if (position && !available) {
          return (
               <Text
                    fontSize={{
                         base: 'xs',
                         lg: 'sm',
                    }}>
                    <Text bold>{translate('holds.position')}:</Text> {position}
               </Text>
          );
     } else {
          return null;
     }
};

const getExpirationDate = (expiration, available) => {
     if (expiration && available) {
          const expirationDateUnix = moment.unix(expiration);
          let expirationDate = moment(expirationDateUnix).format('MMM D, YYYY');
          return (
               <Text
                    fontSize={{
                         base: 'xs',
                         lg: 'sm',
                    }}>
                    <Text bold>{translate('holds.pickup_by')}:</Text> {expirationDate}
               </Text>
          );
     } else {
          return null;
     }
};

const SelectThawDate = (props) => {
     const { libraryContext, onClose, freezeId, recordId, source, userId, resetGroup } = props;
     let data = props.data;
     let count = props.count;
     const [loading, setLoading] = React.useState(false);
     const [showModal, setShowModal] = React.useState(false);

     const today = new Date();
     const minDate = today.setDate(today.getDate() + 7);
     const [date, setDate] = useState(new Date());
     const [show, setShow] = useState(false);

     const onChange = (event, selectedDate) => {
          const currentDate = selectedDate;
          setShow(false);
          setDate(currentDate);
     };

     const createActionsheetItem = () => {
          if (data) {
               return (
                    <Actionsheet.Item
                         onPress={() => {
                              setShowModal(true);
                         }}>
                         {translate('holds.freeze_all_holds_count', { num: count })}
                    </Actionsheet.Item>
               );
          } else {
               return (
                    <Actionsheet.Item
                         startIcon={<Icon as={MaterialIcons} name="pause" color="trueGray.400" mr="1" size="6" />}
                         onPress={() => {
                              onClose();
                              setShowModal(true);
                         }}>
                         {translate('holds.freeze_hold')}
                    </Actionsheet.Item>
               );
          }
     };

     return (
          <>
               {createActionsheetItem()}
               <Modal
                    isVisible={showModal}
                    avoidKeyboard={true}
                    onBackdropPress={() => {
                         setShowModal(false);
                    }}>
                    <Box
                         bgColor="muted.50"
                         rounded="md"
                         p={1}
                         _text={{ color: 'text.900' }}
                         _dark={{
                              bg: 'muted.800',
                              _text: { color: 'text.50' },
                         }}>
                         <VStack space={3}>
                              <HStack
                                   p={4}
                                   borderBottomWidth="1"
                                   bg="muted.50"
                                   justifyContent="space-between"
                                   alignItems="flex-start"
                                   borderColor="muted.300"
                                   _dark={{
                                        bg: 'muted.800',
                                        borderColor: 'muted.700',
                                   }}>
                                   <Box
                                        _text={{
                                             color: 'text.900',
                                             fontSize: 'md',
                                             fontWeight: 'semibold',
                                             lineHeight: 'sm',
                                        }}
                                        _dark={{
                                             _text: { color: 'text.50' },
                                        }}>
                                        {data ? translate('holds.freeze_all_holds') : translate('holds.freeze_hold')}
                                   </Box>
                                   <Pressable onPress={() => setShowModal(false)}>
                                        <CloseIcon
                                             zIndex="1"
                                             colorScheme="coolGray"
                                             p="2"
                                             bg="transparent"
                                             borderRadius="sm"
                                             _icon={{
                                                  color: 'muted.500',
                                                  size: '4',
                                             }}
                                             _dark={{
                                                  _icon: { color: 'muted.400' },
                                                  _hover: { bg: 'muted.700' },
                                                  _pressed: { bg: 'muted.600' },
                                             }}
                                        />
                                   </Pressable>
                              </HStack>
                              <Box p={4} _text={{ color: 'text.900' }} _hover={{ bg: 'muted.200' }} _pressed={{ bg: 'muted.300' }} _dark={{ _text: { color: 'text.50' } }}>
                                   <DateTimePicker testID="dateTimePicker" value={date} mode="date" display="default" minimumDate={minDate} onChange={onChange} />
                              </Box>
                              <Button.Group
                                   p={4}
                                   flexDirection="row"
                                   justifyContent="flex-end"
                                   flexWrap="wrap"
                                   bg="muted.50"
                                   borderColor="muted.300"
                                   borderTopWidth="1"
                                   _dark={{
                                        bg: 'muted.800',
                                        borderColor: 'muted.700',
                                   }}>
                                   <Button
                                        variant="outline"
                                        onPress={() => {
                                             setShowModal(false);
                                        }}>
                                        {translate('general.cancel')}
                                   </Button>

                                   {data ? (
                                        <Button
                                             isLoading={loading}
                                             isLoadingText={translate('holds.freezing')}
                                             onPress={() => {
                                                  setLoading(true);
                                                  freezeHolds(data, libraryContext.baseUrl, date).then((result) => {
                                                       resetGroup();
                                                       setLoading(false);
                                                       onClose();
                                                       setShowModal(false);
                                                  });
                                             }}>
                                             {translate('holds.freeze_holds')}
                                        </Button>
                                   ) : (
                                        <Button
                                             isLoading={loading}
                                             isLoadingText={translate('holds.freezing')}
                                             onPress={() => {
                                                  setLoading(true);
                                                  freezeHold(freezeId, recordId, source, libraryContext.baseUrl, userId, date).then((result) => {
                                                       resetGroup();
                                                       setLoading(false);
                                                       onClose();
                                                       setShowModal(false);
                                                  });
                                             }}>
                                             {translate('holds.freeze_hold')}
                                        </Button>
                                   )}
                              </Button.Group>
                         </VStack>
                    </Box>
               </Modal>
          </>
     );
};

const SelectPickupLocation = (props) => {
     const { locations, onClose, currentPickupId, holdId, userId, libraryContext, holdsContext, resetGroup } = props;
     let pickupLocation = _.findIndex(locations, function (o) {
          return o.locationId === currentPickupId;
     });
     pickupLocation = _.nth(locations, pickupLocation);
     let pickupLocationCode = _.get(pickupLocation, 'code', '');
     pickupLocation = currentPickupId.concat('_', pickupLocationCode);

     const [loading, setLoading] = useState(false);
     const [showModal, setShowModal] = useState(false);
     let [location, setLocation] = React.useState(pickupLocation);

     return (
          <>
               <Actionsheet.Item
                    startIcon={<Icon as={Ionicons} name="location" color="trueGray.400" mr="1" size="6" />}
                    onPress={() => {
                         setShowModal(true);
                    }}>
                    {translate('pickup_locations.change_location')}
               </Actionsheet.Item>
               <Modal
                    isVisible={showModal}
                    avoidKeyboard={true}
                    onBackdropPress={() => {
                         setShowModal(false);
                    }}>
                    <Box
                         bgColor="muted.50"
                         rounded="md"
                         p={1}
                         _text={{ color: 'text.900' }}
                         _dark={{
                              bg: 'muted.800',
                              _text: { color: 'text.50' },
                         }}>
                         <VStack space={3}>
                              <HStack
                                   p={4}
                                   borderBottomWidth="1"
                                   bg="muted.50"
                                   justifyContent="space-between"
                                   alignItems="flex-start"
                                   borderColor="muted.300"
                                   _dark={{
                                        bg: 'muted.800',
                                        borderColor: 'muted.700',
                                   }}>
                                   <Box
                                        _text={{
                                             color: 'text.900',
                                             fontSize: 'md',
                                             fontWeight: 'semibold',
                                             lineHeight: 'sm',
                                        }}
                                        _dark={{
                                             _text: { color: 'text.50' },
                                        }}>
                                        {translate('pickup_locations.change_hold_location')}
                                   </Box>
                                   <Pressable onPress={() => setShowModal(false)}>
                                        <CloseIcon
                                             zIndex="1"
                                             colorScheme="coolGray"
                                             p="2"
                                             bg="transparent"
                                             borderRadius="sm"
                                             _icon={{
                                                  color: 'muted.500',
                                                  size: '4',
                                             }}
                                             _dark={{
                                                  _icon: { color: 'muted.400' },
                                                  _hover: { bg: 'muted.700' },
                                                  _pressed: { bg: 'muted.600' },
                                             }}
                                        />
                                   </Pressable>
                              </HStack>
                              <Box p={4} _text={{ color: 'text.900' }} _hover={{ bg: 'muted.200' }} _pressed={{ bg: 'muted.300' }} _dark={{ _text: { color: 'text.50' } }}>
                                   <FormControl>
                                        <FormControl.Label>{translate('pickup_locations.select_new_pickup')}</FormControl.Label>
                                        <Select
                                             name="pickupLocations"
                                             selectedValue={location}
                                             minWidth="200"
                                             accessibilityLabel="Select a new pickup location"
                                             _selectedItem={{
                                                  bg: 'tertiary.300',
                                                  endIcon: <CheckIcon size="5" />,
                                             }}
                                             mt={1}
                                             mb={3}
                                             _actionSheet={{
                                                  useRNModal: Platform.OS === 'ios',
                                             }}
                                             onValueChange={(itemValue) => setLocation(itemValue)}>
                                             {locations.map((item, index) => {
                                                  const locationId = item.locationId;
                                                  const code = item.code;
                                                  const id = locationId.concat('_', code);
                                                  return <Select.Item value={id} label={item.name} />;
                                             })}
                                        </Select>
                                   </FormControl>
                              </Box>
                              <Button.Group
                                   p={4}
                                   flexDirection="row"
                                   justifyContent="flex-end"
                                   flexWrap="wrap"
                                   bg="muted.50"
                                   borderColor="muted.300"
                                   borderTopWidth="1"
                                   _dark={{
                                        bg: 'muted.800',
                                        borderColor: 'muted.700',
                                   }}>
                                   <Button
                                        variant="outline"
                                        onPress={() => {
                                             setShowModal(false);
                                        }}>
                                        {translate('general.cancel')}
                                   </Button>
                                   <Button
                                        isLoading={loading}
                                        isLoadingText="Updating..."
                                        onPress={() => {
                                             setLoading(true);
                                             changeHoldPickUpLocation(holdId, location, libraryContext.baseUrl, userId).then((r) => {
                                                  setShowModal(false);
                                                  resetGroup();
                                                  onClose(onClose);
                                                  setLoading(false);
                                             });
                                        }}>
                                        {translate('pickup_locations.change_location')}
                                   </Button>
                              </Button.Group>
                         </VStack>
                    </Box>
               </Modal>
          </>
     );
};