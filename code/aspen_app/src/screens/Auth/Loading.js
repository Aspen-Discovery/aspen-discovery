import React from 'react';
import { create } from 'apisauce';
import * as Linking from 'expo-linking';
import * as Notifications from 'expo-notifications';
import { useNavigation, useFocusEffect, useLinkTo } from '@react-navigation/native';
import { Center, Heading, Spinner, VStack } from 'native-base';
import _ from 'lodash';
import { checkVersion } from "react-native-check-version";
import { BrowseCategoryContext, LibraryBranchContext, LibrarySystemContext, UserContext } from '../../context/initialContext';
import { formatBrowseCategories, LIBRARY } from '../../util/loadLibrary';
import { GLOBALS } from '../../util/globals';
import { createAuthTokens, getHeaders, postData } from '../../util/apiAuth';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { getBrowseCategoryListForUser } from '../../util/loadPatron';
import { ForceLogout } from './ForceLogout';
import {UpdateAvailable} from './UpdateAvailable';

const prefix = Linking.createURL('/');

Notifications.setNotificationHandler({
     handleNotification: async () => ({
          shouldShowAlert: true,
          shouldPlaySound: true,
          shouldSetBadge: false,
     }),
});

export const LoadingScreen = () => {
     const linkingUrl = Linking.useURL();
     const linkTo = useLinkTo();
     const navigation = useNavigation();
     const [incomingUrl, setIncomingUrl] = React.useState('');
     const [hasIncomingUrlChanged, setIncomingUrlChanged] = React.useState(false);
     const [loading, setLoading] = React.useState(true);
     const [hasError, setHasError] = React.useState(false);
     const [hasUpdate, setHasUpdate] = React.useState(false);
     const [appStoreUrl, setAppStoreUrl] = React.useState('');
     const [latestVersion, setLatestVersion] = React.useState('');
     const { user, updateUser } = React.useContext(UserContext);
     const { library, updateLibrary } = React.useContext(LibrarySystemContext);
     const { location, updateLocation, updateScope } = React.useContext(LibraryBranchContext);
     const { category, updateBrowseCategories, updateBrowseCategoryList, updateMaxCategories } = React.useContext(BrowseCategoryContext);


     React.useEffect(() => {
          const responseListener = Notifications.addNotificationResponseReceivedListener(response => {
               const url = response?.notification?.request?.content?.data?.url ?? prefix;
               if(url !== incomingUrl) {
                    console.log("Incoming url changed");
                    console.log("OLD > " + incomingUrl);
                    console.log("NEW > " + url);
                    setIncomingUrl(response?.notification?.request?.content?.data?.url ?? prefix);
                    setIncomingUrlChanged(true);
               } else {
                    setIncomingUrlChanged(false);
               }
          });

          return () => {
               responseListener.remove();
          };
     }, []);

     useFocusEffect(
          React.useCallback(() => {
               if (!_.isEmpty(user) && !_.isEmpty(library) && !_.isEmpty(location) && !_.isEmpty(category)) {
                    setLoading(false);
               } else {
                    const unsubscribe = async () => {
                         updateMaxCategories(5);
                         await reloadPatronBrowseCategories(5).then((result) => {
                              updateBrowseCategories(result);
                         });
                         await reloadUserProfile().then((result) => {
                              if (_.isUndefined(result) || _.isEmpty(result)) {
                                   setHasError(true);
                              }
                              updateUser(result);
                         });
                         await reloadLibrarySystem().then((result) => {
                              updateLibrary(result);
                         });
                         await reloadLibraryBranch().then((result) => {
                              updateLocation(result);
                         });
                         await getBrowseCategoryListForUser().then((result) => {
                              updateBrowseCategoryList(result);
                         });

                         await AsyncStorage.getItem('@solrScope').then((result) => {
                              updateScope(result);
                         });

                         await checkStoreVersion().then((result) => {
                              setLatestVersion(result.latest);
                              setHasUpdate(result.needsUpdate);
                              if(result.needsUpdate) {
                                   setAppStoreUrl(result.url);
                              }
                         });
                         setLoading(false);
                    };
                    unsubscribe().then(() => {
                         return () => unsubscribe();
                    });
               }
          }, [])
     );

     if (hasError) {
          return <ForceLogout />;
     }

     if(hasUpdate) {
          return <UpdateAvailable url={appStoreUrl} latest={latestVersion} setHasUpdate={setHasUpdate} />;
     }

     if (!loading) {
          if(hasIncomingUrlChanged) {
               let url = decodeURIComponent(incomingUrl).replace(/\+/g, ' ');
               url = url.replace('aspen-lida://', prefix);
               console.log("incomingUrl > " + url);
               setIncomingUrlChanged(false);
               try {
                    console.log("Trying to open screen based on incomingUrl...")
                    Linking.openURL(url)
               } catch (e) {
                    console.log(e);
               }
          } else if (linkingUrl) {
               if((linkingUrl !== prefix) && (linkingUrl !== incomingUrl)) {
                    setIncomingUrl(linkingUrl);
                    console.log("Updated incoming url");
                    const { hostname, path, queryParams, scheme } = Linking.parse(linkingUrl);
                    console.log('linkingUrl > ' + linkingUrl);
                    console.log(
                        `Linked to app with hostname: ${hostname}, path: ${path}, scheme: ${scheme} and data: ${JSON.stringify(
                            queryParams
                        )}`
                    );
                    try {
                         if(scheme !== 'exp') {
                              console.log("Trying to open screen based on linkingUrl...");
                              const url = linkingUrl.replace('aspen-lida://', prefix);
                              console.log('url > ' + url);
                              linkTo('/' + url);
                         } else {
                              if(path) {
                                   console.log("Trying to open screen based on linkingUrl to Expo app...");
                                   let url = '/' + path;
                                   if(!_.isEmpty(queryParams)) {
                                        const params = new URLSearchParams(queryParams);
                                        const str = params.toString();
                                        url = url + "?" + str + "&url=" + library.baseUrl;
                                   }
                                   console.log('url > ' + url);
                                   console.log('linkingUrl > ' + linkingUrl);
                                   linkTo('/' + url);
                              }
                         }
                    } catch (e) {
                         console.log(e);
                    }
               }
          }

          navigation.navigate('DrawerStack', {
               user: user,
               library: library,
               location: location,
          });
     }

     return (
          <Center flex={1} px="3">
               <VStack space={5} alignItems="center">
                    <Spinner size="lg" />
                    <Heading color="primary.500" fontSize="md">
                         Dusting the shelves...
                    </Heading>
               </VStack>
          </Center>
     );
};

