import { Ionicons } from '@expo/vector-icons';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { useNavigation, useRoute } from '@react-navigation/native';
import { useQuery } from '@tanstack/react-query';
import { create } from 'apisauce';
import Constants from 'expo-constants';
import * as SecureStore from 'expo-secure-store';
import _ from 'lodash';
import { Button, Center, FormControl, Icon, Input, Pressable } from 'native-base';
import React, { useRef } from 'react';

// custom components and helper files
import { AuthContext } from '../../components/navigation';
import { DisplayMessage } from '../../components/Notifications';
import { BrowseCategoryContext, LanguageContext, LibraryBranchContext, LibrarySystemContext, UserContext } from '../../context/initialContext';
import { navigate } from '../../helpers/RootNavigator';
import { getTermFromDictionary } from '../../translations/TranslationService';
import { getCatalogStatus, getLibraryInfo, getLibraryLanguages } from '../../util/api/library';
import { getLocationInfo } from '../../util/api/location';
import { loginToLiDA, reloadProfile, validateUser } from '../../util/api/user';
import { createAuthTokens, decodeHTML, getHeaders, stripHTML } from '../../util/apiAuth';
import { GLOBALS } from '../../util/globals';
import { formatDiscoveryVersion, LIBRARY, reloadBrowseCategories } from '../../util/loadLibrary';
import { PATRON } from '../../util/loadPatron';
import { ResetExpiredPin } from './ResetExpiredPin';

