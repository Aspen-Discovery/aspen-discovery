import { createNativeStackNavigator } from '@react-navigation/native-stack';
import React from 'react';

import { ContactLibrary } from '../../screens/Library/Contact';
import More from '../../screens/More/More';
import {LanguageContext, LibraryBranchContext, LibrarySystemContext, UserContext} from '../../context/initialContext';
import {getTermFromDictionary} from '../../translations/TranslationService';

const MoreStackNavigator = () => {
     const { language } = React.useContext(LanguageContext);
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
                    options={{ title: getTermFromDictionary(language, 'nav_more') }}
                    initialParams={{
                         libraryContext: JSON.stringify(React.useContext(LibrarySystemContext)),
                         locationContext: JSON.stringify(React.useContext(LibraryBranchContext)),
                         userContext: React.useContext(UserContext),
                    }}
               />
               <Stack.Screen name="Contact" component={ContactLibrary} options={{ title: getTermFromDictionary(language, 'contact') }} />
          </Stack.Navigator>
     );
};

export default MoreStackNavigator;