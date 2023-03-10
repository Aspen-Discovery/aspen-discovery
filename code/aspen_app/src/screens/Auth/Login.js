import { Ionicons } from '@expo/vector-icons';
import { create } from 'apisauce';
import Constants from 'expo-constants';
import * as Updates from 'expo-updates';
import _ from 'lodash';
import { MaterialIcons } from "@expo/vector-icons";
import { Badge, Box, Button, Center, HStack, Icon, Image, Input, KeyboardAvoidingView, Pressable, Text, VStack, Modal, FlatList } from 'native-base';
import React, { Component } from 'react';
import { Platform } from 'react-native';
import AsyncStorage from '@react-native-async-storage/async-storage';

// custom components and helper files
import { translate } from '../../translations/translations';
import { getHeaders } from '../../util/apiAuth';
import { GLOBALS, LOGIN_DATA } from '../../util/globals';
import { makeGreenhouseRequestAll, makeGreenhouseRequestNearby } from '../../util/login';
import { GetLoginForm } from './LoginForm';
import { SelectYourLibrary } from './SelectYourLibrary';
import {LIBRARY} from '../../util/loadLibrary';
import {getLibraryLoginLabels} from '../../util/api/library';
import {getTermFromDictionary, getTranslation} from '../../translations/TranslationService';
import {LanguageContext} from '../../context/initialContext';
import { useQuery } from '@tanstack/react-query';
import {getFirstRecord, getVariations} from '../../util/api/item';
import {loadingSpinner} from '../../components/loadingSpinner';
import {loadError} from '../../components/loadError';

export const LoginScreen = () => {
     const { language } = React.useContext(LanguageContext);
     const [isLoading, setIsLoading] = React.useState(false);
     const [selectedLibrary, setSelectedLibrary] = React.useState([]);
     const [usernameLabel, setUsernameLabel] = React.useState('Library Barcode');
     const [passwordLabel, setPasswordLabel] = React.useState('Password/PIN');
     const [showModal, setShowModal] = React.useState(false);
     const [query, setQuery] = React.useState('');
     const isCommunity = GLOBALS.slug === 'aspen-lida';
     const logoImage = Constants.manifest2?.extra?.expoClient?.extra?.loginLogo ?? Constants.manifest.extra.loginLogo;

     const updateSelectedLibrary = async (data) => {
          setSelectedLibrary(data);
          LIBRARY.url = data.baseUrl; // used in some cases before library context is set
          await getLibraryLoginLabels(data.libraryId, data.baseUrl).then(async labels => {
               try {
                    const username = await getTranslation(labels.username, 'en', data.baseUrl);
                    const password = await getTranslation(labels.password, 'en', data.baseUrl);
                    setUsernameLabel(username);
                    setPasswordLabel(password);
               } catch (e) {
                    // couldn't fetch translated login terms for some reason, just use the default as backup
               }
          });
          setShowModal(false);
     }

     return (
         <Box flex={1} alignItems="center" justifyContent="center" safeArea={5}>
              <Image source={{ uri: logoImage }} rounded={25} size="xl" alt="" fallbackSource={require('../../themes/default/aspenLogo.png')} />
              {isCommunity ? (
                  <SelectYourLibraryModal updateSelectedLibrary={updateSelectedLibrary} selectedLibrary={selectedLibrary} query={query} setQuery={query} showModal={showModal} setShowModal={setShowModal} isCommunity={isCommunity}/>
              ) : null}
              <KeyboardAvoidingView behavior={Platform.OS === 'ios' ? 'padding' : 'padding'} width="100%">
                   {selectedLibrary ? (
                       <GetLoginForm />
                   ) : null}
                   {isCommunity && Platform.OS !== 'android' ? (
                       <Button mt={8} size="xs" variant="ghost" colorScheme="secondary" startIcon={<Icon as={Ionicons} name="navigate-circle-outline" size={5} />}>{translate('login.reset_geolocation')}</Button>
                   ) : null}
                   <Center>
                        <Text mt={5} fontSize="xs" color="coolGray.600">
                             {GLOBALS.appVersion} b[{GLOBALS.appBuild}] p[{GLOBALS.appPatch}] c[{GLOBALS.releaseChannel ?? 'Development'}]
                        </Text>
                   </Center>
              </KeyboardAvoidingView>
         </Box>
     )

}

