import AsyncStorage from '@react-native-async-storage/async-storage';
import { DefaultTheme, NavigationContainer, useNavigationContainerRef } from '@react-navigation/native';
import { createNativeStackNavigator } from '@react-navigation/native-stack';
import { create } from 'apisauce';
import Constants from 'expo-constants';
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
//import * as Sentry from '@sentry/react-native';
import * as Sentry from 'sentry-expo';

import Login from '../screens/Auth/Login';
import { translate } from '../translations/translations';
import { createAuthTokens, getHeaders } from '../util/apiAuth';
import { GLOBALS } from '../util/globals';
import { formatDiscoveryVersion, LIBRARY } from '../util/loadLibrary';
import { PATRON } from '../util/loadPatron';
import { checkCachedUrl } from '../util/login';
import { popAlert, popToast } from './loadError';
import LaunchStackNavigator from '../navigations/LaunchStackNavigator';
import { BrowseCategoryProvider, CheckoutsProvider, GroupedWorkProvider, HoldsProvider, LibraryBranchProvider, LibrarySystemProvider, UserProvider } from '../context/initialContext';
import { SplashScreen } from '../screens/Auth/Splash';
import { RemoveData } from '../util/logout';
import { Platform } from 'react-native';
import { navigationRef } from '../helpers/RootNavigator';

const prefix = Linking.createURL('/');

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

//info.yavapai.catalog@22.12.00+62

Sentry.init({
     dsn: Constants.manifest2?.extra?.expoClient?.extra?.sentryDSN ?? Constants.manifest.extra.sentryDSN,
     enableInExpoDevelopment: false,
     enableAutoSessionTracking: true,
     sessionTrackingIntervalMillis: 10000,
     debug: true,
     tracesSampleRate: 0.25,
     environment: Updates.channel ?? Updates.releaseChannel,
     release: releaseCode,
     dist: distribution,
     integrations: [
          new Sentry.Native.ReactNativeTracing({
               routingInstrumentation,
          }),
     ],
});