/* class LoadingScreenB extends Component {
 constructor() {
 super();
 this.state = {
 isLoading: true,
 category: this?.context?.category ?? [],
 user: this?.props?.route?.userContext?.user ?? [],
 };
 }

 componentDidMount() {
 if (this.state.category) {
 console.log(this.state.category);
 }
 }

 componentDidUpdate(prevProps, prevState) {
 if (prevState.category !== this.context.category) {
 if (prevState.user !== this?.props?.route?.userContext?.user) {
 this.props.navigation.navigate('Drawer');
 }
 }
 }

 render() {
 return (
 <Center flex={1} px="3">
 <VStack space={5} alignItems="center">
 <Spinner size="lg" />
 <Heading color="primary.500" fontSize="md">
 Dusting the shelves...
 </Heading>
 </VStack>
 </Center>
 );
 }
 } */

async function reloadLibrarySystem() {
     let libraryId;

     try {
          libraryId = await AsyncStorage.getItem('@libraryId');
     } catch (e) {
          console.log(e);
     }
     const discovery = create({
          baseURL: LIBRARY.url + '/API',
          timeout: GLOBALS.timeoutFast,
          headers: getHeaders(),
          auth: createAuthTokens(),
          params: {
               id: libraryId,
          },
     });
     const response = await discovery.get('/SystemAPI?method=getLibraryInfo');
     if (response.ok) {
          if (response.data.result) {
               return response.data.result.library;
          }
     }

     return [];
}

async function reloadLibraryBranch() {
     let scope, locationId;
     try {
          scope = await AsyncStorage.getItem('@solrScope');
          locationId = await AsyncStorage.getItem('@locationId');
     } catch (e) {
          console.log(e);
     }

     const discovery = create({
          baseURL: LIBRARY.url + '/API',
          timeout: GLOBALS.timeoutFast,
          headers: getHeaders(),
          auth: createAuthTokens(),
          params: {
               id: locationId,
               library: scope,
               version: GLOBALS.appVersion,
          },
     });
     const response = await discovery.get('/SystemAPI?method=getLocationInfo');
     if (response.ok) {
          if (response.data.result) {
               return response.data.result.location;
          }
     }
     return [];
}

async function reloadUserProfile() {
     const postBody = await postData();
     const discovery = create({
          baseURL: LIBRARY.url + '/API',
          timeout: GLOBALS.timeoutAverage,
          headers: getHeaders(true),
          auth: createAuthTokens(),
          params: {
               linkedUsers: true,
               checkIfValid: false,
          },
     });
     const response = await discovery.post('/UserAPI?method=getPatronProfile', postBody);
     //console.log(response);
     if (response.ok) {
          if (response.data.result) {
               return response.data.result.profile;
          }
     }
     return [];
}

async function reloadPatronBrowseCategories(maxNum) {
     const postBody = await postData();
     const discovery = create({
          baseURL: LIBRARY.url + '/API',
          timeout: GLOBALS.timeoutAverage,
          headers: getHeaders(true),
          auth: createAuthTokens(),
          params: {
               maxCategories: maxNum,
               LiDARequest: true,
          },
     });
     const response = await discovery.post('/SearchAPI?method=getAppActiveBrowseCategories&includeSubCategories=true', postBody);
     if (response.ok) {
          if (response.data.result) {
               return formatBrowseCategories(response.data.result);
          }
     }
     return [];
}

async function checkStoreVersion() {
     try {
          const version = await checkVersion({
               bundleId: GLOBALS.bundleId,
               currentVersion: GLOBALS.appVersion
          });
          if(version.needsUpdate) {
               return {
                    needsUpdate: true,
                    url: version.url,
                    latest: version.version,
               }
          }
     } catch (e) {
          console.log(e);
     }

     return {
          needsUpdate: false,
          url: null,
          latest: GLOBALS.appVersion
     }
}

export default LoadingScreen;