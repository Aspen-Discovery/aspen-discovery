import { createNativeStackNavigator } from '@react-navigation/native-stack';
import React from 'react';

import { MyLibraryCard } from '../../screens/MyAccount/MyLibraryCard/MyLibraryCard';
import { LanguageContext, LibrarySystemContext } from '../../context/initialContext';
import { getTermFromDictionary } from '../../translations/TranslationService';

const LibraryCardStackNavigator = () => {
     const Stack = createNativeStackNavigator();
     const { language } = React.useContext(LanguageContext);
     return (
          <Stack.Navigator
               initialRouteName={'LibraryCard'}
               screenOptions={{
                    headerShown: true,
                    headerBackTitleVisible: false,
                    gestureEnabled: false,
               }}>
               <Stack.Screen
                    name="LibraryCard"
                    component={MyLibraryCard}
                    options={{ title: getTermFromDictionary(language, 'library_card') }}
                    initialParams={{
                         libraryContext: JSON.stringify(React.useContext(LibrarySystemContext)),
                    }}
               />
          </Stack.Navigator>
     );
};

export default LibraryCardStackNavigator;