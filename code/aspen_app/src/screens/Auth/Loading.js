import React from 'react';
import { create } from 'apisauce';
import { useNavigation, useFocusEffect } from '@react-navigation/native';
import { Center, Heading, Spinner, VStack } from 'native-base';
import _ from 'lodash';
import { BrowseCategoryContext, LibraryBranchContext, LibrarySystemContext, UserContext } from '../../context/initialContext';
import { formatBrowseCategories, LIBRARY } from '../../util/loadLibrary';
import { GLOBALS } from '../../util/globals';
import { createAuthTokens, getHeaders, postData } from '../../util/apiAuth';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { getBrowseCategoryListForUser } from '../../util/loadPatron';

export const LoadingScreen = () => {
     const navigation = useNavigation();
     const [loading, setLoading] = React.useState(true);
     const { user, updateUser, resetUser } = React.useContext(UserContext);
     const { library, updateLibrary, resetLibrary } = React.useContext(LibrarySystemContext);
     const { location, updateLocation, updateScope, resetLocation } = React.useContext(LibraryBranchContext);
     const { category, list, updateBrowseCategories, updateBrowseCategoryList, resetBrowseCategories, maxNum, updateMaxCategories } = React.useContext(BrowseCategoryContext);

     useFocusEffect(
          React.useCallback(() => {
               if (!_.isEmpty(user) && !_.isEmpty(library) && !_.isEmpty(location) && !_.isEmpty(category)) {
                    setLoading(false);
                    navigation.navigate('Drawer', {
                         user: user,
                         library: library,
                         location: location,
                    });
               } else {
                    const unsubscribe = async () => {
                         updateMaxCategories(5);
                         await reloadPatronBrowseCategories(5).then((result) => {
                              updateBrowseCategories(result);
                         });
                         await reloadUserProfile().then((result) => {
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

                         setLoading(false);
                         navigation.navigate('Drawer', {
                              user: user,
                              library: library,
                              location: location,
                         });
                    };
                    unsubscribe().then(() => {
                         return () => unsubscribe();
                    });
               }
          }, [])
     );

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

export default LoadingScreen;