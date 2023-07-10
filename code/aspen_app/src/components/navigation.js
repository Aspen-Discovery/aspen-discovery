import AsyncStorage from '@react-native-async-storage/async-storage';
import { DefaultTheme, NavigationContainer, useNavigationContainerRef } from '@react-navigation/native';
import { createNativeStackNavigator } from '@react-navigation/native-stack';
import { create } from 'apisauce';
import Constants from 'expo-constants';
import _ from 'lodash';
// Access any @sentry/react-native exports via:
// Sentry.Native.*
import * as Linking from 'expo-linking';
import * as Location from 'expo-location';
import * as Notifications from 'expo-notifications';
import * as SecureStore from 'expo-secure-store';
import * as Updates from 'expo-updates';
import { Spinner, useColorModeValue, useContrastText, useToken } from 'native-base';
import React from 'react';
import { enableScreens } from 'react-native-screens';
import { useQueryClient } from '@tanstack/react-query';

//import * as Sentry from '@sentry/react-native';
import * as Sentry from 'sentry-expo';

import Login, { LoginScreen } from '../screens/Auth/Login';
import { GLOBALS } from '../util/globals';
import { LIBRARY } from '../util/loadLibrary';
import { PATRON } from '../util/loadPatron';
import { checkCachedUrl } from '../util/login';
import LaunchStackNavigator from '../navigations/LaunchStackNavigator';
import { BrowseCategoryProvider, CheckoutsProvider, GroupedWorkProvider, HoldsProvider, LanguageProvider, LibraryBranchProvider, LibrarySystemProvider, UserProvider } from '../context/initialContext';
import { SplashScreen } from '../screens/Auth/Splash';
import { RemoveData } from '../util/logout';
import { Platform } from 'react-native';
import { navigationRef } from '../helpers/RootNavigator';
import { updateAspenLiDABuild } from '../util/greenhouse';
import { ResetExpiredPin } from '../screens/Auth/ResetExpiredPin';
import { PermissionsPrompt } from './PermissionsPrompt';
import LibraryCardScanner from './LibraryCardScanner';

const prefix = Linking.createURL('/');
console.log(prefix);

enableScreens();

const Stack = createNativeStackNavigator();
const routingInstrumentation = new Sentry.Native.ReactNavigationInstrumentation();

export const AuthContext = React.createContext();

const iOSRelease = Constants.manifest2?.extra?.expoClient?.ios?.bundleIdentifier ?? Constants.manifest.ios.bundleIdentifier;
const androidRelease = Constants.manifest2?.extra?.expoClient?.android?.package ?? Constants.manifest.android.package;
const iOSDist = Constants.manifest2?.extra?.expoClient?.ios?.buildNumber ?? Constants.manifest.ios.buildNumber;
const androidDist = Constants.manifest2?.extra?.expoClient?.android?.versionCode ?? Constants.manifest.android.versionCode;
const version = Constants.manifest2?.extra?.expoClient?.version ?? Constants.manifest.version;

console.log(iOSRelease);
console.log(iOSDist);

let releaseCode = Platform.OS === 'android' ? androidRelease + '@' + version + '+' + androidDist : iOSRelease + '@' + version + '+' + iOSDist;
releaseCode = releaseCode.toString();

let distribution = Platform.OS === 'android' ? androidDist : iOSDist;
distribution = distribution.toString();

Sentry.init({
     dsn: Constants.manifest2?.extra?.expoClient?.extra?.sentryDSN ?? Constants.manifest.extra.sentryDSN,
     enableInExpoDevelopment: false,
     enableAutoSessionTracking: true,
     sessionTrackingIntervalMillis: 10000,
     debug: false,
     tracesSampleRate: 0.1,
     sampleRate: 0.1,
     environment: Updates.channel ?? Updates.releaseChannel,
     release: releaseCode,
     dist: distribution,
     integrations: [
          new Sentry.Native.ReactNativeTracing({
               routingInstrumentation,
          }),
     ],
});

Sentry.Native.setTag('patch', GLOBALS.appPatch);

