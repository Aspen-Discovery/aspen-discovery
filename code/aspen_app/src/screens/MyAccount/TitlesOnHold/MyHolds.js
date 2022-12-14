import { ScrollView, Box, Button, Center, FlatList, Text, HStack, Checkbox } from 'native-base';
import React from 'react';
import { SafeAreaView } from 'react-native';
import { useFocusEffect } from '@react-navigation/native';

// custom components and helper files
import { loadingSpinner } from '../../../components/loadingSpinner';
import { translate } from '../../../translations/translations';
import { reloadHolds } from '../../../util/loadPatron';
import { HoldsContext, LibrarySystemContext, UserContext } from '../../../context/initialContext';
import { getPickupLocations } from '../../../util/loadLibrary';
import { refreshProfile } from '../../../util/api/user';
import { MyHold, ManageAllHolds, ManageSelectedHolds } from './MyHold';

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
                    <FlatList data={holds.holds} ListEmptyComponent={noHolds} renderItem={({ item }) => <MyHold data={item} resetGroup={resetGroup} pickupLocations={pickupLocations} />} keyExtractor={(item, index) => index.toString()} contentContainerStyle={{ paddingBottom: 30 }} />
               </Checkbox.Group>
          </SafeAreaView>
     );
};