const SelectYourLibraryModal = (data) => {
     const locations = [];
     const {query, setQuery, isCommunity, showModal, setShowModal, updateSelectedLibrary, selectedLibrary} = data;
     const releaseChannel = GLOBALS.releaseChannel ?? Updates.releaseChannel;
     const url = Constants.manifest2?.extra?.expoClient?.extra?.greenhouseUrl ?? Constants.manifest.extra.greenhouseUrl;

     const { status, data: nearbyLibraries, error, isFetching, isPreviousData } = useQuery({
          queryKey: ['nearbyLibrariesFromGreenhouse', url, releaseChannel],
          queryFn: () => fetchNearbyLibrariesFromGreenhouse(releaseChannel, url)
     });

     const { data: allLibraries } = useQuery({
          queryKey: ['allLibrariesFromGreenhouse', url, releaseChannel],
          queryFn: () => fetchAllLibrariesFromGreenhouse(releaseChannel, url),
          enabled: !!nearbyLibraries
     });

     const Header = () => {
          return (
              <Box bg="white" _dark={{ bg: 'coolGray.800' }}>
                   <Input variant="filled" size="lg" autoCorrect={false} status="info" placeholder={translate('search.title')} clearButtonMode="always" value={query} onChangeText={(text) => setQuery(text)}/>
              </Box>
          )
     }

     function Filter() {
          const query = "";
          let haystack = locations;
          if(!_.isEmpty(query) && !_.isEmpty(haystack)) {
               haystack = '';
          }

          if(!isCommunity) {
               return _.filter(haystack, function (branch) {
                    return branch.name.toLowerCase().indexOf(query.toLowerCase()) > -1;
               });
          }

          return _.filter(haystack, function (branch) {
               return branch.name.toLowerCase().indexOf(query.toLowerCase()) > -1 || branch.librarySystem.toLowerCase().indexOf(query.toLowerCase()) > -1;
          })
     }

     return (
         <Center>
              <Button onPress={() => setShowModal(true)} colorScheme="primary" m={5} size="md"
                      startIcon={<Icon as={MaterialIcons} name="place" size={5} />}>{selectedLibrary?.name ? selectedLibrary.name : getTermFromDictionary('en', 'select_your_library')}</Button>
              <Modal isOpen={showModal} size="lg" avoidKeyboard onClose={() => setShowModal(false)}>
                   <Modal.Content bg="white" _dark={{ bg: "coolGray.800" }} maxH="350">
                        <Modal.CloseButton />
                        <Modal.Header>{getTermFromDictionary('en', 'find_your_library')}</Modal.Header>
                        {status === 'loading' || isFetching ? loadingSpinner() : status === 'error' ? loadError(error, '') : (
                            <FlatList
                                stickyHeaderIndices={[0]}
                                keyboardShouldPersistTaps="handled"
                                keyExtractor={(item, index) => index.toString()}
                                renderItem={({ item }) => <Item data={item} isCommunity={isCommunity} setShowModal={setShowModal} updateSelectedLibrary={updateSelectedLibrary}/>}
                                ListHeaderComponent={Header}
                            />
                        )}
                   </Modal.Content>
              </Modal>
         </Center>
     )
}

