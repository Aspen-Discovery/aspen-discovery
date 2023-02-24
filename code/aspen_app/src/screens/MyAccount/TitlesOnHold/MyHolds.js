import { ScrollView, Box, Button, Center, Text, HStack, Checkbox, Select, FormControl, CheckIcon, Heading } from 'native-base';
import React from 'react';
import { useNavigation } from '@react-navigation/native';
import {SafeAreaView, SectionList} from 'react-native';
import { useFocusEffect } from '@react-navigation/native';
import _ from 'lodash';

// custom components and helper files
import { loadingSpinner } from '../../../components/loadingSpinner';
import { translate } from '../../../translations/translations';
import { HoldsContext, LibrarySystemContext, UserContext } from '../../../context/initialContext';
import { getPickupLocations } from '../../../util/loadLibrary';
import {getPatronHolds, refreshProfile, reloadProfile} from '../../../util/api/user';
import { MyHold, ManageAllHolds, ManageSelectedHolds } from './MyHold';
import {DisplayMessage} from '../../../components/Notifications';

export const MyHolds = () => {
     const navigation = useNavigation();
     const { user, updateUser } = React.useContext(UserContext);
     const { library } = React.useContext(LibrarySystemContext);
     const { holds, updateHolds } = React.useContext(HoldsContext);
     const [holdSource, setHoldSource] = React.useState('all');
     const [readySort, setReadySort] = React.useState('expire');
     const [pendingSort, setPendingSort] = React.useState('sortTitle');
     const [isLoading, setLoading] = React.useState(true);
     const [values, setGroupValues] = React.useState([]);
     const [date, setNewDate] = React.useState();
     const [pickupLocations, setPickupLocations] = React.useState([]);

    const toggleReadySort = async (value) => {
        setReadySort(value);
        setLoading(true);
        await getPatronHolds(value, pendingSort, holdSource, library.baseUrl).then((result) => {
            updateHolds(result);
            setLoading(false);
        });
    };

    const togglePendingSort = async (value) => {
        setPendingSort(value);
        setLoading(true);
        await getPatronHolds(readySort, value, holdSource, library.baseUrl).then((result) => {
            updateHolds(result);
            setLoading(false);
        });
    };

    const toggleHoldSource = async (value) => {
        setHoldSource(value);
        setLoading(true);
        await getPatronHolds(readySort, pendingSort, value, library.baseUrl).then((result) => {
            updateHolds(result);
            if (!_.isNull(value)) {
                if(value === 'ils') {
                    navigation.setOptions({ title: translate('holds.titles_on_hold_for_source', {source: "Physical Materials"})});
                } else if (value === 'overdrive') {
                    navigation.setOptions({ title: translate('holds.titles_on_hold_for_source', {source: "OverDrive"})});
                } else if (value === 'cloud_library') {
                    navigation.setOptions({ title: translate('holds.titles_on_hold_for_source', {source: "CloudLibrary"})});
                } else if (value === 'axis360') {
                    navigation.setOptions({ title: translate('holds.titles_on_hold_for_source', {source: "Axis 360"})});
                } else {
                    navigation.setOptions({ title: translate('holds.title')});
                }
            }
            setLoading(false);
        })
    }

     useFocusEffect(
          React.useCallback(() => {
               const update = async () => {
                    await getPatronHolds(readySort, pendingSort, holdSource, library.baseUrl).then((result) => {
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
          await getPatronHolds(readySort, pendingSort, holdSource, library.baseUrl).then((result) => {
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
                         {translate('holds.pending_holds_none')}
                     </Text>
                 </Center>
             )
         } else {
             return (
                 <Center mt={5} mb={5}>
                     <Text bold fontSize="lg">
                         {translate('holds.holds_ready_for_pickup_none')}
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
                                          accessibilityLabel="Select a Sort Method"
                                          _selectedItem={{
                                              bg: 'tertiary.300',
                                              endIcon: <CheckIcon size="5"/>,
                                          }}
                                          onValueChange={(itemValue) => togglePendingSort(itemValue)}>
                                          <Select.Item label={translate('general.sort_by', { sort: translate('general.title') })} value="sortTitle" key={0}/>
                                          <Select.Item label={translate('general.sort_by', { sort: translate('grouped_work.author') })} value="author" key={1}/>
                                          <Select.Item label={translate('general.sort_by', { sort: translate('grouped_work.format') })} value="format" key={2}/>
                                          <Select.Item label={translate('general.sort_by', { sort: 'Status'})} value="status" key={3}/>
                                          <Select.Item label={translate('general.sort_by', { sort: 'Date Placed'})} value="placed" key={4}/>
                                          <Select.Item label={translate('general.sort_by', { sort: 'Position'})} value="position" key={5}/>
                                          <Select.Item label={translate('general.sort_by', { sort: 'Pickup Location'})} value="location" key={6}/>
                                          <Select.Item label={translate('general.sort_by', { sort: 'Library Account'})} value="libraryAccount" key={7}/>
                                      </Select>
                                  </FormControl>
                                  <ManageSelectedHolds selectedValues={values} onAllDateChange={handleDateChange} selectedReactivationDate={date} resetGroup={resetGroup} />
                                  <Button size="sm" variant="outline" mr={1} onPress={() => clearGroupValue()}>
                                      {translate('holds.clear_selections')}
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
                                      accessibilityLabel="Select a Sort Method"
                                      _selectedItem={{
                                          bg: 'tertiary.300',
                                          endIcon: <CheckIcon size="5"/>,
                                      }}
                                      onValueChange={(itemValue) => togglePendingSort(itemValue)}>
                                      <Select.Item label={translate('general.sort_by', { sort: translate('general.title') })} value="sortTitle" key={0}/>
                                      <Select.Item label={translate('general.sort_by', { sort: translate('grouped_work.author') })} value="author" key={1}/>
                                      <Select.Item label={translate('general.sort_by', { sort: translate('grouped_work.format') })} value="format" key={2}/>
                                      <Select.Item label={translate('general.sort_by', { sort: 'Status'})} value="status" key={3}/>
                                      <Select.Item label={translate('general.sort_by', { sort: 'Date Placed'})} value="placed" key={4}/>
                                      <Select.Item label={translate('general.sort_by', { sort: 'Position'})} value="position" key={5}/>
                                      <Select.Item label={translate('general.sort_by', { sort: 'Pickup Location'})} value="location" key={6}/>
                                      <Select.Item label={translate('general.sort_by', { sort: 'Library Account'})} value="libraryAccount" key={7}/>
                                  </Select>
                              </FormControl>
                              <ManageAllHolds data={holds} onDateChange={handleDateChange} selectedReactivationDate={date} resetGroup={resetGroup} />
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
                                      accessibilityLabel="Select a Sort Method"
                                      _selectedItem={{
                                          bg: 'tertiary.300',
                                          endIcon: <CheckIcon size="5"/>,
                                      }}
                                      onValueChange={(itemValue) => toggleReadySort(itemValue)}>
                                      <Select.Item label={translate('general.sort_by', { sort: translate('general.title') })} value="title" key={0}/>
                                      <Select.Item label={translate('general.sort_by', { sort: translate('grouped_work.author') })} value="author" key={1}/>
                                      <Select.Item label={translate('general.sort_by', { sort: translate('grouped_work.format') })} value="format" key={2}/>
                                      <Select.Item label={translate('general.sort_by', { sort: 'Expiration Date'})} value="expire" key={3}/>
                                      <Select.Item label={translate('general.sort_by', { sort: 'Date Placed'})} value="placed" key={4}/>
                                      <Select.Item label={translate('general.sort_by', { sort: 'Pickup Location'})} value="location" key={5}/>
                                      <Select.Item label={translate('general.sort_by', { sort: 'Library Account'})} value="libraryAccount" key={6}/>
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
                         {translate('holds.reload_holds')}
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
                           <Select.Item label={translate('holds.filter_by', {source: 'All', num: user.numHolds ?? 0})} value="all" key={0}/>
                           <Select.Item label={translate('holds.filter_by', {source: 'Physical Materials', num: user.numHoldsIls ?? 0})} value="ils" key={1}/>
                           <Select.Item label={translate('holds.filter_by', {source: 'OverDrive', num: user.numHoldsOverDrive ?? 0})} value="overdrive" key={2}/>
                           <Select.Item label={translate('holds.filter_by', {source: 'CloudLibrary', num: user.numHolds_cloudLibrary ?? 0})} value="cloud_library" key={3}/>
                           <Select.Item label={translate('holds.filter_by', {source: 'Axis 360', num: user.numHolds_axis360 ?? 0})} value="axis360" key={3}/>
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
                     <Heading pb={1} pt={3}>{translate('holds.pending_holds')}</Heading>
                     <DisplayMessage type="info" message={translate('holds.pending_holds_message')}/>
                     {actionButtons('pending')}
                 </Box>
             )
         } else {
             return (
                 <Box  bgColor="warmGray.50" borderBottomWidth="1" _dark={{ borderColor: 'gray.600', bgColor: 'coolGray.800' }} borderColor="coolGray.200" flexWrap="nowrap" maxWidth="100%" safeArea={2}>
                     <Heading pb={1}>{translate('holds.holds_ready_for_pickup')}</Heading>
                     <DisplayMessage type="info" message={translate('holds.holds_ready_for_pickup_message')}/>
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
                       accessibilityLabel={translate('holds.multiple_holds')}
                       onChange={(newValues) => {
                           saveGroupValue(newValues);
                       }}>
                       <SectionList sections={holds} renderItem={({ item, section:{title} }) => <MyHold data={item} resetGroup={resetGroup} pickupLocations={pickupLocations} section={title} key="ready"/>} stickySectionHeadersEnabled={true} renderSectionHeader={({ section: { title } }) => (displaySectionHeader(title))} renderSectionFooter={({ section: { title } }) => (displaySectionFooter(title))}  contentContainerStyle={{ paddingBottom: 30 }} />
                   </Checkbox.Group>
               </Box>
          </SafeAreaView>
     );
};