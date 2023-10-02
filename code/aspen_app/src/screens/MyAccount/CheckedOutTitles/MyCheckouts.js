import { MaterialIcons } from '@expo/vector-icons';
import moment from 'moment';
import _ from 'lodash';
import { ScrollView, Actionsheet, FormControl, Select, Box, Button, Center, FlatList, Icon, Pressable, Text, HStack, VStack, CheckIcon, Image } from 'native-base';
import React, { useState } from 'react';
import { Platform, SafeAreaView } from 'react-native';
import { useNavigation, useFocusEffect } from '@react-navigation/native';
import { useQueryClient, useQuery, useIsFetching } from '@tanstack/react-query';
import CachedImage from 'expo-cached-image';

// custom components and helper files
import { loadingSpinner } from '../../../components/loadingSpinner';
import { renewAllCheckouts, renewCheckout, returnCheckout, viewOnlineItem, viewOverDriveItem } from '../../../util/accountActions';
import { CheckoutsContext, LanguageContext, LibrarySystemContext, SystemMessagesContext, UserContext } from '../../../context/initialContext';
import { getPatronCheckedOutItems, reloadProfile } from '../../../util/api/user';
import { getAuthor, getCheckedOutTo, getCleanTitle, getDueDate, getFormat, getRenewalCount, getTitle, isOverdue, willAutoRenew } from '../../../helpers/item';
import { navigateStack } from '../../../helpers/RootNavigator';
import { formatDiscoveryVersion } from '../../../util/loadLibrary';
import { getTermFromDictionary, getTranslationsWithValues } from '../../../translations/TranslationService';
import { DisplaySystemMessage } from '../../../components/Notifications';

