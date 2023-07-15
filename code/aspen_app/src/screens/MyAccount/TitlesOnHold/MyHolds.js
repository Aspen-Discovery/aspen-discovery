import { ScrollView, Box, Button, Center, Text, HStack, Checkbox, Select, FormControl, CheckIcon, Heading } from 'native-base';
import React from 'react';
import { useNavigation } from '@react-navigation/native';
import { Platform, SafeAreaView, SectionList } from 'react-native';
import { useFocusEffect } from '@react-navigation/native';
import _ from 'lodash';

// custom components and helper files
import { loadingSpinner } from '../../../components/loadingSpinner';
import { HoldsContext, LanguageContext, LibrarySystemContext, UserContext } from '../../../context/initialContext';
import { getPickupLocations } from '../../../util/loadLibrary';
import { getPatronHolds, refreshProfile, reloadProfile } from '../../../util/api/user';
import { MyHold, ManageAllHolds, ManageSelectedHolds } from './MyHold';
import { DisplayMessage } from '../../../components/Notifications';
import { getTermFromDictionary, getTranslationsWithValues } from '../../../translations/TranslationService';
import { useQueryClient, useQuery, useIsFetching } from '@tanstack/react-query';

export const MyHolds = () => {
     const isFetchingHolds = useIsFetching({ queryKey: ['holds'] });
     const queryClient = useQueryClient();
     const navigation = useNavigation();
     const { user, updateUser } = React.useContext(UserContext);
     const { library } = React.useContext(LibrarySystemContext);
     const { holds, updateHolds, pendingSortMethod, readySortMethod, updatePendingSortMethod, updateReadySortMethod } = React.useContext(HoldsContext);
     const { language } = React.useContext(LanguageContext);
     const [holdSource, setHoldSource] = React.useState('all');
     const [isLoading, setLoading] = React.useState(false);
     const [values, setGroupValues] = React.useState([]);
     const [date, setNewDate] = React.useState();
     const [pickupLocations, setPickupLocations] = React.useState([]);

     const [sortBy, setSortBy] = React.useState({
          title: 'Sort by Title',
          author: 'Sort by Author',
          format: 'Sort by Format',
          status: 'Sort by Status',
          date_placed: 'Sort by Date Placed',
          position: 'Sort by Position',
          pickup_location: 'Sort by Pickup Location',
          library_account: 'Sort by Library Account',
          expiration: 'Sort by Expiration Date',
     });

     React.useLayoutEffect(() => {
          navigation.setOptions({
               headerLeft: () => <Box />,
          });
     }, [navigation]);

     /*useQuery(['holds', user.id, library.baseUrl, language], () => getPatronHolds(readySortMethod, pendingSortMethod, holdSource, library.baseUrl, true, language), {
          onSuccess: (data) => {
               updateHolds(data);
               setLoading(false);
          },
     });*/

     const toggleReadySort = async (value) => {
          updateReadySortMethod(value);
          const sortedHolds = sortHolds(holds, pendingSortMethod, value);
          queryClient.setQueryData(['holds', library.baseUrl, language], sortedHolds);
          updateHolds(sortedHolds);
     };

     const togglePendingSort = async (value) => {
          updatePendingSortMethod(value);
          const sortedHolds = sortHolds(holds, value, readySortMethod);
          queryClient.setQueryData(['holds', library.baseUrl, language], sortedHolds);
          updateHolds(sortedHolds);
     };

     const toggleHoldSource = async (value) => {
          setHoldSource(value);
          setLoading(true);
          if (!_.isNull(value)) {
               if (value === 'ils') {
                    navigation.setOptions({ title: getTermFromDictionary(language, 'titles_on_hold_for_ils') });
               } else if (value === 'overdrive') {
                    navigation.setOptions({ title: getTermFromDictionary(language, 'titles_on_hold_for_overdrive') });
               } else if (value === 'cloud_library') {
                    navigation.setOptions({ title: getTermFromDictionary(language, 'titles_on_hold_for_cloud_library') });
               } else if (value === 'axis360') {
                    navigation.setOptions({ title: getTermFromDictionary(language, 'titles_on_hold_for_axis_360') });
               } else {
                    navigation.setOptions({ title: getTermFromDictionary(language, 'titles_on_hold_for_all') });
               }
          }
          queryClient.invalidateQueries({ queryKey: ['holds', library.baseUrl, language] });
     };

     useFocusEffect(
          React.useCallback(() => {
               const update = async () => {
                    await getPickupLocations(library.baseUrl).then((result) => {
                         if (pickupLocations !== result) {
                              setPickupLocations(result);
                         }
                    });

                    let tmp = sortBy;
                    let term = '';

                    term = getTermFromDictionary(language, 'sort_by_title');

                    if (!term.includes('%1%')) {
                         tmp = _.set(tmp, 'title', term);
                         setSortBy(tmp);
                    }

                    term = getTermFromDictionary(language, 'sort_by_author');
                    if (!term.includes('%1%')) {
                         tmp = _.set(tmp, 'author', term);
                         setSortBy(tmp);
                    }

                    term = getTermFromDictionary(language, 'sort_by_format');
                    if (!term.includes('%1%')) {
                         tmp = _.set(tmp, 'format', term);
                         setSortBy(tmp);
                    }

                    term = getTermFromDictionary(language, 'sort_by_status');
                    if (!term.includes('%1%')) {
                         tmp = _.set(tmp, 'status', term);
                         setSortBy(tmp);
                    }

                    term = getTermFromDictionary(language, 'sort_by_date_placed');
                    if (!term.includes('%1%')) {
                         tmp = _.set(tmp, 'date_placed', term);
                         setSortBy(tmp);
                    }

                    term = getTermFromDictionary(language, 'sort_by_position');
                    if (!term.includes('%1%')) {
                         tmp = _.set(tmp, 'position', term);
                         setSortBy(tmp);
                    }

                    term = getTermFromDictionary(language, 'sort_by_pickup_location');
                    if (!term.includes('%1%')) {
                         tmp = _.set(tmp, 'pickup_location', term);
                         setSortBy(tmp);
                    }

                    term = getTermFromDictionary(language, 'sort_by_library_account');
                    if (!term.includes('%1%')) {
                         tmp = _.set(tmp, 'library_account', term);
                         setSortBy(tmp);
                    }

                    term = getTermFromDictionary(language, 'sort_by_expiration');
                    if (!term.includes('%1%')) {
                         tmp = _.set(tmp, 'expiration', term);
                         setSortBy(tmp);
                    }

                    setLoading(false);
               };
               update().then(() => {
                    return () => update();
               });
          }, [language])
     );

     if (isFetchingHolds) {
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
          queryClient.invalidateQueries({ queryKey: ['holds', user.id, library.baseUrl, language] });
          queryClient.invalidateQueries({ queryKey: ['user', library.baseUrl, language] });
     };

     const handleDateChange = (date) => {
          setNewDate(date);
     };

     const noHolds = (title) => {
          if (title === 'Pending') {
               return (
                    <Center safeArea={2}>
                         <Text bold fontSize="lg">
                              {getTermFromDictionary(language, 'pending_holds_none')}
                         </Text>
                    </Center>
               );
          } else {
               return (
                    <Center safeArea={2}>
                         <Text bold fontSize="lg">
                              {getTermFromDictionary(language, 'holds_ready_for_pickup_none')}
                         </Text>
                    </Center>
               );
          }
     };

     const refreshHolds = async () => {
          setLoading(true);
          queryClient.invalidateQueries({ queryKey: ['holds', user.id, library.baseUrl, language] });
          queryClient.invalidateQueries({ queryKey: ['user', library.baseUrl, language] });
          useQuery(['holds', user.id, library.baseUrl, language], () => getPatronHolds(readySortMethod, pendingSortMethod, holdSource, library.baseUrl, true, language), {
               onSuccess: (data) => {
                    updateHolds(data);
               },
          });
          setLoading(false);
     };

     const actionButtons = (section) => {
          let showSelectOptions = false;
          if (values.length >= 1) {
               showSelectOptions = true;
          }

          if (section === 'pending') {
               if (showSelectOptions) {
                    return (
                         <Box safeArea={2}>
                              <ScrollView horizontal>
                                   <HStack space={2}>
                                        <FormControl w={150}>
                                             <Select
                                                  isReadOnly={Platform.OS === 'android'}
                                                  name="sortBy"
                                                  selectedValue={pendingSortMethod}
                                                  accessibilityLabel={getTermFromDictionary(language, 'select_sort_method')}
                                                  _selectedItem={{
                                                       bg: 'tertiary.300',
                                                       endIcon: <CheckIcon size="5" />,
                                                  }}
                                                  onValueChange={(itemValue) => togglePendingSort(itemValue)}>
                                                  <Select.Item label={sortBy.title} value="sortTitle" key={0} />
                                                  <Select.Item label={sortBy.author} value="author" key={1} />
                                                  <Select.Item label={sortBy.format} value="format" key={2} />
                                                  <Select.Item label={sortBy.status} value="status" key={3} />
                                                  <Select.Item label={sortBy.date_placed} value="placed" key={4} />
                                                  <Select.Item label={sortBy.position} value="position" key={5} />
                                                  <Select.Item label={sortBy.pickup_location} value="location" key={6} />
                                                  <Select.Item label={sortBy.library_account} value="libraryAccount" key={7} />
                                             </Select>
                                        </FormControl>
                                        <ManageSelectedHolds language={language} selectedValues={values} onAllDateChange={handleDateChange} selectedReactivationDate={date} resetGroup={resetGroup} />
                                        <Button size="sm" variant="outline" mr={1} onPress={() => clearGroupValue()}>
                                             {getTermFromDictionary(language, 'holds_clear_selections')}
                                        </Button>
                                   </HStack>
                              </ScrollView>
                         </Box>
                    );
               }

               return (
                    <Box safeArea={2}>
                         <ScrollView horizontal>
                              <HStack space={2}>
                                   <FormControl w={150}>
                                        <Select
                                             isReadOnly={Platform.OS === 'android'}
                                             name="sortBy"
                                             selectedValue={pendingSortMethod}
                                             accessibilityLabel={getTermFromDictionary(language, 'select_sort_method')}
                                             _selectedItem={{
                                                  bg: 'tertiary.300',
                                                  endIcon: <CheckIcon size="5" />,
                                             }}
                                             onValueChange={(itemValue) => togglePendingSort(itemValue)}>
                                             <Select.Item label={sortBy.title} value="sortTitle" key={0} />
                                             <Select.Item label={sortBy.author} value="author" key={1} />
                                             <Select.Item label={sortBy.format} value="format" key={2} />
                                             <Select.Item label={sortBy.status} value="status" key={3} />
                                             <Select.Item label={sortBy.date_placed} value="placed" key={4} />
                                             <Select.Item label={sortBy.position} value="position" key={5} />
                                             <Select.Item label={sortBy.pickup_location} value="location" key={6} />
                                             <Select.Item label={sortBy.library_account} value="libraryAccount" key={7} />
                                        </Select>
                                   </FormControl>
                                   <ManageAllHolds language={language} data={holds} onDateChange={handleDateChange} selectedReactivationDate={date} resetGroup={resetGroup} />
                              </HStack>
                         </ScrollView>
                    </Box>
               );
          }

          if (section === 'ready') {
               return (
                    <Box safeArea={2}>
                         <ScrollView horizontal>
                              <HStack space={2}>
                                   <FormControl w={150}>
                                        <Select
                                             isReadOnly={Platform.OS === 'android'}
                                             name="sortBy"
                                             selectedValue={readySortMethod}
                                             accessibilityLabel={getTermFromDictionary(language, 'select_sort_method')}
                                             _selectedItem={{
                                                  bg: 'tertiary.300',
                                                  endIcon: <CheckIcon size="5" />,
                                             }}
                                             onValueChange={(itemValue) => toggleReadySort(itemValue)}>
                                             <Select.Item label={sortBy.title} value="sortTitle" key={0} />
                                             <Select.Item label={sortBy.author} value="author" key={1} />
                                             <Select.Item label={sortBy.format} value="format" key={2} />
                                             <Select.Item label={sortBy.expiration} value="expire" key={3} />
                                             <Select.Item label={sortBy.date_placed} value="placed" key={4} />
                                             <Select.Item label={sortBy.pickup_location} value="location" key={5} />
                                             <Select.Item label={sortBy.library_account} value="libraryAccount" key={6} />
                                        </Select>
                                   </FormControl>
                              </HStack>
                         </ScrollView>
                    </Box>
               );
          }

          return (
               <Box
                    safeArea={2}
                    bgColor="coolGray.100"
                    borderBottomWidth="1"
                    _dark={{
                         borderColor: 'gray.600',
                         bg: 'coolGray.700',
                    }}
                    borderColor="coolGray.200"
                    flexWrap="nowrap">
                    <ScrollView horizontal>
                         <HStack space={2}>
                              <Button
                                   size="sm"
                                   variant="outline"
                                   onPress={() => {
                                        refreshHolds();
                                   }}>
                                   {getTermFromDictionary(language, 'holds_reload')}
                              </Button>
                              <FormControl w={175}>
                                   <Select
                                        name="holdSource"
                                        selectedValue={holdSource}
                                        accessibilityLabel="Filter By Source"
                                        _selectedItem={{
                                             bg: 'tertiary.300',
                                             endIcon: <CheckIcon size="5" />,
                                        }}
                                        onValueChange={(itemValue) => toggleHoldSource(itemValue)}>
                                        <Select.Item label={getTermFromDictionary(language, 'filter_by_all') + ' (' + (user.numCheckedOut ?? 0) + ')'} value="all" key={0} />
                                        <Select.Item label={getTermFromDictionary(language, 'filter_by_ils') + ' (' + (user.numCheckedOutIls ?? 0) + ')'} value="ils" key={1} />
                                        <Select.Item label={getTermFromDictionary(language, 'filter_by_overdrive') + ' (' + (user.numCheckedOutOverDrive ?? 0) + ')'} value="overdrive" key={2} />
                                        <Select.Item label={getTermFromDictionary(language, 'filter_by_cloud_library') + ' (' + (user.numCheckedOut_cloudLibrary ?? 0) + ')'} value="cloud_library" key={3} />
                                        <Select.Item label={getTermFromDictionary(language, 'filter_by_axis_360') + ' (' + (user.numCheckedOut_axis360 ?? 0) + ')'} value="axis360" key={4} />
                                   </Select>
                              </FormControl>
                         </HStack>
                    </ScrollView>
               </Box>
          );
     };

     const displaySectionHeader = (title) => {
          if (title === 'Pending') {
               return (
                    <Box bgColor="warmGray.50" borderBottomWidth="1" _dark={{ borderColor: 'gray.600', bgColor: 'coolGray.800' }} borderColor="coolGray.200" flexWrap="nowrap" maxWidth="100%" safeArea={2}>
                         <Heading pb={1} pt={3}>
                              {getTermFromDictionary(language, 'pending_holds')}
                         </Heading>
                         <DisplayMessage type="info" message={getTermFromDictionary(language, 'pending_holds_message')} />
                         {actionButtons('pending')}
                    </Box>
               );
          } else {
               return (
                    <Box bgColor="warmGray.50" borderBottomWidth="1" _dark={{ borderColor: 'gray.600', bgColor: 'coolGray.800' }} borderColor="coolGray.200" flexWrap="nowrap" maxWidth="100%" safeArea={2}>
                         <Heading pb={1}>{getTermFromDictionary(language, 'holds_ready_for_pickup')}</Heading>
                         <DisplayMessage type="info" message={getTermFromDictionary(language, 'holds_ready_for_pickup_message')} />
                         {actionButtons('ready')}
                    </Box>
               );
          }
     };

     const displaySectionFooter = (title) => {
          const sectionData = _.find(holds, { title: title });
          if (title === 'Pending') {
               if (_.isEmpty(sectionData.data)) {
                    return noHolds(title);
               } else {
                    return <Box pb={30} />;
               }
          } else if (title === 'Ready') {
               if (_.isEmpty(sectionData.data)) {
                    return noHolds(title);
               }
          }
          return null;
     };

     return (
          <SafeAreaView>
               {actionButtons('none')}
               <Box style={{ paddingBottom: 100 }}>
                    <Checkbox.Group
                         style={{
                              maxWidth: '100%',
                              alignItems: 'center',
                              _text: {
                                   textAlign: 'left',
                              },
                              padding: 0,
                              margin: 0,
                         }}
                         name="Holds"
                         value={values}
                         accessibilityLabel={getTermFromDictionary(language, 'multiple_holds')}
                         onChange={(newValues) => {
                              saveGroupValue(newValues);
                         }}>
                         <SectionList sections={holds} renderItem={({ item, section: { title } }) => <MyHold data={item} resetGroup={resetGroup} language={language} pickupLocations={pickupLocations} section={title} key="ready" />} stickySectionHeadersEnabled={true} renderSectionHeader={({ section: { title } }) => displaySectionHeader(title)} renderSectionFooter={({ section: { title } }) => displaySectionFooter(title)} contentContainerStyle={{ paddingBottom: 30 }} />
                    </Checkbox.Group>
               </Box>
          </SafeAreaView>
     );
};

