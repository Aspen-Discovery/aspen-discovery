import { createNativeStackNavigator } from '@react-navigation/native-stack';
import React from 'react';

import { ContactLibrary } from '../../screens/Library/Contact';
import More from '../../screens/More/More';
import { translate } from '../../translations/translations';
import { LibraryBranchContext, LibrarySystemContext, UserContext } from '../../context/initialContext';

const MoreStackNavigator = () => {
     const Stack = createNativeStackNavigator();
     return (
          <Stack.Navigator
               initialRouteName="More"
               screenOptions={{
                    headerShown: true,
                    headerBackTitleVisible: false,
               }}>
               <Stack.Screen
                    name="More"
                    component={More}
                    options={{ title: translate('navigation.more') }}
                    initialParams={{
                         libraryContext: JSON.stringify(React.useContext(LibrarySystemContext)),
                         locationContext: JSON.stringify(React.useContext(LibraryBranchContext)),
                         userContext: React.useContext(UserContext),
                    }}
               />
               <Stack.Screen name="Contact" component={ContactLibrary} options={{ title: translate('general.contact') }} />
          </Stack.Navigator>
     );
};

export default MoreStackNavigator;