export function App() {
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
                              data: action.data,
                              library: action.library,
                              isLoading: false,
                         };
                    case 'SIGN_OUT':
                         return {
                              ...prevState,
                              isSignout: true,
                              userToken: null,
                              data: [],
                              library: [],
                              isLoading: false,
                         };
               }
          },
          {
               isLoading: true,
               isSignout: false,
               userToken: null,
               data: [],
               library: [],
          }
     );

     React.useEffect(() => {
          const timer = setInterval(async () => {
               if (!__DEV__) {
                    const update = await Updates.checkForUpdateAsync();
                    if (update.isAvailable) {
                         console.log('Found an update from Updates Listener...');
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
               }
          }, 15000);
          return () => {
               clearInterval(timer);
          };
     }, []);

     React.useEffect(() => {
          const bootstrapAsync = async () => {
               await getPermissions();

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
               signIn: async (data) => {
                    let userToken;
                    const patronsLibrary = data.patronsLibrary;

                    try {
                         const postBody = new FormData();
                         postBody.append('username', data.valueUser);
                         postBody.append('password', data.valueSecret);
                         const api = create({
                              baseURL: data.libraryUrl + '/API',
                              timeout: 5000,
                              headers: getHeaders(true),
                              auth: createAuthTokens(),
                         });
                         const response = await api.post('/UserAPI?method=validateAccount', postBody);
                         //console.log(response);
                         if (response.ok) {
                              let result = false;
                              if (response.data.result) {
                                   result = response.data.result;
                              }
                              if (result) {
                                   result = result.success;
                                   if (result['id'] != null) {
                                        let patronName = result.firstname;
                                        // if patronName is in all uppercase, force it to sentence-case
                                        if (patronName === patronName.toUpperCase()) {
                                             patronName = patronName.toLowerCase();
                                             patronName = patronName.split(' ');
                                             for (let i = 0; i < patronName.length; i++) {
                                                  patronName[i] = patronName[i].charAt(0).toUpperCase() + patronName[i].slice(1);
                                             }
                                             patronName = patronName.join(' ');
                                        }
                                        userToken = JSON.stringify(result.firstname + ' ' + result.lastname);
                                        console.log('Valid user: ' + userToken);

                                        // update global variables for later
                                        GLOBALS.solrScope = patronsLibrary['solrScope'];
                                        GLOBALS.lastSeen = Constants.manifest2?.extra?.expoClient?.version ?? Constants.manifest.version;
                                        LIBRARY.url = data.libraryUrl;
                                        LIBRARY.name = patronsLibrary['name'];
                                        if (patronsLibrary['version']) {
                                             LIBRARY.version = formatDiscoveryVersion(patronsLibrary['version']);
                                        }
                                        LIBRARY.favicon = patronsLibrary['favicon'];
                                        PATRON.userToken = userToken;
                                        PATRON.scope = patronsLibrary['solrScope'];
                                        PATRON.library = patronsLibrary['libraryId'];
                                        PATRON.location = patronsLibrary['locationId'];

                                        try {
                                             await AsyncStorage.setItem('@userToken', userToken);
                                             await SecureStore.setItemAsync('userKey', data.valueUser);
                                             await SecureStore.setItemAsync('secretKey', data.valueSecret);
                                             // save variables in the Secure Store to access later on
                                             await SecureStore.setItemAsync('patronName', patronName);
                                             await SecureStore.setItemAsync('library', patronsLibrary['libraryId']);
                                             await AsyncStorage.setItem('@libraryId', patronsLibrary['libraryId']);
                                             await SecureStore.setItemAsync('libraryName', patronsLibrary['name']);
                                             await SecureStore.setItemAsync('locationId', patronsLibrary['locationId']);
                                             await AsyncStorage.setItem('@locationId', patronsLibrary['locationId']);
                                             await SecureStore.setItemAsync('solrScope', patronsLibrary['solrScope']);

                                             await AsyncStorage.setItem('@solrScope', patronsLibrary['solrScope']);
                                             await AsyncStorage.setItem('@pathUrl', data.libraryUrl);
                                             await SecureStore.setItemAsync('pathUrl', data.libraryUrl);
                                             await AsyncStorage.setItem('@lastStoredVersion', Constants.manifest2?.extra?.expoClient?.version ?? Constants.manifest.version);
                                             await AsyncStorage.setItem('@patronLibrary', JSON.stringify(patronsLibrary));

                                             dispatch({
                                                  type: 'SIGN_IN',
                                                  token: userToken,
                                                  user: data,
                                                  library: patronsLibrary,
                                             });
                                        } catch (e) {
                                             console.log('Unable to log in user.');
                                             console.log(e);
                                        }
                                   } else {
                                        console.log('Invalid user. Unable to store data.');
                                        popAlert(translate('login.unable_to_login'), translate('login.invalid_user'), 'error');
                                        console.log(response);
                                   }
                              } else {
                                   console.log('Unable to validate user account. ');
                                   popAlert(translate('error.no_server_connection'), "We're unable to validate your account at this time.", 'warning');
                                   console.log(response);
                              }
                         } else {
                              popToast(translate('error.no_server_connection'), translate('error.no_library_connection'), 'warning');
                              console.log(response);
                         }
                    } catch (error) {
                         popAlert(translate('login.unable_to_login'), translate('login.not_enough_data'), 'error');
                         console.log(error);
                    }
               },
               signOut: async () => {
                    await RemoveData().then((res) => {
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
                                                                      Drawer: {
                                                                           screens: {
                                                                                Tabs: {
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
                                                                                          HomeTab: {
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
                                                            name="Root">
                                                            {state.userToken === null ? (
                                                                 // No token found, user isn't signed in
                                                                 <Stack.Screen
                                                                      name="Login"
                                                                      component={Login}
                                                                      options={{
                                                                           headerShown: false,
                                                                           animationTypeForReplace: state.isSignout ? 'pop' : 'push',
                                                                      }}
                                                                 />
                                                            ) : (
                                                                 // User is signed in
                                                                 <Stack.Screen
                                                                      name="Launch"
                                                                      component={LaunchStackNavigator}
                                                                      options={{
                                                                           animationEnabled: false,
                                                                           header: () => null,
                                                                      }}
                                                                 />
                                                            )}
                                                       </Stack.Navigator>
                                                  </NavigationContainer>
                                             </GroupedWorkProvider>
                                        </BrowseCategoryProvider>
                                   </HoldsProvider>
                              </CheckoutsProvider>
                         </UserProvider>
                    </LibraryBranchProvider>
               </LibrarySystemProvider>
          </AuthContext.Provider>
     );
}

async function getPermissions() {
     const { status } = await Location.requestForegroundPermissionsAsync();

     if (status !== 'granted') {
          await SecureStore.setItemAsync('latitude', '0');
          await SecureStore.setItemAsync('longitude', '0');
          PATRON.coords.lat = 0;
          PATRON.coords.long = 0;
          return;
     }

     const location = await Location.getLastKnownPositionAsync({});

     if (location != null) {
          const latitude = JSON.stringify(location.coords.latitude);
          const longitude = JSON.stringify(location.coords.longitude);
          await SecureStore.setItemAsync('latitude', latitude);
          await SecureStore.setItemAsync('longitude', longitude);
          PATRON.coords.lat = latitude;
          PATRON.coords.long = longitude;
     } else {
          await SecureStore.setItemAsync('latitude', '0');
          await SecureStore.setItemAsync('longitude', '0');
          PATRON.coords.lat = 0;
          PATRON.coords.long = 0;
     }
     return location;
}

export default Sentry.Native.wrap(App);