function sortHolds(holds, pendingSort, readySort) {
     let sortedHolds = holds;
     let holdsReady = [];
     let holdsNotReady = [];

     let pendingSortMethod = pendingSort;
     if (pendingSort === 'sortTitle') {
          pendingSortMethod = 'title';
     } else if (pendingSort === 'libraryAccount') {
          pendingSortMethod = 'user';
     }

     let readySortMethod = readySort;
     if (readySort === 'sortTitle') {
          readySortMethod = 'title';
     } else if (readySort === 'libraryAccount') {
          readySortMethod = 'user';
     }

     if (holds) {
          if (holds[1].title === 'Pending') {
               holdsNotReady = holds[1].data;
               if (pendingSortMethod === 'position') {
                    holdsNotReady = _.orderBy(holdsNotReady, [pendingSortMethod], ['desc']);
               }
               holdsNotReady = _.orderBy(holdsNotReady, [pendingSortMethod], ['asc']);
          }

          if (holds[0].title === 'Ready') {
               holdsReady = holds[0].data;
               holdsReady = _.orderBy(holdsReady, [readySortMethod], ['asc']);
          }
     }

     return [
          {
               title: 'Ready',
               data: holdsReady,
          },
          {
               title: 'Pending',
               data: holdsNotReady,
          },
     ];
}