export const MyCheckouts = () => {
     const isFetchingCheckouts = useIsFetching({ queryKey: ['checkouts'] });
     const queryClient = useQueryClient();
     const navigation = useNavigation();
     const { user, updateUser } = React.useContext(UserContext);
     const { library } = React.useContext(LibrarySystemContext);
     const { checkouts, updateCheckouts } = React.useContext(CheckoutsContext);
     const { language } = React.useContext(LanguageContext);
     const [isLoading, setLoading] = React.useState(false);
     const [renewAll, setRenewAll] = React.useState(false);
     const [source, setSource] = React.useState('all');
     const { systemMessages, updateSystemMessages } = React.useContext(SystemMessagesContext);

     const [checkoutsBy, setCheckoutBy] = React.useState({
          ils: 'Checked Out Titles for Physical Materials',
          hoopla: 'Checked Out Titles for Hoopla',
          overdrive: 'Checked Out Titles for OverDrive',
          axis_360: 'Checked Out Titles for Axis 360',
          cloud_library: 'Checked Out Titles for cloudLibrary',
          all: 'Checked Out Titles',
     });

     React.useLayoutEffect(() => {
          navigation.setOptions({
               headerLeft: () => <Box />,
          });
     }, [navigation]);

     useQuery(['checkouts', user.id, library.baseUrl, language, source], () => getPatronCheckedOutItems(source, library.baseUrl, true, language), {
          onSuccess: (data) => {
               updateCheckouts(data);
          },
          onSettle: (data) => setLoading(false),
     });

     const toggleSource = async (value) => {
          setSource(value);
          setLoading(true);
          console.log('toggleSource: ' + value);
          if (!_.isNull(value)) {
               if (value === 'ils') {
                    navigation.setOptions({ title: checkoutsBy.ils });
               } else if (value === 'overdrive') {
                    navigation.setOptions({ title: checkoutsBy.overdrive });
               } else if (value === 'cloud_library') {
                    navigation.setOptions({ title: checkoutsBy.cloud_library });
               } else if (value === 'hoopla') {
                    navigation.setOptions({ title: checkoutsBy.hoopla });
               } else if (value === 'axis360') {
                    navigation.setOptions({ title: checkoutsBy.axis_360 });
               } else {
                    navigation.setOptions({ title: checkoutsBy.all });
               }
          }
     };

     useFocusEffect(
          React.useCallback(() => {
               const update = async () => {
                    let tmp = checkoutsBy;
                    let term = '';

                    term = getTermFromDictionary(language, 'checkouts_for_all');
                    if (!term.includes('%1%')) {
                         tmp = _.set(tmp, 'all', term);
                         setCheckoutBy(tmp);
                    }

                    term = getTermFromDictionary(language, 'checkouts_for_ils');
                    if (!term.includes('%1%')) {
                         tmp = _.set(tmp, 'ils', term);
                         setCheckoutBy(tmp);
                    }

                    term = getTermFromDictionary(language, 'checkouts_for_overdrive');
                    if (!term.includes('%1%')) {
                         tmp = _.set(tmp, 'overdrive', term);
                         setCheckoutBy(tmp);
                    }

                    term = getTermFromDictionary(language, 'checkouts_for_hoopla');
                    if (!term.includes('%1%')) {
                         tmp = _.set(tmp, 'hoopla', term);
                         setCheckoutBy(tmp);
                    }

                    term = getTermFromDictionary(language, 'checkouts_for_cloud_library');
                    if (!term.includes('%1%')) {
                         tmp = _.set(tmp, 'cloud_library', term);
                         setCheckoutBy(tmp);
                    }

                    term = getTermFromDictionary(language, 'checkouts_for_axis_360');
                    if (!term.includes('%1%')) {
                         tmp = _.set(tmp, 'axis_360', term);
                         setCheckoutBy(tmp);
                    }

                    setLoading(false);
               };
               update().then(() => {
                    return () => update();
               });
          }, [language])
     );

     if (isFetchingCheckouts) {
          return loadingSpinner();
     }

     let numCheckedOut = 0;
     if (!_.isUndefined(user.numCheckedOut)) {
          numCheckedOut = user.numCheckedOut;
     }

     const noCheckouts = () => {
          return (
               <Center mt={5} mb={5}>
                    <Text bold fontSize="lg">
                         {getTermFromDictionary(language, 'no_checkouts')}
                    </Text>
               </Center>
          );
     };

     const reloadCheckouts = async () => {
          setLoading(true);
          queryClient.invalidateQueries({ queryKey: ['user', library.baseUrl, language] });
          queryClient.invalidateQueries({ queryKey: ['checkouts', user.id, library.baseUrl, language, source] });
     };

     const refreshCheckouts = async () => {
          setLoading(true);
          queryClient.invalidateQueries({ queryKey: ['user', library.baseUrl, language] });
          queryClient.invalidateQueries({ queryKey: ['checkouts', user.id, library.baseUrl, language, source] });
     };

     const actionButtons = () => {
          if (numCheckedOut > 0) {
               return (
                    <HStack space={2}>
                         <Button
                              isLoading={renewAll}
                              isLoadingText={getTermFromDictionary(language, 'renewing_all', true)}
                              size="sm"
                              variant="solid"
                              colorScheme="primary"
                              onPress={() => {
                                   setRenewAll(true);
                                   renewAllCheckouts(library.baseUrl).then((r) => {
                                        refreshCheckouts();
                                        setRenewAll(false);
                                   });
                              }}
                              startIcon={<Icon as={MaterialIcons} name="autorenew" size={5} />}>
                              {getTermFromDictionary(language, 'checkout_renew_all')}
                         </Button>
                         <Button
                              size="sm"
                              variant="outline"
                              onPress={() => {
                                   setLoading(true);
                                   reloadCheckouts();
                              }}>
                              {getTermFromDictionary(language, 'checkouts_reload')}
                         </Button>
                         <FormControl w={175}>
                              <Select
                                   isReadOnly={Platform.OS === 'android'}
                                   name="holdSource"
                                   selectedValue={source}
                                   accessibilityLabel={getTermFromDictionary(language, 'filter_by_source_label')}
                                   _selectedItem={{
                                        bg: 'tertiary.300',
                                        endIcon: <CheckIcon size="5" />,
                                   }}
                                   onValueChange={(itemValue) => toggleSource(itemValue)}>
                                   <Select.Item label={getTermFromDictionary(language, 'filter_by_all') + ' (' + (user.numCheckedOut ?? 0) + ')'} value="all" key={0} />
                                   <Select.Item label={getTermFromDictionary(language, 'filter_by_ils') + ' (' + (user.numCheckedOutIls ?? 0) + ')'} value="ils" key={1} />
                                   <Select.Item label={getTermFromDictionary(language, 'filter_by_overdrive') + ' (' + (user.numCheckedOutOverDrive ?? 0) + ')'} value="overdrive" key={2} />
                                   <Select.Item label={getTermFromDictionary(language, 'filter_by_hoopla') + ' (' + (user.numCheckedOut_Hoopla ?? 0) + ')'} value="hoopla" key={3} />
                                   <Select.Item label={getTermFromDictionary(language, 'filter_by_cloud_library') + ' (' + (user.numCheckedOut_cloudLibrary ?? 0) + ')'} value="cloud_library" key={4} />
                                   <Select.Item label={getTermFromDictionary(language, 'filter_by_axis_360') + ' (' + (user.numCheckedOut_axis360 ?? 0) + ')'} value="axis360" key={5} />
                              </Select>
                         </FormControl>
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
                              {getTermFromDictionary(language, 'checkouts_reload')}
                         </Button>
                    </HStack>
               );
          }
     };

     const showSystemMessage = () => {
          if (_.isArray(systemMessages)) {
               return systemMessages.map((obj, index, collection) => {
                    if (obj.showOn === '0' || obj.showOn === '1' || obj.showOn === '2') {
                         return <DisplaySystemMessage style={obj.style} message={obj.message} dismissable={obj.dismissable} id={obj.id} all={systemMessages} url={library.baseUrl} updateSystemMessages={updateSystemMessages} queryClient={queryClient} />;
                    }
               });
          }
          return null;
     };

     return (
          <SafeAreaView style={{ flex: 1 }}>
               <Box safeArea={2} bgColor="coolGray.100" borderBottomWidth="1" _dark={{ borderColor: 'gray.600', bg: 'coolGray.700' }} borderColor="coolGray.200" flexWrap="nowrap">
                    {showSystemMessage()}
                    <ScrollView horizontal>{actionButtons()}</ScrollView>
               </Box>
               <FlatList data={checkouts} ListEmptyComponent={noCheckouts} renderItem={({ item }) => <Checkout data={item} reloadCheckouts={reloadCheckouts} />} keyExtractor={(item, index) => index.toString()} contentContainerStyle={{ paddingBottom: 30 }} />
          </SafeAreaView>
     );
};

