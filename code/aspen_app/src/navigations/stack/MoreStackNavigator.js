import { createNativeStackNavigator } from '@react-navigation/native-stack';
import React from 'react';
import { LanguageContext } from '../../context/initialContext';
import { AllLocations } from '../../screens/Library/AllLocations';
import { Location } from '../../screens/Library/Location';

import { MyLibrary } from '../../screens/Library/MyLibrary';
import { MoreMenu } from '../../screens/More/MoreMenu';
import { Settings_BrowseCategories } from '../../screens/MyAccount/Settings/BrowseCategories';
import { Settings_LanguageScreen } from '../../screens/MyAccount/Settings/Language';
import { Settings_NotificationOptions } from '../../screens/MyAccount/Settings/NotificationOptions';
import { CalendarPermissionDescription } from '../../screens/MyAccount/Settings/Permission/Calendar';
import { CameraPermissionDescription } from '../../screens/MyAccount/Settings/Permission/Camera';
import { GeolocationPermissionDescription } from '../../screens/MyAccount/Settings/Permission/Geolocation';
import { NotificationPermissionDescription } from '../../screens/MyAccount/Settings/Permission/Notifications';
import { ScreenBrightnessPermissionDescription } from '../../screens/MyAccount/Settings/Permission/ScreenBrightness';
import { PermissionsDashboard } from '../../screens/MyAccount/Settings/Permissions';
import { PreferencesScreen } from '../../screens/MyAccount/Settings/Preferences';
import { SupportScreen } from '../../screens/MyAccount/Settings/Support';
import { BackIcon } from '../../themes/theme';
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
                    gestureEnabled: false,
                    headerBackImage: () => <BackIcon />,
               }}>
               <Stack.Screen name="MoreMenu" component={MoreMenu} options={{ title: getTermFromDictionary(language, 'nav_more') }} />
               <Stack.Screen
                    name="AllLocations"
                    component={AllLocations}
                    options={({ route }) => ({
                         title: getTermFromDictionary(language, 'locations'),
                    })}
               />
               <Stack.Screen
                    name="Location"
                    component={Location}
                    options={({ route }) => ({
                         title: route?.params?.title ?? getTermFromDictionary(language, 'location'),
                    })}
               />
               <Stack.Screen
                    name="MyLibrary"
                    component={MyLibrary}
                    options={({ route }) => ({
                         title: route?.params?.title ?? getTermFromDictionary(language, 'my_library'),
                    })}
               />
               <Stack.Group>
                    <Stack.Screen name="MyPreferences" component={PreferencesScreen} options={{ title: getTermFromDictionary(language, 'preferences') }} />
                    <Stack.Screen name="MyPreferences_ManageBrowseCategories" component={Settings_BrowseCategories} options={{ title: getTermFromDictionary(language, 'manage_browse_categories') }} />
                    <Stack.Screen name="MyPreferences_Language" component={Settings_LanguageScreen} options={{ title: getTermFromDictionary(language, 'manage_browse_categories') }} />
                    <Stack.Screen name="MyPreferences_Appearance" component={Settings_BrowseCategories} options={{ title: getTermFromDictionary(language, 'manage_browse_categories') }} />
                    <Stack.Screen name="MyDevice_Support" component={SupportScreen} options={{ title: getTermFromDictionary(language, 'support') }} />
               </Stack.Group>
               <Stack.Group>
                    <Stack.Screen name="PermissionDashboard" component={PermissionsDashboard} options={{ title: getTermFromDictionary(language, 'device_permissions') }} />
                    <Stack.Screen name="PermissionCameraDescription" component={CameraPermissionDescription} options={{ title: getTermFromDictionary(language, 'camera_permission') }} />
                    <Stack.Screen name="PermissionCalendarDescription" component={CalendarPermissionDescription} options={{ title: getTermFromDictionary(language, 'calendar_permission') }} />
                    <Stack.Screen name="PermissionGeolocationDescription" component={GeolocationPermissionDescription} options={{ title: getTermFromDictionary(language, 'geolocation_permission') }} />
                    <Stack.Screen name="PermissionNotificationDescription" component={NotificationPermissionDescription} options={{ title: getTermFromDictionary(language, 'notification_permission') }} />
                    <Stack.Screen name="PermissionScreenBrightnessDescription" component={ScreenBrightnessPermissionDescription} options={{ title: getTermFromDictionary(language, 'screen_brightness_permission') }} />
               </Stack.Group>
          </Stack.Navigator>
     );
};

export default MoreStackNavigator;