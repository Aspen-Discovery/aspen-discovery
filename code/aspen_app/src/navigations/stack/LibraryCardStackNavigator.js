import { createNativeStackNavigator } from '@react-navigation/native-stack';
import React from 'react';

import { MyLibraryCardTemp } from '../../screens/MyAccount/MyLibraryCard';
import { translate } from '../../translations/translations';
import { LibrarySystemContext } from '../../context/initialContext';

const LibraryCardStackNavigator = () => {
     const Stack = createNativeStackNavigator();
     return (
          <Stack.Navigator
               initialRouteName="LibraryCard"
               screenOptions={{
                    headerShown: true,
                    headerBackTitleVisible: false,
               }}>
               <Stack.Screen
                    name="LibraryCard"
                    component={MyLibraryCardTemp}
                    options={{ title: translate('user_profile.library_card') }}
                    initialParams={{
                         libraryContext: JSON.stringify(React.useContext(LibrarySystemContext)),
                    }}
               />
          </Stack.Navigator>
     );
};

export default LibraryCardStackNavigator;