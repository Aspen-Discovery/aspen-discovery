import { createNativeStackNavigator } from '@react-navigation/native-stack';
import React from 'react';

import {MyLibraryCard, MyLibraryCard221200} from '../../screens/MyAccount/MyLibraryCard';
import {LanguageContext, LibrarySystemContext} from '../../context/initialContext';
import {formatDiscoveryVersion} from '../../util/loadLibrary';
import {getTermFromDictionary} from '../../translations/TranslationService';

const LibraryCardStackNavigator = () => {
     const Stack = createNativeStackNavigator();
     const { library } = React.useContext(LibrarySystemContext);
     const { language } = React.useContext(LanguageContext);
     const version = formatDiscoveryVersion(library.discoveryVersion);
     return (
          <Stack.Navigator
               initialRouteName={version >= '23.01.00' ? 'LibraryCard' : 'LibraryCard221200'}
               screenOptions={{
                    headerShown: true,
                    headerBackTitleVisible: false,
               }}>
               <Stack.Screen
                    name="LibraryCard"
                    component={MyLibraryCard}
                    options={{ title: getTermFromDictionary(language, 'library_card') }}
                    initialParams={{
                         libraryContext: JSON.stringify(React.useContext(LibrarySystemContext)),
                    }}
               />
              <Stack.Screen
                  name="LibraryCard221200"
                  component={MyLibraryCard221200}
                  options={{ title: getTermFromDictionary(language, 'library_card') }}
                  initialParams={{
                      libraryContext: JSON.stringify(React.useContext(LibrarySystemContext)),
                  }}
              />
          </Stack.Navigator>
     );
};

export default LibraryCardStackNavigator;