const Item = (data) => {
     const library = data.data;
     const libraryIcon = library.favicon;
     const {isCommunity, setShowModal, updateSelectedLibrary} = data;
     return (
         <Pressable borderBottomWidth="1" _dark={{ borderColor: 'gray.600' }} borderColor="coolGray.200" onPress={() => updateSelectedLibrary(library)} pl="4" pr="5" py="2">
              <HStack space={3} alignItems="center">
                   <Image
                       key={library.name}
                       borderRadius={100}
                       source={{ uri: libraryIcon }}
                       fallbackSource={require('../../themes/default/aspenLogo.png')}
                       bg="warmGray.200"
                       _dark={{bgColor: 'coolGray.800'}}
                       size={{
                            base: '25px',
                       }}
                       alt={library.name} />
                   <VStack>
                        <Text
                            bold
                            fontSize={{
                                 base: 'sm',
                                 lg: 'md',
                            }}>
                             {library.name}
                        </Text>
                        {isCommunity ? (
                            <Text
                                fontSize={{
                                     base: 'xs',
                                     lg: 'sm',
                                }}>
                                 {library.librarySystem}
                            </Text>
                        ) : null}
                   </VStack>
              </HStack>
         </Pressable>
     )
}

export default class Login extends Component {
     constructor(props) {
          super(props);
          this.state = {
               isLoading: true,
               libraryData: [],
               query: '',
               fetchError: null,
               isFetching: true,
               fetchAll: true,
               listen: null,
               error: false,
               isBeta: false,
               fullData: [],
               locationNum: 0,
               showModal: false,
               hasPendingChanges: false,
               usernameLabel: 'Library Barcode',
               passwordLabel: 'Password/PIN'
          };
          this._isMounted = false;
     }

     componentDidMount = async () => {
          this._isMounted = true;

          let userToken;
          try {
               userToken = await AsyncStorage.getItem('@userToken');
          } catch (e) {
               console.log(e);
          }

          if (!userToken && _.isEmpty(LOGIN_DATA.nearbyLocations)) {
               this._isMounted && (await makeGreenhouseRequestNearby());
               LOGIN_DATA.hasPendingChanges = false;
               if (!LOGIN_DATA.showSelectLibrary) {
                    await this.setLibraryBranch(LOGIN_DATA.nearbyLocations[0]);
               }
          }

          this._isMounted &&
               this.setState({
                    isLoading: false,
                    isFetching: false,
                    userToken: userToken,
               });

          if (this._isMounted && GLOBALS.slug === 'aspen-lida') {
               if (!userToken && GLOBALS.runGreenhouse) {
                    await makeGreenhouseRequestAll();
                    LOGIN_DATA.runGreenhouse = false;
                    LOGIN_DATA.hasPendingChanges = false;
               }
          }
     };

     componentWillUnmount() {
          this._isMounted = false;
     }

     async componentDidUpdate(prevProps, prevState) {
          if (prevState.messages !== LOGIN_DATA.hasPendingChanges) {
               this.setState({
                    messages: LOGIN_DATA.hasPendingChanges,
               });
               LOGIN_DATA.hasPendingChanges = !LOGIN_DATA.hasPendingChanges;
          }

          let userToken;
          try {
               userToken = await AsyncStorage.getItem('@userToken');
          } catch (e) {
               console.log(e);
          }
     }

     // handles the opening or closing of the showLibraries() modal
     handleModal = (newState) => {
          this.setState({
               showModal: newState,
          });
     };

     makeFullGreenhouseRequest = async () => {
          if (LOGIN_DATA.runGreenhouse) {
               this.setState({
                    isFetching: true,
               });
               const api = create({
                    baseURL: Constants.manifest2?.extra?.expoClient?.extra?.greenhouseUrl ?? Constants.manifest.extra.greenhouseUrl + '/API',
                    timeout: GLOBALS.timeoutSlow,
                    headers: getHeaders(),
               });
               const response = await api.get('/GreenhouseAPI?method=getLibraries', {
                    release_channel: Updates.releaseChannel,
               });
               if (response.ok) {
                    const data = response.data;
                    const libraries = _.uniqBy(data.libraries, (v) => [v.locationId, v.libraryId].join());
                    LOGIN_DATA.allLocations = _.uniqBy(libraries, (v) => [v.librarySystem, v.name].join());
                    LOGIN_DATA.runGreenhouse = false;
                    this.setState({
                         isFetching: false,
                    });
                    return true;
               } else {
                    this.setState({
                         error: true,
                    });
                    //console.log(response);
               }
               console.log('Full greenhouse request completed.');
               return false;
          }
     };

