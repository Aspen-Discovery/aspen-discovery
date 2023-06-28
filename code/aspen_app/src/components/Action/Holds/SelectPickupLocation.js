import { FormControl, Select, CheckIcon } from 'native-base';
import React from 'react';
import { Platform } from 'react-native';

export const SelectPickupLocation = (props) => {
     const { locations, location, setLocation } = props;
     return (
          <>
               <FormControl>
                    <FormControl.Label>Select a Pickup Location</FormControl.Label>
                    <Select
                         isReadOnly={Platform.OS === 'android'}
                         name="pickupLocations"
                         selectedValue={location}
                         minWidth="200"
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
          </>
     );
};