const Checkout = (props) => {
     const checkout = props.data;
     const reloadCheckouts = props.reloadCheckouts;
     const navigation = useNavigation();
     const { user, updateUser } = React.useContext(UserContext);
     const { library } = React.useContext(LibrarySystemContext);
     const { language } = React.useContext(LanguageContext);
     const { checkouts, updateCheckouts } = React.useContext(CheckoutsContext);
     const version = formatDiscoveryVersion(library.discoveryVersion);

     const openGroupedWork = (item, title) => {
          if (version >= '23.01.00') {
               navigateStack('AccountScreenTab', 'MyCheckout', {
                    id: item,
                    title: getCleanTitle(title),
                    url: library.baseUrl,
                    userContext: user,
                    libraryContext: library,
                    prevRoute: 'MyCheckouts',
               });
          } else {
               navigateStack('AccountScreenTab', 'MyCheckout221200', {
                    id: item,
                    title: getCleanTitle(title),
                    url: library.baseUrl,
                    userContext: user,
                    libraryContext: library,
               });
          }
     };

     const [access, setAccess] = useState(false);
     const [returning, setReturn] = useState(false);
     const [renewing, setRenew] = useState(false);
     const [isOpen, setIsOpen] = React.useState(false);
     const [label, setAccessLabel] = React.useState('Access Online');
     const toggle = () => {
          setIsOpen(!isOpen);
     };

     let canRenew = !checkout.canRenew;
     let allowLinkedAccountAction = true;
     if (version < '22.05.00') {
          if (checkout.userId !== user.id) {
               allowLinkedAccountAction = false;
          }
     }

     let formatId;
     React.useEffect(() => {
          async function preloadTranslations() {
               if (checkout?.checkoutSource) {
                    if (checkout.checkoutSource === 'OverDrive') {
                         if (checkout.overdriveRead === 1) {
                              formatId = 'ebook-overdrive';
                              await getTranslationsWithValues('checkout_read_online', 'OverDrive', language, library.baseUrl).then((term) => {
                                   setAccessLabel(_.toString(term));
                              });
                         } else if (checkout.overdriveListen === 1) {
                              formatId = 'audiobook-overdrive';
                              await getTranslationsWithValues('checkout_listen_online', 'OverDrive', language, library.baseUrl).then((term) => {
                                   setAccessLabel(_.toString(term));
                              });
                         } else if (checkout.overdriveVideo === 1) {
                              formatId = 'video-streaming';
                              await getTranslationsWithValues('checkout_watch_online', 'OverDrive', language, library.baseUrl).then((term) => {
                                   setAccessLabel(_.toString(term));
                              });
                         } else if (checkout.overdriveMagazine === 1) {
                              formatId = 'magazine-overdrive';
                              await getTranslationsWithValues('checkout_read_online', 'OverDrive', language, library.baseUrl).then((term) => {
                                   setAccessLabel(_.toString(term));
                              });
                         } else {
                              await getTranslationsWithValues('checkout_access_online', 'OverDrive', language, library.baseUrl).then((term) => {
                                   setAccessLabel(_.toString(term));
                              });
                         }
                    } else {
                         await getTranslationsWithValues('checkout_access_online', checkout.checkoutSource, language, library.baseUrl).then((term) => {
                              setAccessLabel(_.toString(term));
                         });
                    }
               }
          }
          preloadTranslations();
     }, [language]);

     let returnEarly = false;
     if (checkout.canReturnEarly === 1 || checkout.canReturnEarly === '1' || checkout.canReturnEarly === true || checkout.canReturnEarly === 'true') {
          returnEarly = true;
     }

     let renewMessage = false;
     if (checkout.canRenew) {
          renewMessage = getTermFromDictionary(language, 'checkout_renew');
     } else {
          renewMessage = getTermFromDictionary(language, 'not_eligible_for_renewals');
     }
     if (checkout.autoRenew === '1' || checkout.autoRenew === 1) {
          renewMessage = getTermFromDictionary(language, 'if_eligible_auto_renew');
     }
     if (checkout.autoRenewError) {
          renewMessage = checkout.autoRenewError;
     }
     if (checkout.renewError) {
          renewMessage = checkout.renewError;
     }

     const imageUrl = checkout.coverUrl;

     return (
          <Pressable onPress={toggle} borderBottomWidth="1" _dark={{ borderColor: 'gray.600' }} borderColor="coolGray.200" pl="4" pr="5" py="2">
               <HStack space={3} maxW="75%">
                    <CachedImage
                         cacheKey={checkout.groupedWorkId}
                         alt={checkout.title}
                         source={{
                              uri: `${imageUrl}`,
                              expiresIn: 86400,
                         }}
                         style={{
                              width: 100,
                              height: 150,
                              borderRadius: 4,
                         }}
                         resizeMode="cover"
                         placeholderContent={
                              <Box
                                   bg="warmGray.50"
                                   _dark={{
                                        bgColor: 'coolGray.800',
                                   }}
                                   width={{
                                        base: 100,
                                        lg: 200,
                                   }}
                                   height={{
                                        base: 150,
                                        lg: 250,
                                   }}
                              />
                         }
                    />
                    <VStack>
                         {getTitle(checkout.title)}
                         {isOverdue(checkout.overdue)}
                         {getAuthor(checkout.author)}
                         {getFormat(checkout.format, checkout.source)}
                         {getCheckedOutTo(checkout.user)}
                         {getDueDate(checkout.dueDate)}
                         {getRenewalCount(checkout.renewCount ?? 0, checkout.maxRenewals ?? null)}
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
                              {getTermFromDictionary(language, 'view_item_details')}
                         </Actionsheet.Item>
                         {renewMessage ? (
                              <Actionsheet.Item
                                   isDisabled={canRenew}
                                   isLoading={renewing}
                                   isLoadingText={getTermFromDictionary(language, 'renewing', true)}
                                   onPress={() => {
                                        setRenew(true);
                                        renewCheckout(checkout.barcode, checkout.recordId, checkout.source, checkout.itemId, library.baseUrl, checkout.userId).then((result) => {
                                             setRenew(false);
                                             reloadCheckouts();
                                             toggle();
                                        });
                                   }}
                                   startIcon={<Icon as={MaterialIcons} name="autorenew" color="trueGray.400" mr="1" size="6" />}>
                                   {renewMessage}
                              </Actionsheet.Item>
                         ) : null}
                         {checkout.source === 'overdrive' ? (
                              <Actionsheet.Item
                                   isLoading={access}
                                   isLoadingText={getTermFromDictionary(language, 'accessing', true)}
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
                                        isLoadingText={getTermFromDictionary(language, 'accessing', true)}
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
                                        isLoadingText={getTermFromDictionary(language, 'returning', true)}
                                        onPress={() => {
                                             setReturn(true);
                                             returnCheckout(checkout.userId, checkout.recordId, checkout.source, checkout.overDriveId, library.baseUrl, version, checkout.transactionId).then((result) => {
                                                  setReturn(false);
                                                  reloadCheckouts();
                                                  toggle();
                                             });
                                        }}
                                        startIcon={<Icon as={MaterialIcons} name="logout" color="trueGray.400" mr="1" size="6" />}>
                                        {getTermFromDictionary(language, 'checkout_return_now')}
                                   </Actionsheet.Item>
                              </>
                         ) : null}
                         {returnEarly && allowLinkedAccountAction ? (
                              <>
                                   <Actionsheet.Item
                                        isLoading={returning}
                                        isLoadingText={getTermFromDictionary(language, 'returning', true)}
                                        onPress={() => {
                                             setReturn(true);
                                             returnCheckout(checkout.userId, checkout.recordId, checkout.source, checkout.overDriveId, library.baseUrl, version, checkout.transactionId).then((result) => {
                                                  setReturn(false);
                                                  reloadCheckouts();
                                                  toggle();
                                             });
                                        }}
                                        startIcon={<Icon as={MaterialIcons} name="logout" color="trueGray.400" mr="1" size="6" />}>
                                        {getTermFromDictionary(language, 'checkout_return_now')}
                                   </Actionsheet.Item>
                              </>
                         ) : null}
                    </Actionsheet.Content>
               </Actionsheet>
          </Pressable>
     );
};