export function App() {
     const queryClient = useQueryClient();
     const primaryColor = useToken('colors', 'primary.base');
     const primaryColorContrast = useToken('colors', useContrastText(primaryColor));
     const screenBackgroundColor = useToken('colors', useColorModeValue('warmGray.50', 'coolGray.800'));
     const navigationTheme = {
          ...DefaultTheme,
          colors: {
               ...DefaultTheme.colors,
               primary: primaryColorContrast,
               card: primaryColor,
               text: primaryColorContrast,
               background: screenBackgroundColor,
          },
     };
     const [state, dispatch] = React.useReducer(
          (prevState, action) => {
               switch (action.type) {
                    case 'RESTORE_TOKEN':
                         return {
                              ...prevState,
                              userToken: action.token,
                              isLoading: false,
                         };
                    case 'SIGN_IN':
                         return {
                              ...prevState,
                              isSignout: false,
                              userToken: action.token,
                              isLoading: false,
                         };
                    case 'SIGN_OUT':
                         return {
                              ...prevState,
                              isSignout: true,
                              userToken: null,
                              isLoading: false,
                         };
               }
          },
          {
               isLoading: true,
               isSignout: false,
               userToken: null,
          }
     );

     React.useEffect(() => {
          const timer = setInterval(async () => {
               if (!__DEV__) {
                    try {
                         const update = await Updates.checkForUpdateAsync();
                         if (update.isAvailable) {
                              console.log('Found an update...');
                              try {
                                   console.log('Downloading update...');
                                   await Updates.fetchUpdateAsync().then(async (r) => {
                                        console.log('Updating app...');
                                        await Updates.reloadAsync();
                                   });
                              } catch (e) {
                                   console.log(e);
                              }
                         }
                    } catch (e) {
                         // error checking for updates
                    }
               }
          }, 10000);
          return () => {
               clearInterval(timer);
          };
     }, []);

     React.useEffect(() => {
          const bootstrapAsync = async () => {
               console.log('Checking updates...');
               if (Updates.manifest) {
                    await updateAspenLiDABuild(Updates.updateId, Updates.channel, Updates.createdAt);
                    console.log('Update information sent to Greenhouse.');
               } else {
                    console.log('No update to send to Greenhouse.');
               }

               console.log('Checking existing session...');
               let userToken;
               let libraryUrl;
               let userKey;
               try {
                    // Restore token stored in `AsyncStorage`
                    userToken = await AsyncStorage.getItem('@userToken');
                    libraryUrl = await AsyncStorage.getItem('@pathUrl');
                    userKey = await SecureStore.getItemAsync('userKey');
               } catch (e) {
                    // Restoring token failed
                    console.log(e);
                    dispatch({ type: 'SIGN_OUT' });
               }

               if (!userKey) {
                    dispatch({ type: 'SIGN_OUT' });
               }

               if (!libraryUrl) {
                    libraryUrl = LIBRARY.url;
               }

               if (userToken) {
                    console.log('Session found');
                    if (libraryUrl) {
                         console.log('Trying to connect to: ', libraryUrl);
                         await checkCachedUrl(libraryUrl).then(async (result) => {
                              if (result) {
                                   LIBRARY.url = libraryUrl;
                                   console.log('Connection successful. Continuing...');
                              } else {
                                   console.log('Connection failed, logging out.');
                                   userToken = null;
                                   await RemoveData().then((res) => {
                                        dispatch({ type: 'SIGN_OUT' });
                                   });
                              }
                         });
                    } else {
                         console.log('No cached library url, logging out.');
                         await RemoveData().then((res) => {
                              dispatch({ type: 'SIGN_OUT' });
                         });
                    }
               } else {
                    console.log('No session found. Starting new.');
               }
               dispatch({
                    type: 'RESTORE_TOKEN',
                    token: userToken,
               });
          };
          bootstrapAsync();
     }, []);

     const authContext = React.useMemo(
          () => ({
               signIn: async () => {
                    const userToken = Constants.manifest2?.extra?.expoClient?.sessionid ?? Constants.sessionId;
                    await AsyncStorage.setItem('@userToken', userToken);
                    dispatch({
                         type: 'SIGN_IN',
                         token: userToken,
                    });
               },
               signOut: async () => {
                    await RemoveData().then((res) => {
                         queryClient.invalidateQueries({});
                         dispatch({ type: 'SIGN_OUT' });
                    });
                    console.log('Session ended.');
               },
          }),
          []
     );

     if (state.isLoading) {
          // We haven't finished checking for the token yet
          return <SplashScreen />;
     }

     return (
          <AuthContext.Provider value={authContext}>
               <LanguageProvider>
                    <LibrarySystemProvider>
                         <LibraryBranchProvider>
                              <UserProvider>
                                   <CheckoutsProvider>
                                        <HoldsProvider>
                                             <BrowseCategoryProvider>
                                                  <GroupedWorkProvider>
                                                       <NavigationContainer
                                                            theme={navigationTheme}
                                                            ref={navigationRef}
                                                            fallback={<Spinner />}
                                                            linking={{
                                                                 prefixes: prefix,
                                                                 config: {
                                                                      screens: {
                                                                           Login: 'user/login',
                                                                           LaunchStack: {
                                                                                screens: {
                                                                                     DrawerStack: {
                                                                                          screens: {
                                                                                               TabsNavigator: {
                                                                                                    screens: {
                                                                                                         AccountScreenTab: {
                                                                                                              screens: {
                                                                                                                   MySavedSearches: 'user/saved_searches',
                                                                                                                   LoadSavedSearch: 'user/saved_search',
                                                                                                                   MyLists: 'user/lists',
                                                                                                                   MyList: 'user/list',
                                                                                                                   MyLinkedAccounts: 'user/linked_accounts',
                                                                                                                   MyHolds: 'user/holds',
                                                                                                                   MyCheckouts: 'user/checkouts',
                                                                                                                   MyPreferences: 'user/preferences',
                                                                                                                   MyProfile: 'user',
                                                                                                                   MyReadingHistory: 'user/reading_history',
                                                                                                              },
                                                                                                         },
                                                                                                         LibraryCardTab: {
                                                                                                              screens: {
                                                                                                                   LibraryCard: 'user/library_card',
                                                                                                              },
                                                                                                         },
                                                                                                         SearchTab: {
                                                                                                              screens: {
                                                                                                                   SearchResults: 'search',
                                                                                                                   SearchByCategory: 'search/browse_category',
                                                                                                                   SearchByAuthor: 'search/author',
                                                                                                                   SearchByList: 'search/list',
                                                                                                              },
                                                                                                         },
                                                                                                         BrowseTab: {
                                                                                                              screens: {
                                                                                                                   HomeScreen: 'home',
                                                                                                                   GroupedWorkScreen: 'search/grouped_work',
                                                                                                              },
                                                                                                         },
                                                                                                    },
                                                                                               },
                                                                                          },
                                                                                     },
                                                                                },
                                                                           },
                                                                      },
                                                                 },
                                                                 async getInitialURL() {
                                                                      let url = await Linking.getInitialURL();

                                                                      if (url != null) {
                                                                           url = decodeURIComponent(url).replace(/\+/g, ' ');
                                                                           url = url.replace('aspen-lida://', prefix);
                                                                           return url;
                                                                      }

                                                                      const response = await Notifications.getLastNotificationResponseAsync();
                                                                      url = decodeURIComponent(response?.notification.request.content.data.url).replace(/\+/g, ' ');
                                                                      url = url.replace('aspen-lida://', prefix);
                                                                      return url;
                                                                 },
                                                                 subscribe(listener) {
                                                                      const linkingSubscription = Linking.addEventListener('url', ({ url }) => {
                                                                           listener(url);
                                                                      });
                                                                      const subscription = Notifications.addNotificationResponseReceivedListener((response) => {
                                                                           const url = response.notification.request.content.data.url;
                                                                           listener(url);
                                                                      });

                                                                      return () => {
                                                                           subscription.remove();
                                                                           linkingSubscription.remove();
                                                                      };
                                                                 },
                                                            }}>
                                                            <Stack.Navigator
                                                                 screenOptions={{
                                                                      headerShown: false,
                                                                 }}
                                                                 name="RootNavigator">
                                                                 {state.userToken === null ? (
                                                                      // No token found, user isn't signed in
                                                                      <Stack.Screen
                                                                           name="Login"
                                                                           component={LoginScreen}
                                                                           options={{
                                                                                headerShown: false,
                                                                                animationTypeForReplace: state.isSignout ? 'pop' : 'push',
                                                                           }}
                                                                      />
                                                                 ) : (
                                                                      // User is signed in
                                                                      <Stack.Screen
                                                                           name="LaunchStack"
                                                                           component={LaunchStackNavigator}
                                                                           options={{
                                                                                animationEnabled: false,
                                                                                header: () => null,
                                                                           }}
                                                                      />
                                                                 )}
                                                                 <Stack.Screen
                                                                      name="LibraryCardScanner"
                                                                      component={LibraryCardScanner}
                                                                      options={{
                                                                           presentation: 'modal',
                                                                      }}
                                                                 />
                                                            </Stack.Navigator>
                                                       </NavigationContainer>
                                                  </GroupedWorkProvider>
                                             </BrowseCategoryProvider>
                                        </HoldsProvider>
                                   </CheckoutsProvider>
                              </UserProvider>
                         </LibraryBranchProvider>
                    </LibrarySystemProvider>
               </LanguageProvider>
          </AuthContext.Provider>
     );
}

export default Sentry.Native.wrap(App);