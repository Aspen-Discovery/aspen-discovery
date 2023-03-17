import { createNativeStackNavigator } from '@react-navigation/native-stack';
import React from 'react';

import { ContactLibrary } from '../../screens/Library/Contact';
import {LanguageContext} from '../../context/initialContext';
import {getTermFromDictionary} from '../../translations/TranslationService';
import {MoreScreen} from '../../screens/More/More';

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
                    component={MoreScreen}
                    options={{ title: getTermFromDictionary(language, 'nav_more') }}
               />
               <Stack.Screen name="Contact" component={ContactLibrary} options={{ title: getTermFromDictionary(language, 'contact') }} />
          </Stack.Navigator>
     );
};

export default MoreStackNavigator;