     renderListItem = (item, setShowModal, showModal) => {
          const libraryIcon = item.favicon;
          let isCommunity = true;
          if (GLOBALS.slug !== 'aspen-lida') {
               isCommunity = false;
          }
          return (
               <Pressable borderBottomWidth="1" _dark={{ borderColor: 'gray.600' }} borderColor="coolGray.200" onPress={() => this.setNewLibraryBranch(item, showModal)} pl="4" pr="5" py="2">
                    <HStack space={3} alignItems="center">
                         <Image
                             key={item.name}
                             borderRadius={100}
                             source={{ uri: libraryIcon }}
                             fallbackSource={require('../../themes/default/aspenLogo.png')}
                             bg="warmGray.200"
                             _dark={{bgColor: 'coolGray.800'}}
                             size={{
                                  base: '25px',
                             }}
                             alt={item.name} />
                         <VStack>
                              <Text
                                   bold
                                   fontSize={{
                                        base: 'sm',
                                        lg: 'md',
                                   }}>
                                   {item.name}
                              </Text>
                              {isCommunity ? (
                                   <Text
                                        fontSize={{
                                             base: 'xs',
                                             lg: 'sm',
                                        }}>
                                        {item.librarySystem}
                                   </Text>
                              ) : null}
                         </VStack>
                    </HStack>
               </Pressable>
          );
     };

     // FlatList: Renders the search box for filtering
     renderListHeader = () => {
          return (
               <Box bg="white" _dark={{ bg: 'coolGray.800' }}>
                    <Input variant="filled" size="lg" autoCorrect={false} onChangeText={(text) => this.setState({ query: text })} status="info" placeholder={translate('search.title')} clearButtonMode="always" value={this.state.query} />
               </Box>
          );
     };

     filterLibraries(payload) {
          const query = this.state.query;
          if (!_.isEmpty(query) && !_.isEmpty(LOGIN_DATA.allLocations)) {
               payload = LOGIN_DATA.allLocations;
          }
          if (GLOBALS.slug !== 'aspen-lida') {
               return _.filter(payload, function (branch) {
                    return branch.name.toLowerCase().indexOf(query.toLowerCase()) > -1;
               });
          }
          return _.filter(payload, function (branch) {
               return branch.name.toLowerCase().indexOf(query.toLowerCase()) > -1 || branch.librarySystem.toLowerCase().indexOf(query.toLowerCase()) > -1;
          });
     }

     // showLibraries: handles storing the states based on selected library to use later on in validation
     setLibraryBranch = async (item) => {
          if (_.isObject(item) && !this.state.libraryName) {
               LIBRARY.url = item.baseUrl;

               // get labels for login fields
               const labels = await getLibraryLoginLabels(item.libraryId, item.baseUrl);
               let username = this.state.usernameLabel;
               let password = this.state.passwordLabel;
               try {
                    username = await getTranslation(labels.username ?? "Your Name", 'en', item.baseUrl);
                    password = await getTranslation(labels.password ?? "Library Card Number", 'en', item.baseUrl);
               } catch (e) {
                    // couldn't fetch translated login terms for some reason, just use the default as backup
               }

               this.setState({
                    libraryName: item.name,
                    libraryUrl: item.baseUrl,
                    solrScope: item.solrScope,
                    libraryId: item.libraryId,
                    locationId: item.locationId,
                    favicon: item.favicon,
                    logo: item.logo,
                    patronsLibrary: item,
                    usernameLabel: username,
                    passwordLabel: password,
               });
          }
     };

