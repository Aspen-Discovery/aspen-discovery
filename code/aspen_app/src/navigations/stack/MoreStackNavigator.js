import { createNativeStackNavigator } from '@react-navigation/native-stack';
import React from 'react';
import { LanguageContext } from '../../context/initialContext';

import { MyLibrary } from '../../screens/Library/MyLibrary';
import { MoreMenu } from '../../screens/More/MoreMenu';
import { Settings_BrowseCategories } from '../../screens/MyAccount/Settings/BrowseCategories';
import { Settings_NotificationOptions } from '../../screens/MyAccount/Settings/NotificationOptions';
import { PreferencesScreen } from '../../screens/MyAccount/Settings/Preferences';
import { getTermFromDictionary } from '../../translations/TranslationService';

const MoreStackNavigator = () => {
     const { language } = React.useContext(LanguageContext);
     const Stack = createNativeStackNavigator();
     return (
          <Stack.Navigator
               initialRouteName="MoreMenu"
               screenOptions={{
                    headerShown: true,
                    headerBackTitleVisible: false,
               }}>
               <Stack.Screen name="MoreMenu" component={MoreMenu} options={{ title: getTermFromDictionary(language, 'nav_more') }} />
               <Stack.Screen
                    name="MyLibrary"
                    component={MyLibrary}
                    options={({ route }) => ({
                         title: route?.params?.title ?? getTermFromDictionary(language, 'my_library'),
                    })}
               />
               <Stack.Group>
                    <Stack.Screen name="MyPreferences" component={PreferencesScreen} options={{ title: getTermFromDictionary(language, 'preferences') }} />
                    <Stack.Screen name="SettingsBrowseCategories" component={Settings_BrowseCategories} options={{ title: getTermFromDictionary(language, 'manage_browse_categories') }} />
                    <Stack.Screen name="SettingsNotificationOptions" component={Settings_NotificationOptions} options={{ title: getTermFromDictionary(language, 'notification_settings') }} />
               </Stack.Group>
          </Stack.Navigator>
     );
};

export default MoreStackNavigator;