import { createNativeStackNavigator } from '@react-navigation/native-stack';
import React from 'react';

import LibraryCard from '../../screens/MyAccount/MyLibraryCard';
import { translate } from '../../translations/translations';
import { LibraryBranchContext, LibrarySystemContext, UserContext } from '../../context/initialContext';

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
                    component={LibraryCard}
                    options={{ title: translate('user_profile.library_card') }}
                    initialParams={{
                         libraryContext: JSON.stringify(React.useContext(LibrarySystemContext)),
                    }}
               />
          </Stack.Navigator>
     );
};

export default LibraryCardStackNavigator;