     setNewLibraryBranch = async (item) => {
          if (_.isObject(item)) {
               LIBRARY.url = item.baseUrl;

               // get labels for login fields
               const labels = await getLibraryLoginLabels(item.libraryId, item.baseUrl);
               let username = this.state.usernameLabel;
               let password = this.state.passwordLabel;
               try {
                    username = await getTranslation(labels.username ?? "Your Name", 'en', item.baseUrl);
                    password = await getTranslation(labels.password ?? "Library Card Number", 'en', item.baseUrl);
               } catch (e) {
                    // couldn't fetch translated login terms for some reason, just use the default as backup
               }

               this.setState({
                    libraryName: item.name,
                    libraryUrl: item.baseUrl,
                    solrScope: item.solrScope,
                    libraryId: item.libraryId,
                    locationId: item.locationId,
                    favicon: item.favicon,
                    logo: item.logo,
                    patronsLibrary: item,
                    usernameLabel: username,
                    passwordLabel: password,
               });

          }

          this.handleModal(false);
     };

     /**
    // end of showLibraries() setup
	 **/
     // render the Login screen
     render() {
          const isBeta = this.state.isBeta;
          const logo = Constants.manifest2?.extra?.expoClient?.extra?.loginLogo ?? Constants.manifest.extra.loginLogo;

          let isCommunity = true;
          if (GLOBALS.slug !== 'aspen-lida') {
               isCommunity = false;
          }

          return (
               <Box flex={1} alignItems="center" justifyContent="center" safeArea={5}>
                    <Image source={{ uri: logo }} rounded={25} size="xl" alt={translate('app.name')} fallbackSource={require('../../themes/default/aspenLogo.png')} />
                    {LOGIN_DATA.showSelectLibrary || isCommunity ? <SelectYourLibrary libraryName={this.state.libraryName} uniqueLibraries={this.filterLibraries(LOGIN_DATA.nearbyLocations)} renderListItem={this.renderListItem} renderListHeader={this.renderListHeader} extraData={this.state.query} isRefreshing={this.state.isFetching} showModal={this.state.showModal} handleModal={this.handleModal} /> : null}
                    <KeyboardAvoidingView behavior={Platform.OS === 'ios' ? 'padding' : 'padding'} width="100%">
                         {this.state.libraryName ? <GetLoginForm libraryName={this.state.libraryName} locationId={this.state.locationId} libraryId={this.state.libraryId} libraryUrl={this.state.libraryUrl} solrScope={this.state.solrScope} favicon={this.state.favicon} logo={this.state.logo} sessionId={this.state.sessionId} navigation={this.props.navigation} patronsLibrary={this.state.patronsLibrary} usernameLabel={this.state.usernameLabel} passwordLabel={this.state.passwordLabel}/> : null}

                         {isCommunity && Platform.OS !== 'android' ? (
                              <Button onPress={() => makeGreenhouseRequestNearby()} mt={8} size="xs" variant="ghost" colorScheme="secondary" startIcon={<Icon as={Ionicons} name="navigate-circle-outline" size={5} />}>
                                   {translate('login.reset_geolocation')}
                              </Button>
                         ) : null}
                         <Center>
                              {isBeta ? (
                                   <Badge rounded={5} mt={5}>
                                        {translate('app.beta')}
                                   </Badge>
                              ) : null}
                         </Center>
                         <Center>
                              <Text mt={5} fontSize="xs" color="coolGray.600">
                                   {GLOBALS.appVersion} b[{GLOBALS.appBuild}] p[{GLOBALS.appPatch}] c[{GLOBALS.releaseChannel ?? 'Development'}]
                              </Text>
                         </Center>
                    </KeyboardAvoidingView>
               </Box>
          );
     }
}