export const GetLoginForm = (props) => {
     const navigation = useNavigation();
     const barcode = useRoute().params?.barcode ?? null;
     const [loading, setLoading] = React.useState(false);

     const [pinValidationRules, setPinValidationRules] = React.useState([]);
     const [expiredPin, setExpiredPin] = React.useState(false);
     const [resetToken, setResetToken] = React.useState('');
     const [userId, setUserId] = React.useState('');

     const [loginError, setLoginError] = React.useState(false);
     const [loginErrorMessage, setLoginErrorMessage] = React.useState('');

     // securely set and store key:value pairs
     const [valueUser, setUsername] = React.useState('');
     const [valueSecret, setPassword] = React.useState('');

     // show:hide data from password field
     const [showPassword, setShowPassword] = React.useState(false);
     const toggleShowPassword = () => setShowPassword(!showPassword);

     // make ref to move the user to next input field
     const passwordRef = useRef();
     const { signIn } = React.useContext(AuthContext);
     const { updateLibrary, updateCatalogStatus, catalogStatus } = React.useContext(LibrarySystemContext);
     const { updateLocation } = React.useContext(LibraryBranchContext);
     const { updateUser } = React.useContext(UserContext);
     const { updateBrowseCategories } = React.useContext(BrowseCategoryContext);
     const { language, updateLanguage, updateLanguages } = React.useContext(LanguageContext);
     const patronsLibrary = props.selectedLibrary;

     const { usernameLabel, passwordLabel, allowBarcodeScanner, allowCode39 } = props;

     React.useEffect(() => {
          const unsubscribe = navigation.addListener('focus', () => {
               if (barcode) {
                    setUsername(barcode);
               }
          });

          return unsubscribe;
     }, [navigation, barcode]);

     const initialValidation = async () => {
          setLoginError(false);
          setLoginErrorMessage('');
          updateCatalogStatus({
               message: null,
               status: 0,
          });
          const result = await checkAspenDiscovery(patronsLibrary['baseUrl'], patronsLibrary['libraryId']);
          if (result.success) {
               const version = formatDiscoveryVersion(result.library.discoveryVersion);

               // check if catalog is in offline mode
               if (version >= '24.03.00') {
                    const currentStatus = await getCatalogStatus(patronsLibrary['baseUrl']);
                    if (currentStatus) {
                         console.log(currentStatus);
                         updateCatalogStatus(currentStatus);
                         if (currentStatus.status >= 1) {
                              // catalog is offline
                              console.log('catalog is offline');
                              setLoading(false);
                              setLoginError(true);
                              if (currentStatus.message) {
                                   let tmp = stripHTML(currentStatus.message);
                                   tmp = tmp.trim();
                                   setLoginErrorMessage(tmp);
                              } else {
                                   getTermFromDictionary('en', 'catalog_offline_message');
                              }
                              return;
                         } else {
                              console.log('catalog online');
                              console.log(catalogStatus);
                              updateCatalogStatus({
                                   status: 0,
                                   message: null,
                              });
                         }
                    }
               }

               if (version >= '23.02.00') {
                    setPinValidationRules(result.library.pinValidationRules);
                    console.log(patronsLibrary['baseUrl']);
                    const validatedUser = await loginToLiDA(valueUser, valueSecret, patronsLibrary['baseUrl']);
                    if (validatedUser) {
                         GLOBALS.appSessionId = validatedUser.session ?? '';
                         PATRON.language = validatedUser.lang ?? 'en';
                         PATRON.homeLocationId = validatedUser.homeLocationId ?? null;
                         updateLanguage(validatedUser.lang ?? 'en');
                         if (validatedUser.success) {
                              await setAsyncStorage();
                              signIn();
                              setLoading(false);
                         } else {
                              if (validatedUser.resetToken) {
                                   console.log('Expired pin!');
                                   setResetToken(validatedUser.resetToken);
                                   setUserId(validatedUser.userId);
                                   setExpiredPin(true);
                                   setLoading(false);
                              } else {
                                   console.log(validatedUser.message);
                                   setLoginError(true);
                                   setLoginErrorMessage(validatedUser.message);
                                   setLoading(false);
                              }
                         }
                    }
               } else {
                    const validatedUser = await validateUser(valueUser, valueSecret, patronsLibrary['baseUrl']);
                    if (validatedUser) {
                         if (validatedUser.success['id']) {
                              await setAsyncStorage();
                              signIn();
                              setLoading(false);
                         } else {
                              setLoginError(true);
                              setLoginErrorMessage(getTermFromDictionary('en', 'invalid_user'));
                              setLoading(false);
                         }
                    }
               }
          } else {
               setLoading(false);
               setLoginError(true);
               setLoginErrorMessage(getTermFromDictionary('en', 'error_no_library_connection'));
          }
     };

     const openScanner = async () => {
          navigate('LibraryCardScanner', { allowCode39 });
     };

     const setAsyncStorage = async () => {
          await SecureStore.setItemAsync('userKey', valueUser);
          await SecureStore.setItemAsync('secretKey', valueSecret);
          await AsyncStorage.setItem('@lastStoredVersion', Constants.expoConfig.version);
          const autoPickUserHomeLocation = LIBRARY.appSettings?.autoPickUserHomeLocation ?? 0;

          if (PATRON.homeLocationId && !_.includes(GLOBALS.slug, 'aspen-lida') && autoPickUserHomeLocation === 1) {
               console.log(PATRON.homeLocationId);
               await getLocationInfo(GLOBALS.url, PATRON.homeLocationId).then(async (patronsLibrary) => {
                    if (!_.isUndefined(patronsLibrary.baseUrl)) {
                         LIBRARY.url = patronsLibrary.baseUrl;
                         await SecureStore.setItemAsync('library', JSON.stringify(patronsLibrary.libraryId));
                         await AsyncStorage.setItem('@libraryId', JSON.stringify(patronsLibrary.libraryId));
                         await SecureStore.setItemAsync('libraryName', patronsLibrary.parentLibraryDisplayName);
                         await SecureStore.setItemAsync('locationId', JSON.stringify(patronsLibrary.locationId));
                         await AsyncStorage.setItem('@locationId', JSON.stringify(patronsLibrary.locationId));
                         await SecureStore.setItemAsync('solrScope', patronsLibrary.solrScope);

                         await AsyncStorage.setItem('@solrScope', patronsLibrary.solrScope);
                         await AsyncStorage.setItem('@pathUrl', patronsLibrary.baseUrl);
                    } else {
                         // library isn't on correct version of 24.06 ?
                    }
               });
          } else {
               await SecureStore.setItemAsync('library', patronsLibrary['libraryId']);
               await AsyncStorage.setItem('@libraryId', patronsLibrary['libraryId']);
               await SecureStore.setItemAsync('libraryName', patronsLibrary['name']);
               await SecureStore.setItemAsync('locationId', patronsLibrary['locationId']);
               await AsyncStorage.setItem('@locationId', patronsLibrary['locationId']);
               await SecureStore.setItemAsync('solrScope', patronsLibrary['solrScope']);

               await AsyncStorage.setItem('@solrScope', patronsLibrary['solrScope']);
               await AsyncStorage.setItem('@pathUrl', patronsLibrary['baseUrl']);
          }
     };

     if (expiredPin) {
          return <ResetExpiredPin username={valueUser} userId={userId} resetToken={resetToken} url={patronsLibrary['baseUrl']} pinValidationRules={pinValidationRules} setExpiredPin={setExpiredPin} patronsLibrary={patronsLibrary} />;
     }

     return (
          <>
               {loginError ? <DisplayMessage type="error" message={loginErrorMessage} /> : null}
               <FormControl>
                    <FormControl.Label
                         _text={{
                              fontSize: 'sm',
                              fontWeight: 600,
                         }}>
                         {usernameLabel}
                    </FormControl.Label>
                    <Input
                         autoCapitalize="none"
                         size="xl"
                         autoCorrect={false}
                         variant="filled"
                         id="barcode"
                         value={valueUser}
                         onChangeText={(text) => setUsername(text)}
                         returnKeyType="next"
                         textContentType="username"
                         required
                         onSubmitEditing={() => {
                              passwordRef.current.focus();
                         }}
                         blurOnSubmit={false}
                         InputRightElement={
                              allowBarcodeScanner ? (
                                   <Pressable onPress={() => openScanner()}>
                                        <Icon as={<Ionicons name="barcode-outline" />} size={6} mr="2" />
                                   </Pressable>
                              ) : null
                         }
                    />
               </FormControl>
               <FormControl mt={3}>
                    <FormControl.Label
                         _text={{
                              fontSize: 'sm',
                              fontWeight: 600,
                         }}>
                         {passwordLabel}
                    </FormControl.Label>
                    <Input
                         variant="filled"
                         size="xl"
                         type={showPassword ? 'text' : 'password'}
                         returnKeyType="go"
                         textContentType="password"
                         ref={passwordRef}
                         InputRightElement={<Icon as={<Ionicons name={showPassword ? 'eye-outline' : 'eye-off-outline'} />} size="md" ml={1} mr={3} onPress={toggleShowPassword} roundedLeft={0} roundedRight="md" />}
                         onChangeText={(text) => setPassword(text)}
                         onSubmitEditing={async () => {
                              setLoading(true);
                              await initialValidation();
                         }}
                         required
                    />
               </FormControl>

               <Center>
                    <Button
                         mt={3}
                         size="md"
                         color="#30373b"
                         isLoading={loading}
                         isLoadingText={getTermFromDictionary('en', 'logging_in', true)}
                         onPress={async () => {
                              setLoading(true);
                              await initialValidation();
                         }}>
                         {getTermFromDictionary('en', 'login')}
                    </Button>
               </Center>
          </>
     );
};

async function checkAspenDiscovery(url, id) {
     const discovery = create({
          baseURL: url + '/API',
          timeout: GLOBALS.timeoutFast,
          headers: getHeaders(false),
          auth: createAuthTokens(),
          params: {
               id: id,
          },
     });
     const response = await discovery.get('/SystemAPI?method=getLibraryInfo');
     if (response.ok) {
          return {
               success: true,
               library: response.data?.result?.library ?? [],
          };
     }

     return {
          success: false,
          library: [],
     };
}