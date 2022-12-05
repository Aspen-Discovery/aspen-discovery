import { MaterialIcons } from '@expo/vector-icons';
import moment from 'moment';
import _ from 'lodash';
import { ScrollView, Actionsheet, Badge, Box, Button, Center, FlatList, Icon, Pressable, Text, HStack, VStack, IconButton, Image } from 'native-base';
import React, { useState } from 'react';
import { SafeAreaView } from 'react-native';
import { useNavigation, useFocusEffect } from '@react-navigation/native';

// custom components and helper files
import { loadingSpinner } from '../../../components/loadingSpinner';
import { translate } from '../../../translations/translations';
import { renewAllCheckouts, renewCheckout, returnCheckout, viewOnlineItem, viewOverDriveItem } from '../../../util/accountActions';
import { getCheckedOutItems, reloadCheckedOutItems } from '../../../util/loadPatron';
import { CheckoutsContext, LibrarySystemContext, UserContext } from '../../../context/initialContext';
import { refreshProfile } from '../../../util/api/user';

export const MyCheckouts = () => {
     const { user, updateUser } = React.useContext(UserContext);
     const { library } = React.useContext(LibrarySystemContext);
     const { checkouts, updateCheckouts } = React.useContext(CheckoutsContext);
     const [isLoading, setLoading] = React.useState(true);
     const [renewAll, setRenewAll] = React.useState(false);

     useFocusEffect(
          React.useCallback(() => {
               const update = async () => {
                    await getCheckedOutItems(library.baseUrl).then((result) => {
                         if (checkouts !== result) {
                              updateCheckouts(result);
                         }
                         setLoading(false);
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

     let numCheckedOut = 0;
     if (!_.isUndefined(user.numCheckedOut)) {
          numCheckedOut = user.numCheckedOut;
     }
     console.log(user);

     const noCheckouts = () => {
          return (
               <Center mt={5} mb={5}>
                    <Text bold fontSize="lg">
                         {translate('checkouts.no_checkouts')}
                    </Text>
               </Center>
          );
     };

     const reloadCheckouts = async () => {
          setLoading(true);
          await reloadCheckedOutItems(library.baseUrl).then((result) => {
               if (checkouts !== result) {
                    updateCheckouts(result);
               }
               setLoading(false);
          });
          refreshProfile(library.baseUrl).then((result) => {
               updateUser(result);
          });
     };

     const refreshCheckouts = async () => {
          setLoading(true);
          await getCheckedOutItems(library.baseUrl).then((result) => {
               if (checkouts !== result) {
                    updateCheckouts(result);
               }
               setLoading(false);
          });
          refreshProfile(library.baseUrl).then((result) => {
               updateUser(result);
          });
     };

     const actionButtons = () => {
          if (numCheckedOut > 0) {
               return (
                    <HStack>
                         <Button
                              isLoading={renewAll}
                              isLoadingText="Renewing all..."
                              size="sm"
                              variant="solid"
                              mr={1}
                              colorScheme="primary"
                              onPress={() => {
                                   setRenewAll(true);
                                   renewAllCheckouts().then((r) => {
                                        refreshCheckouts();
                                        setRenewAll(false);
                                   });
                              }}
                              startIcon={<Icon as={MaterialIcons} name="autorenew" size={5} />}>
                              {translate('checkouts.renew_all')}
                         </Button>
                         <Button
                              size="sm"
                              variant="outline"
                              onPress={() => {
                                   setLoading(true);
                                   reloadCheckouts();
                              }}>
                              {translate('holds.reload_holds')}
                         </Button>
                    </HStack>
               );
          } else {
               return (
                    <HStack>
                         <Button
                              size="sm"
                              variant="outline"
                              onPress={() => {
                                   setLoading(true);
                                   reloadCheckouts();
                              }}>
                              {translate('holds.reload_holds')}
                         </Button>
                    </HStack>
               );
          }
     };

     return (
          <SafeAreaView style={{ flex: 1 }}>
               <Box safeArea={2} bgColor="coolGray.100" borderBottomWidth="1" _dark={{ borderColor: 'gray.600', bg: 'coolGray.700' }} borderColor="coolGray.200" flexWrap="nowrap">
                    <ScrollView horizontal>{actionButtons()}</ScrollView>
               </Box>
               <FlatList data={checkouts} ListEmptyComponent={noCheckouts} renderItem={({ item }) => <Checkout data={item} />} keyExtractor={(item, index) => index.toString()} contentContainerStyle={{ paddingBottom: 30 }} />
          </SafeAreaView>
     );
};

const Checkout = (props) => {
     const checkout = props.data;
     const navigation = useNavigation();
     const { user, updateUser } = React.useContext(UserContext);
     const { library } = React.useContext(LibrarySystemContext);
     const { checkouts, updateCheckouts } = React.useContext(CheckoutsContext);

     const refreshCheckouts = async () => {
          await getCheckedOutItems(library.baseUrl).then((result) => {
               if (checkouts !== result) {
                    updateCheckouts(result);
               }
          });
          refreshProfile(library.baseUrl).then((result) => {
               updateUser(result);
          });
     };

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

     const [access, setAccess] = useState(false);
     const [returning, setReturn] = useState(false);
     const [renewing, setRenew] = useState(false);
     const [isOpen, setIsOpen] = React.useState(false);
     const toggle = () => {
          setIsOpen(!isOpen);
     };

     let canRenew = !checkout.canRenew;
     let allowLinkedAccountAction = false;

     let formatId;
     let label = translate('checkouts.access_online', {
          source: checkout.checkoutSource,
     });

     if (checkout.checkoutSource === 'OverDrive') {
          if (checkout.overdriveRead === 1) {
               formatId = 'ebook-overdrive';
               label = translate('checkouts.read_online', {
                    source: checkout.checkoutSource,
               });
          } else if (checkout.overdriveListen === 1) {
               formatId = 'audiobook-overdrive';
               label = translate('checkouts.listen_online', {
                    source: checkout.checkoutSource,
               });
          } else if (checkout.overdriveVideo === 1) {
               formatId = 'video-streaming';
               label = translate('checkouts.watch_online', {
                    source: checkout.checkoutSource,
               });
          } else if (checkout.overdriveMagazine === 1) {
               formatId = 'magazine-overdrive';
               label = translate('checkouts.read_online', {
                    source: checkout.checkoutSource,
               });
          } else {
               formatId = 'ebook-overdrive';
               label = translate('checkouts.access_online', {
                    source: checkout.checkoutSource,
               });
          }
     }

     let renewMessage = translate('checkouts.renew');
     if (!checkout.canRenew) {
          renewMessage = translate('checkouts.no_renewals');
     }
     if (checkout.autoRenewError) {
          renewMessage = checkout.autoRenewError;
     }
     if (checkout.renewError) {
          renewMessage = checkout.renewError;
     }

     return (
          <Pressable onPress={toggle} borderBottomWidth="1" _dark={{ borderColor: 'gray.600' }} borderColor="coolGray.200" pl="4" pr="5" py="2">
               <HStack space={3} maxW="75%">
                    <Image
                         source={{ uri: checkout.coverUrl }}
                         borderRadius="md"
                         size={{
                              base: '80px',
                              lg: '120px',
                         }}
                         alt={checkout.title}
                    />
                    <VStack>
                         {getTitle(checkout.title)}
                         {isOverdue(checkout.overdue)}
                         {getAuthor(checkout.author)}
                         {getFormat(checkout.format)}
                         {getCheckedOutTo(checkout.user)}
                         {getDueDate(checkout.dueDate)}
                         {willAutoRenew(checkout.autoRenew ?? false, checkout.renewalDate)}
                    </VStack>
               </HStack>
               <Actionsheet isOpen={isOpen} onClose={toggle} size="full">
                    <Actionsheet.Content>
                         <Box w="100%" h={60} px={4} justifyContent="center">
                              <Text
                                   fontSize="18"
                                   color="gray.500"
                                   _dark={{
                                        color: 'gray.300',
                                   }}>
                                   {getTitle(checkout.title)}
                              </Text>
                         </Box>
                         <Actionsheet.Item
                              onPress={() => {
                                   openGroupedWork(checkout.groupedWorkId, checkout.title);
                                   toggle();
                              }}
                              startIcon={<Icon as={MaterialIcons} name="search" color="trueGray.400" mr="1" size="6" />}>
                              {translate('grouped_work.view_item_details')}
                         </Actionsheet.Item>
                         <Actionsheet.Item
                              isDisabled={canRenew}
                              isLoading={renewing}
                              isLoadingText="Renewing..."
                              onPress={() => {
                                   setRenew(true);
                                   renewCheckout(checkout.barcode, checkout.recordId, checkout.source, checkout.itemId, library.baseUrl, checkout.userId).then((result) => {
                                        setRenew(false);
                                        refreshCheckouts();
                                        toggle();
                                   });
                              }}
                              startIcon={<Icon as={MaterialIcons} name="autorenew" color="trueGray.400" mr="1" size="6" />}>
                              {renewMessage}
                         </Actionsheet.Item>
                         {checkout.source === 'overdrive' ? (
                              <Actionsheet.Item
                                   isLoading={access}
                                   isLoadingText="Accessing..."
                                   onPress={() => {
                                        setAccess(true);
                                        viewOverDriveItem(checkout.userId, formatId, checkout.overDriveId, library.baseUrl).then((result) => {
                                             setAccess(false);
                                             toggle();
                                        });
                                   }}
                                   startIcon={<Icon as={MaterialIcons} name="book" color="trueGray.400" mr="1" size="6" />}>
                                   {label}
                              </Actionsheet.Item>
                         ) : null}
                         {checkout.accessOnlineUrl != null ? (
                              <>
                                   <Actionsheet.Item
                                        isLoading={access}
                                        isLoadingText="Accessing..."
                                        onPress={() => {
                                             setAccess(true);
                                             viewOnlineItem(checkout.userId, checkout.recordId, checkout.source, checkout.accessOnlineUrl, library.baseUrl).then((result) => {
                                                  setAccess(false);
                                                  toggle();
                                             });
                                        }}
                                        startIcon={<Icon as={MaterialIcons} name="book" color="trueGray.400" mr="1" size="6" />}>
                                        {label}
                                   </Actionsheet.Item>
                                   <Actionsheet.Item
                                        isLoading={returning}
                                        isLoadingText="Returning..."
                                        onPress={() => {
                                             setReturn(true);
                                             returnCheckout(checkout.userId, checkout.recordId, checkout.source, checkout.overDriveId, library.baseUrl).then((result) => {
                                                  setReturn(false);
                                                  refreshCheckouts();
                                                  toggle();
                                             });
                                        }}
                                        startIcon={<Icon as={MaterialIcons} name="logout" color="trueGray.400" mr="1" size="6" />}>
                                        {translate('checkouts.return_now')}
                                   </Actionsheet.Item>
                              </>
                         ) : null}
                         {checkout.canReturnEarly && allowLinkedAccountAction ? (
                              <>
                                   <Actionsheet.Item
                                        isLoading={returning}
                                        isLoadingText="Returning..."
                                        onPress={() => {
                                             setReturn(true);
                                             returnCheckout(checkout.userId, checkout.recordId, checkout.source, checkout.overDriveId, library.baseUrl).then((result) => {
                                                  setReturn(false);
                                                  refreshCheckouts();
                                                  toggle();
                                             });
                                        }}
                                        startIcon={<Icon as={MaterialIcons} name="logout" color="trueGray.400" mr="1" size="6" />}>
                                        {translate('checkouts.return_now')}
                                   </Actionsheet.Item>
                              </>
                         ) : null}
                    </Actionsheet.Content>
               </Actionsheet>
          </Pressable>
     );
};

const isOverdue = (props) => {
     if (props.overdue) {
          return (
               <Text>
                    <Badge colorScheme="danger" rounded="4px" mt={-0.5}>
                         {translate('checkouts.overdue')}
                    </Badge>
               </Text>
          );
     } else {
          return null;
     }
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

const getCheckedOutTo = (props) => {
     const { user } = React.useContext(UserContext);
     const [checkedOutTo, setCheckedOutTo] = React.useState();
     if (user.id !== checkedOutTo) {
          return (
               <Text
                    fontSize={{
                         base: 'xs',
                         lg: 'sm',
                    }}>
                    <Text bold>Checked Out To:</Text> {props}
               </Text>
          );
     } else {
          return null;
     }
};

const getDueDate = (date) => {
     const dueDate = moment.unix(date);
     const itemDueOn = moment(dueDate).format('MMM D, YYYY');
     return (
          <Text
               fontSize={{
                    base: 'xs',
                    lg: 'sm',
               }}>
               <Text bold>{translate('checkouts.due')}:</Text> {itemDueOn}
          </Text>
     );
};

const willAutoRenew = (props) => {
     if (props.autoRenew === 1) {
          return (
               <Box mt={1} p={0.5} bgColor="muted.100">
                    <Text
                         fontSize={{
                              base: 'xs',
                              lg: 'sm',
                         }}>
                         <Text bold>{translate('checkouts.auto_renew')}:</Text> {props.renewalDate}
                    </Text>
               </Box>
          );
     } else {
          return null;
     }
};