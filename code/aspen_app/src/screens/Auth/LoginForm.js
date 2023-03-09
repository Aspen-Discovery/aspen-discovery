import { Ionicons } from '@expo/vector-icons';
import { Button, Center, FormControl, Icon, Input } from 'native-base';
import React, { useRef } from 'react';
import AsyncStorage from '@react-native-async-storage/async-storage';
import * as SecureStore from 'expo-secure-store';
import Constants from 'expo-constants';
import { create } from 'apisauce';

// custom components and helper files
import { AuthContext } from '../../components/navigation';
import { translate } from '../../translations/translations';
import {getBrowseCategories, getLanguages, getLibraryBranch, getLibrarySystem, getUserProfile} from '../../util/login';
import {BrowseCategoryContext, LanguageContext, LibraryBranchContext, LibrarySystemContext, UserContext} from '../../context/initialContext';
import {ResetExpiredPin} from './ResetExpiredPin';
import {GLOBALS} from '../../util/globals';
import {createAuthTokens, getHeaders} from '../../util/apiAuth';
import {formatDiscoveryVersion} from '../../util/loadLibrary';
import {DisplayMessage} from '../../components/Notifications';
import {loginToLiDA, validateUser} from '../../util/api/user';
import {getTermFromDictionary} from '../../translations/TranslationService';

export const GetLoginForm = (props) => {
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
     const { updateLibrary } = React.useContext(LibrarySystemContext);
     const { updateLocation } = React.useContext(LibraryBranchContext);
     const { updateUser } = React.useContext(UserContext);
     const { updateBrowseCategories } = React.useContext(BrowseCategoryContext);
     const { language, updateLanguage, updateLanguages } = React.useContext(LanguageContext);
     const patronsLibrary = props.patronsLibrary;

     const {usernameLabel, passwordLabel} = props;

     const initialValidation = async () => {
         const result = await checkAspenDiscovery(patronsLibrary['baseUrl'], patronsLibrary['libraryId']);
         if(result.success) {
             const version = formatDiscoveryVersion(result.library.discoveryVersion);
             if(version >= '23.02.00') {
                 setPinValidationRules(result.library.pinValidationRules);
                 const validatedUser = await loginToLiDA(valueUser, valueSecret, patronsLibrary['baseUrl']);
                 if(validatedUser) {
                     if(validatedUser.success) {
                         await setContext();
                         await setAsyncStorage();
                         signIn();
                         setLoading(false);
                     } else {
                         if(validatedUser.resetToken) {
                             console.log("Expired pin!")
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
                 if(validatedUser) {
                     if(validatedUser.success['id']) {
                         await setContext();
                         await setAsyncStorage();
                         signIn();
                         setLoading(false);
                     } else {
                         console.log('Unable to validate user with provided information.');
                         setLoginError(true);
                         setLoginErrorMessage(translate('login.invalid_user'));
                         setLoading(false);
                     }
                 }
             }
         } else {
             setLoading(false);
             setLoginError(true);
             setLoginErrorMessage("Unable to establish connection with library. Please try again later.")
         }
     }

     const setContext = async () => {
         const library = await getLibrarySystem({ patronsLibrary });
         updateLibrary(library);
         const location = await getLibraryBranch({ patronsLibrary });
         updateLocation(location);
         const user = await getUserProfile({ patronsLibrary }, { valueUser }, { valueSecret });
         updateUser(user);
         updateLanguage(user.interfaceLanguage ?? 'en');
         const categories = await getBrowseCategories({ patronsLibrary }, { valueUser }, { valueSecret });
         updateBrowseCategories(categories);
         const languages = await getLanguages({ patronsLibrary });
         updateLanguages(languages);
     }

     const setAsyncStorage = async () => {
         await SecureStore.setItemAsync('userKey', valueUser);
         await SecureStore.setItemAsync('secretKey', valueSecret);
         await SecureStore.setItemAsync('library', patronsLibrary['libraryId']);
         await AsyncStorage.setItem('@libraryId', patronsLibrary['libraryId']);
         await SecureStore.setItemAsync('libraryName', patronsLibrary['name']);
         await SecureStore.setItemAsync('locationId', patronsLibrary['locationId']);
         await AsyncStorage.setItem('@locationId', patronsLibrary['locationId']);
         await SecureStore.setItemAsync('solrScope', patronsLibrary['solrScope']);

         await AsyncStorage.setItem('@solrScope', patronsLibrary['solrScope']);
         await AsyncStorage.setItem('@pathUrl', patronsLibrary['baseUrl']);
         await SecureStore.setItemAsync('pathUrl', patronsLibrary['baseUrl']);
         await AsyncStorage.setItem('@lastStoredVersion', Constants.manifest2?.extra?.expoClient?.version ?? Constants.manifest.version);
         await AsyncStorage.setItem('@patronLibrary', JSON.stringify(patronsLibrary));
     }

     if(expiredPin) {
         return <ResetExpiredPin username={valueUser} userId={userId} resetToken={resetToken} url={patronsLibrary['baseUrl']} pinValidationRules={pinValidationRules} setExpiredPin={setExpiredPin} patronsLibrary={patronsLibrary} />
     }

     return (
          <>
              {loginError ? <DisplayMessage type="error" message={loginErrorMessage}/> : null}
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
                         onChangeText={(text) => setUsername(text)}
                         returnKeyType="next"
                         textContentType="username"
                         required
                         onSubmitEditing={() => {
                              passwordRef.current.focus();
                         }}
                         blurOnSubmit={false}
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
                             await initialValidation()
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
                         isLoadingText={getTermFromDictionary(language, 'logging_in', true)}
                         onPress={async () => {
                             setLoading(true);
                             await initialValidation();
                         }}>
                         {getTermFromDictionary(language, 'login')}
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
            id: id
        }
    });
    const response = await discovery.get('/SystemAPI?method=getLibraryInfo');
    if(response.ok) {
        return {
            success: true,
            library: response.data?.result?.library ?? [],
        }
    }

    return {
        success: false,
        library: [],
    };
}