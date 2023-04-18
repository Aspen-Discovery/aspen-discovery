import { ScrollView, Box, Button, Center, Text, HStack, Checkbox, Select, FormControl, CheckIcon, Heading } from 'native-base';
import React from 'react';
import { useNavigation } from '@react-navigation/native';
import {Platform, SafeAreaView, SectionList} from 'react-native';
import { useFocusEffect } from '@react-navigation/native';
import _ from 'lodash';

// custom components and helper files
import { loadingSpinner } from '../../../components/loadingSpinner';
import {HoldsContext, LanguageContext, LibrarySystemContext, UserContext} from '../../../context/initialContext';
import { getPickupLocations } from '../../../util/loadLibrary';
import {getPatronHolds, refreshProfile, reloadProfile} from '../../../util/api/user';
import { MyHold, ManageAllHolds, ManageSelectedHolds } from './MyHold';
import {DisplayMessage} from '../../../components/Notifications';
import {getTermFromDictionary, getTranslationsWithValues} from '../../../translations/TranslationService';

export const MyHolds = () => {
     const navigation = useNavigation();
     const { user, updateUser } = React.useContext(UserContext);
     const { library } = React.useContext(LibrarySystemContext);
     const { holds, updateHolds } = React.useContext(HoldsContext);
     const { language } = React.useContext(LanguageContext);
     const [holdSource, setHoldSource] = React.useState('all');
     const [readySort, setReadySort] = React.useState('expire');
     const [pendingSort, setPendingSort] = React.useState('sortTitle');
     const [isLoading, setLoading] = React.useState(true);
     const [values, setGroupValues] = React.useState([]);
     const [date, setNewDate] = React.useState();
     const [pickupLocations, setPickupLocations] = React.useState([]);
    const [filterBy, setFilterBy] = React.useState({
        ils: 'Filter by Physical Materials',
        overdrive: 'Filter by OverDrive',
        axis360: 'Filter by Axis 360',
        cloudlibrary: 'Filter by cloudLibrary',
        all: 'Filter by All'
    });
    const [holdsBy, setHoldsBy] = React.useState({
        ils: 'Titles on Hold for Physical Materials',
        hoopla: 'Titles on Hold for Hoopla',
        overdrive: 'Titles on Hold for OverDrive',
        axis360: 'Titles on Hold for Axis 360',
        cloudlibrary: 'Titles on Hold for cloudLibrary',
        all: 'Titles on Hold'
    });

    const [sortBy, setSortBy] = React.useState({
        title: 'Sort by Title',
        author: 'Sort by Author',
        format: 'Sort by Format',
        status: 'Sort by Status',
        placed: 'Sort by Date Placed',
        position: 'Sort by Position',
        location: 'Sort by Pickup Location',
        libraryAccount: 'Sort by Library Account',
        expiration: 'Sort by Expiration Date'
    });

    const toggleReadySort = async (value) => {
        setReadySort(value);
        setLoading(true);
        await getPatronHolds(value, pendingSort, holdSource, library.baseUrl, true, language).then((result) => {
            updateHolds(result);
            setLoading(false);
        });
    };

    const togglePendingSort = async (value) => {
        setPendingSort(value);
        setLoading(true);
        await getPatronHolds(readySort, value, holdSource, library.baseUrl, true, language).then((result) => {
            updateHolds(result);
            setLoading(false);
        });
    };

    const toggleHoldSource = async (value) => {
        setHoldSource(value);
        setLoading(true);
        await getPatronHolds(readySort, pendingSort, value, library.baseUrl, true, language).then((result) => {
            updateHolds(result);
            if (!_.isNull(value)) {
                if(value === 'ils') {
                    navigation.setOptions({ title: holdsBy.ils });
                } else if (value === 'overdrive') {
                    navigation.setOptions({ title: holdsBy.overdrive });
                } else if (value === 'cloud_library') {
                    navigation.setOptions({ title: holdsBy.cloudlibrary });
                } else if (value === 'axis360') {
                    navigation.setOptions({ title: holdsBy.axis360 });
                } else {
                    navigation.setOptions({ title: holdsBy.all });
                }
            }
            setLoading(false);
        })
    }

     useFocusEffect(
          React.useCallback(() => {
               const update = async () => {
                    await getPatronHolds(readySort, pendingSort, holdSource, library.baseUrl, true, language).then(async (result) => {
                        if (holds !== result) {
                            updateHolds(result);
                        }
                        await getTranslationsWithValues('filter_by_source', 'Physical Materials', language, library.baseUrl).then(term => {
                            let tmp = filterBy;
                            tmp = _.set(tmp, 'ils', _.toString(term));
                            setFilterBy(tmp);
                        })
                        await getTranslationsWithValues('filter_by_source', 'OverDrive', language, library.baseUrl).then(term => {
                            let tmp = filterBy;
                            tmp = _.set(tmp, 'overdrive', _.toString(term));
                            setFilterBy(tmp);
                        })
                        await getTranslationsWithValues('filter_by_source', 'cloudLibrary', language, library.baseUrl).then(term => {
                            let tmp = filterBy;
                            tmp = _.set(tmp, 'cloudlibrary', _.toString(term));
                            setFilterBy(tmp);
                        })
                        await getTranslationsWithValues('filter_by_source', 'All', language, library.baseUrl).then(term => {
                            let tmp = filterBy;
                            tmp = _.set(tmp, 'all', _.toString(term));
                            setFilterBy(tmp);
                        })
                        await getTranslationsWithValues('filter_by_source', 'Axis 360', language, library.baseUrl).then(term => {
                            let tmp = filterBy;
                            tmp = _.set(tmp, 'axis360', _.toString(term));
                            setFilterBy(tmp);
                        })
                        await getTranslationsWithValues('titles_on_hold_by_source', 'OverDrive', language, library.baseUrl).then(term => {
                            let tmp = holdsBy;
                            tmp = _.set(tmp, 'overdrive', _.toString(term));
                            setHoldsBy(tmp);
                        })
                        await getTranslationsWithValues('titles_on_hold_by_source', 'Hoopla', language, library.baseUrl).then(term => {
                            let tmp = holdsBy;
                            tmp = _.set(tmp, 'hoopla', _.toString(term));
                            setHoldsBy(tmp);
                        })
                        await getTranslationsWithValues('titles_on_hold_by_source', 'cloudLibrary', language, library.baseUrl).then(term => {
                            let tmp = holdsBy;
                            tmp = _.set(tmp, 'cloudlibrary', _.toString(term));
                            setHoldsBy(tmp);
                        })
                        await getTranslationsWithValues('titles_on_hold_by_source', 'Axis 360', language, library.baseUrl).then(term => {
                            let tmp = holdsBy;
                            tmp = _.set(tmp, 'axis360', _.toString(term));
                            setHoldsBy(tmp);
                        })
                        await getTranslationsWithValues('sort_by_with_sort', 'Title', language, library.baseUrl).then(term => {
                            let tmp = sortBy;
                            tmp = _.set(tmp, 'title', _.toString(term));
                            setSortBy(tmp);
                        })
                        await getTranslationsWithValues('sort_by_with_sort', 'Author', language, library.baseUrl).then(term => {
                            let tmp = sortBy;
                            tmp = _.set(tmp, 'author', _.toString(term));
                            setSortBy(tmp);
                        })
                        await getTranslationsWithValues('sort_by_with_sort', 'Format', language, library.baseUrl).then(term => {
                            let tmp = sortBy;
                            tmp = _.set(tmp, 'format', _.toString(term));
                            setSortBy(tmp);
                        })
                        await getTranslationsWithValues('sort_by_with_sort', 'Source', language, library.baseUrl).then(term => {
                            let tmp = sortBy;
                            tmp = _.set(tmp, 'source', _.toString(term));
                            setSortBy(tmp);
                        })
                        await getTranslationsWithValues('sort_by_with_sort', 'Date Placed', language, library.baseUrl).then(term => {
                            let tmp = sortBy;
                            tmp = _.set(tmp, 'placed', _.toString(term));
                            setSortBy(tmp);
                        })
                        await getTranslationsWithValues('sort_by_with_sort', 'Position', language, library.baseUrl).then(term => {
                            let tmp = sortBy;
                            tmp = _.set(tmp, 'position', _.toString(term));
                            setSortBy(tmp);
                        })
                        await getTranslationsWithValues('sort_by_with_sort', 'Pickup Location', language, library.baseUrl).then(term => {
                            let tmp = sortBy;
                            tmp = _.set(tmp, 'location', _.toString(term));
                            setSortBy(tmp);
                        })
                        await getTranslationsWithValues('sort_by_with_sort', 'Library Account', language, library.baseUrl).then(term => {
                            let tmp = sortBy;
                            tmp = _.set(tmp, 'libraryAccount', _.toString(term));
                            setSortBy(tmp);
                        })
                        await getTranslationsWithValues('sort_by_with_sort', 'Expiration Date', language, library.baseUrl).then(term => {
                            let tmp = sortBy;
                            tmp = _.set(tmp, 'expiration', _.toString(term));
                            setSortBy(tmp);
                        })
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
          }, [language])
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
          await getPatronHolds(readySort, pendingSort, holdSource, library.baseUrl, true, language).then((result) => {
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

     const noHolds = (title) => {
         if(title === 'Pending') {
             return (
                 <Center mt={5} mb={5}>
                     <Text bold fontSize="lg">
                         {getTermFromDictionary(language, 'pending_holds_none')}
                     </Text>
                 </Center>
             )
         } else {
             return (
                 <Center mt={5} mb={5}>
                     <Text bold fontSize="lg">
                         {getTermFromDictionary(language, 'holds_ready_for_pickup_none')}
                     </Text>
                 </Center>
             );
         }
     };

     const refreshHolds = async () => {
          setLoading(true);
          await reloadProfile(library.baseUrl).then((result) => {
              if (user !== result) {
                  updateUser(result);
              }
              setLoading(false);
          })
     };

     const actionButtons = (section) => {
          let showSelectOptions = false;
          if (values.length >= 1) {
               showSelectOptions = true;
          }

          if(section === 'pending') {
              if (showSelectOptions) {
                  return (
                      <Box safeArea={2}>
                          <ScrollView horizontal>
                              <HStack space={2}>
                                  <FormControl w={150}>
                                      <Select
                                          name="sortBy"
                                          selectedValue={pendingSort}
                                          accessibilityLabel={getTermFromDictionary(language, 'select_sort_method')}
                                          _selectedItem={{
                                              bg: 'tertiary.300',
                                              endIcon: <CheckIcon size="5"/>,
                                          }}
                                          onValueChange={(itemValue) => togglePendingSort(itemValue)}>
                                          <Select.Item label={sortBy.title} value="sortTitle" key={0}/>
                                          <Select.Item label={sortBy.author} value="author" key={1}/>
                                          <Select.Item label={sortBy.format} value="format" key={2}/>
                                          <Select.Item label={sortBy.status} value="status" key={3}/>
                                          <Select.Item label={sortBy.placed} value="placed" key={4}/>
                                          <Select.Item label={sortBy.position} value="position" key={5}/>
                                          <Select.Item label={sortBy.location} value="location" key={6}/>
                                          <Select.Item label={sortBy.libraryAccount} value="libraryAccount" key={7}/>
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
                                      name="sortBy"
                                      selectedValue={pendingSort}
                                      accessibilityLabel={getTermFromDictionary(language, 'select_sort_method')}
                                      _selectedItem={{
                                          bg: 'tertiary.300',
                                          endIcon: <CheckIcon size="5"/>,
                                      }}
                                      onValueChange={(itemValue) => togglePendingSort(itemValue)}>
                                      <Select.Item label={sortBy.title} value="sortTitle" key={0}/>
                                      <Select.Item label={sortBy.author} value="author" key={1}/>
                                      <Select.Item label={sortBy.format} value="format" key={2}/>
                                      <Select.Item label={sortBy.status} value="status" key={3}/>
                                      <Select.Item label={sortBy.placed} value="placed" key={4}/>
                                      <Select.Item label={sortBy.position} value="position" key={5}/>
                                      <Select.Item label={sortBy.location} value="location" key={6}/>
                                      <Select.Item label={sortBy.libraryAccount} value="libraryAccount" key={7}/>
                                  </Select>
                              </FormControl>
                              <ManageAllHolds language={language} data={holds} onDateChange={handleDateChange} selectedReactivationDate={date} resetGroup={resetGroup} />
                          </HStack>
                      </ScrollView>
                  </Box>
              );
          }

          if(section === 'ready') {
              return (
                  <Box safeArea={2}>
                      <ScrollView horizontal>
                          <HStack space={2}>
                              <FormControl w={150}>
                                  <Select
                                      name="sortBy"
                                      selectedValue={readySort}
                                      accessibilityLabel={getTermFromDictionary(language, 'select_sort_method')}
                                      _selectedItem={{
                                          bg: 'tertiary.300',
                                          endIcon: <CheckIcon size="5"/>,
                                      }}
                                      onValueChange={(itemValue) => toggleReadySort(itemValue)}>
                                      <Select.Item label={sortBy.title} value="sortTitle" key={0}/>
                                      <Select.Item label={sortBy.author} value="author" key={1}/>
                                      <Select.Item label={sortBy.format} value="format" key={2}/>
                                      <Select.Item label={sortBy.expiration} value="expire" key={3}/>
                                      <Select.Item label={sortBy.placed} value="placed" key={4}/>
                                      <Select.Item label={sortBy.location} value="location" key={5}/>
                                      <Select.Item label={sortBy.libraryAccount} value="libraryAccount" key={6}/>
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
                               endIcon: <CheckIcon size="5"/>,
                           }}
                           onValueChange={(itemValue) => toggleHoldSource(itemValue)}>
                           <Select.Item label={filterBy.all + ' (' + (user.numCheckedOut ?? 0) + ')'} value="all" key={0} />
                           <Select.Item label={filterBy.ils + ' (' + (user.numCheckedOutIls ?? 0) + ')'} value="ils" key={1}/>
                           <Select.Item label={filterBy.overdrive + ' (' + (user.numCheckedOutOverDrive ?? 0) + ')'} value="overdrive" key={2}/>
                           <Select.Item label={filterBy.cloudlibrary + ' (' + (user.numCheckedOut_cloudLibrary ?? 0) + ')'} value="cloud_library" key={3}/>
                           <Select.Item label={filterBy.axis360 + ' (' + (user.numCheckedOut_axis360 ?? 0) + ')'} value="axis360" key={4}/>
                       </Select>
                   </FormControl>
               </HStack>
              </ScrollView>
              </Box>
          );
     };

     const displaySectionHeader = (title) => {
         if(title === 'Pending') {
             return (
                 <Box bgColor="warmGray.50" borderBottomWidth="1" _dark={{ borderColor: 'gray.600', bgColor: 'coolGray.800' }} borderColor="coolGray.200" flexWrap="nowrap" maxWidth="100%" safeArea={2}>
                     <Heading pb={1} pt={3}>{getTermFromDictionary(language, 'pending_holds')}</Heading>
                     <DisplayMessage type="info" message={getTermFromDictionary(language, 'pending_holds_message')}/>
                     {actionButtons('pending')}
                 </Box>
             )
         } else {
             return (
                 <Box  bgColor="warmGray.50" borderBottomWidth="1" _dark={{ borderColor: 'gray.600', bgColor: 'coolGray.800' }} borderColor="coolGray.200" flexWrap="nowrap" maxWidth="100%" safeArea={2}>
                     <Heading pb={1}>{getTermFromDictionary(language, 'holds_ready_for_pickup')}</Heading>
                     <DisplayMessage type="info" message={getTermFromDictionary(language, 'holds_ready_for_pickup_message')}/>
                     {actionButtons('ready')}
                 </Box>
             )
         }
     }

     const displaySectionFooter = (title) => {
         const sectionData = _.find(holds, { 'title': title });
         if(title === 'Pending') {
             if(_.isEmpty(sectionData.data)) {
                 return noHolds(title);
             } else {
                 return (
                     <Box pb={30}/>
                 )
             }
         } else if (title === 'Ready') {
             if(_.isEmpty(sectionData.data)) {
                 return noHolds(title);
             }
         }

         return null;
     }

     return (
          <SafeAreaView>
              {actionButtons('none')}
               <Box style={{paddingBottom: 100}}>
                   <Checkbox.Group
                       style={{
                           maxWidth: '100%',
                           alignItems: 'center',
                           _text: {
                               textAlign: 'left'
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
                       <SectionList sections={holds} renderItem={({ item, section:{title} }) => <MyHold data={item} resetGroup={resetGroup} language={language} pickupLocations={pickupLocations} section={title} key="ready"/>} stickySectionHeadersEnabled={true} renderSectionHeader={({ section: { title } }) => (displaySectionHeader(title))} renderSectionFooter={({ section: { title } }) => (displaySectionFooter(title))}  contentContainerStyle={{ paddingBottom: 30 }} />
                   </Checkbox.Group>
               </Box>
          </SafeAreaView>
     );
};