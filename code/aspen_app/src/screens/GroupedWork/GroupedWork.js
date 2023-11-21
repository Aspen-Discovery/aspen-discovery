import { MaterialIcons } from '@expo/vector-icons';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { useNavigation, useRoute } from '@react-navigation/native';
import { useQueries, useQuery, useQueryClient } from '@tanstack/react-query';
import _ from 'lodash';
import { AlertDialog, Box, Button, Center, HStack, Icon, Image, ScrollView, Text, useToken } from 'native-base';
import React, { Component, useEffect } from 'react';
import { SafeAreaView } from 'react-native';
import { Rating } from 'react-native-elements';

// custom components and helper files
import { loadError } from '../../components/loadError';
import { loadingSpinner } from '../../components/loadingSpinner';
import { DisplaySystemMessage } from '../../components/Notifications';
import { GroupedWorkContext, LanguageContext, LibrarySystemContext, SystemMessagesContext, UserContext } from '../../context/initialContext';
import { userContext } from '../../context/user';
import { navigateStack, startSearch } from '../../helpers/RootNavigator';
import { getTermFromDictionary } from '../../translations/TranslationService';
import { getFirstRecord, getVariations } from '../../util/api/item';
import { getLinkedAccounts } from '../../util/api/user';
import { getGroupedWork } from '../../util/api/work';
import { decodeHTML } from '../../util/apiAuth';
import { getPickupLocations } from '../../util/loadLibrary';
import { PATRON } from '../../util/loadPatron';
import { getGroupedWork221200, getItemDetails } from '../../util/recordActions';
import AddToList from '../Search/AddToList';
import Manifestation from './Manifestation';
import { GetOverDriveSettings } from './OverDriveSettings';
import Variations from './Variations';

export const GroupedWorkScreen = () => {
     const route = useRoute();
     const queryClient = useQueryClient();
     const navigation = useNavigation();
     const id = route.params.id;
     const prevRoute = route.params.prevRoute ?? null;
     const { user, locations, accounts, cards, updatePickupLocations, updateLinkedAccounts, updateLibraryCards } = React.useContext(UserContext);
     const { groupedWork, format, language, updateGroupedWork, updateFormat } = React.useContext(GroupedWorkContext);
     const { library } = React.useContext(LibrarySystemContext);
     const { language: userLanguage } = React.useContext(LanguageContext);
     const { systemMessages, updateSystemMessages } = React.useContext(SystemMessagesContext);
     const [isLoading, setLoading] = React.useState(false);

     const { status, data, error, isFetching } = useQuery(['groupedWork', id, userLanguage, library.baseUrl], () => getGroupedWork(route.params.id, userLanguage, library.baseUrl));

     React.useEffect(() => {
          let isSubscribed = true;
          if (!_.isUndefined(data) && !_.isEmpty(data)) {
               const update = async () => {
                    if (isSubscribed) {
                         updateGroupedWork(data);
                         updateFormat(data.format);
                         await getLinkedAccounts(user, cards, library.barcodeStyle, library.baseUrl, language).then((result) => {
                              if (accounts !== result.accounts) {
                                   updateLinkedAccounts(result.accounts);
                              }
                              if (cards !== result.cards) {
                                   updateLibraryCards(result.cards);
                              }
                         });
                         await getPickupLocations(library.baseUrl).then((result) => {
                              if (locations !== result) {
                                   updatePickupLocations(result);
                              }
                         });
                    }
               };
               update().catch(console.error);

               return () => (isSubscribed = false);
          }
     }, [data]);

     const showSystemMessage = () => {
          if (_.isArray(systemMessages)) {
               return systemMessages.map((obj, index, collection) => {
                    if (obj.showOn === '0') {
                         return <DisplaySystemMessage style={obj.style} message={obj.message} dismissable={obj.dismissable} id={obj.id} all={systemMessages} url={library.baseUrl} updateSystemMessages={updateSystemMessages} queryClient={queryClient} />;
                    }
               });
          }
          return null;
     };

     return (
          <ScrollView>
               {status === 'loading' || isFetching ? (
                    <Box pt={50}>{loadingSpinner('Fetching data...')}</Box>
               ) : status === 'error' ? (
                    <Box pt={50}>{loadError(error, '')}</Box>
               ) : (
                    <>
                         <Box h={{ base: 125, lg: 200 }} w="100%" bgColor="warmGray.200" _dark={{ bgColor: 'coolGray.900' }} zIndex={-1} position="absolute" left={0} top={0} />
                         {systemMessages ? <Box safeArea={2}>{showSystemMessage()}</Box> : null}
                         <DisplayGroupedWork data={data.results} initialFormat={data.format} updateFormat={data.format} />
                    </>
               )}
          </ScrollView>
     );
};

const DisplayGroupedWork = (payload) => {
     const backgroundColor = useToken('colors', 'warmGray.200');
     const groupedWork = payload.data;
     const route = useRoute();
     const id = route.params.id;
     const { format } = React.useContext(GroupedWorkContext);
     const { library } = React.useContext(LibrarySystemContext);
     const { language } = React.useContext(LanguageContext);

     const formats = Object.keys(groupedWork.formats);
     if (formats) {
          useQueries({
               queries: formats.map((format) => {
                    return {
                         queryKey: ['recordId', groupedWork.id, format, language, library.baseUrl],
                         queryFn: () => getFirstRecord(id, format, language, library.baseUrl),
                    };
               }),
          });
     }

     useQueries({
          queries: formats.map((format) => {
               return {
                    queryKey: ['variation', groupedWork.id, format, language, library.baseUrl],
                    queryFn: () => getVariations(id, format, language, library.baseUrl),
               };
          }),
     });

     return (
          <Box safeArea={5} w="100%">
               <Center mt={5} width="100%">
                    <Image resizeMethod="scale" resizeMode="contain" alt={groupedWork.title} source={{ uri: groupedWork.cover }} w={{ base: 200, lg: 300 }} h={{ base: 250, lg: 350 }} shadow={3} style={{ borderRadius: 4, resizeMode: 'contain', overlayColor: backgroundColor }} />
                    {getTitle(groupedWork.title)}
                    {getAuthor(groupedWork.author)}
               </Center>
               {getLanguage(groupedWork.language)}
               {getFormats(groupedWork.formats)}
               <Variations format={format} data={groupedWork} />
               <AddToList itemId={groupedWork.id} btnStyle="lg" />
               {getDescription(groupedWork.description)}
          </Box>
     );
};

const getTitle = (title) => {
     if (title) {
          return (
               <>
                    <Text fontSize={{ base: 'lg', lg: '2xl' }} bold pt={5} alignText="center">
                         {title}
                    </Text>
               </>
          );
     } else {
          return null;
     }
};

const getAuthor = (author) => {
     const { library } = React.useContext(LibrarySystemContext);
     if (author) {
          return (
               <Button pt={2} size="sm" variant="link" colorScheme="tertiary" _text={{ fontWeight: '600' }} leftIcon={<Icon as={MaterialIcons} name="search" size="xs" mr="-1" />} onPress={() => startSearch(author, 'SearchResults', library.baseUrl)}>
                    {author}
               </Button>
          );
     }
     return null;
};

const Format = (data) => {
     const format = data.data;
     const key = data.format;
     const isSelected = data.isSelected;
     const updateFormat = data.updateFormat;
     const btnStyle = isSelected === key ? 'solid' : 'outline';

     if (isSelected === key) {
          updateFormat(key);
     }

     return (
          <Button size="sm" colorScheme="secondary" mb={1} mr={1} variant={btnStyle} onPress={() => updateFormat(key)}>
               {format.label}
          </Button>
     );
};

const getDescription = (description) => {
     if (description) {
          return (
               <Text mt={5} mb={5} fontSize={{ base: 'md', lg: 'lg' }} lineHeight={{ base: '22px', lg: '26px' }}>
                    {decodeHTML(description)}
               </Text>
          );
     } else {
          return null;
     }
};

const getLanguage = (language) => {
     const { language: user_language } = React.useContext(LanguageContext);
     if (language) {
          return (
               <HStack mt={3} mb={1}>
                    <Text fontSize={{ base: 'xs', lg: 'md' }} bold>
                         {getTermFromDictionary(user_language, 'language')}:
                    </Text>
                    <Text fontSize={{ base: 'xs', lg: 'md' }} ml={1}>
                         {language}
                    </Text>
               </HStack>
          );
     } else {
          return null;
     }
};

const getFormats = (formats) => {
     const { language } = React.useContext(LanguageContext);
     const { format, updateFormat } = React.useContext(GroupedWorkContext);
     if (formats) {
          return (
               <>
                    <Text fontSize={{ base: 'xs', lg: 'md' }} bold mt={3} mb={1}>
                         {getTermFromDictionary(language, 'format')}:
                    </Text>
                    <Button.Group flexDirection="row" flexWrap="wrap">
                         {_.map(_.keys(formats), function (item, index, array) {
                              return <Format key={index} format={item} data={formats[item]} isSelected={format} updateFormat={updateFormat} />;
                         })}
                    </Button.Group>
               </>
          );
     } else {
          return null;
     }
};

export class GroupedWork221200 extends Component {
     static contextType = userContext;

     constructor(props, context) {
          super(props, context);
          this.state = {
               isLoading: true,
               locations: [],
               linkedAccounts: 0,
               hasError: false,
               error: null,
               items: [],
               data: [],
               ratingData: null,
               variations: null,
               formats: null,
               languages: null,
               format: null,
               language: null,
               itemDetails: null,
               status: null,
               alert: false,
               shouldReload: false,
               lastListUsed: 0,
               showOverDriveSettings: false,
               user: this.props.route.params?.userContext ?? [],
               library: this.props.route.params?.libraryContext ?? [],
               userLanguage: this.props.route.params?.language ?? 'en',
          };
          this.locations = [];
          this._isMounted = false;
     }

     authorSearch = (author, libraryUrl) => {
          navigateStack('BrowseTab', 'SearchResults', {
               term: author,
               libraryUrl: libraryUrl,
          });
     };

     openCheckouts = () => {
          navigateStack('AccountScreenTab', 'MyCheckouts');
     };

     openHolds = () => {
          navigateStack('AccountScreenTab', 'MyHolds');
     };

     componentDidMount = async () => {
          this._isMounted = true;

          let discoveryVersion = '22.06.00';
          if (this.state.library.discoveryVersion) {
               let version = this.state.library.discoveryVersion;
               version = version.split(' ');
               discoveryVersion = version[0];
          }

          if (this._isMounted) {
               this.setState({
                    discoveryVersion: discoveryVersion,
               });
          }

          if (this._isMounted) {
               await this._fetchItemData().then((res) => {
                    this.setState({ isLoading: false });
               });

               await this._fetchLocations();
               await this._fetchLinkedAccounts();
               await this._getLastListUsed();
          }
     };

     componentWillUnmount() {
          this._isMounted = false;
     }

     _getLastListUsed = async () => {
          let lastListUsed;
          try {
               lastListUsed = await AsyncStorage.getItem('@lastListUsed');
               this.setState({
                    lastListUsed,
               });
          } catch (e) {
               console.log(e);
          }
     };

     _fetchItemData = async () => {
          const { navigation, route } = this.props;
          const givenItem = route.params?.id ?? 'null';
          const libraryUrl = route.params?.url ?? 'unknown';
          const language = route.params?.language ?? 'en';

          await getGroupedWork221200(libraryUrl, givenItem).then((response) => {
               if (response === 'TIMEOUT_ERROR') {
                    this.setState({
                         hasError: true,
                         error: getTermFromDictionary(language, 'error_timeout'),
                    });
               } else {
                    try {
                         this.setState({
                              data: response,
                              ratingData: response.ratingData,
                              variations: response.variation,
                              formats: response.filterOn.format,
                              languages: response.filterOn.language,
                              format: response.filterOn.format[0].format,
                              language: response.filterOn.language[0].language,
                              groupedWorkId: response.id,
                              groupedWorkTitle: response.title,
                              hasError: false,
                              error: null,
                         });
                    } catch (error) {
                         this.setState({
                              hasError: true,
                              error: getTermFromDictionary(language, 'error_no_data'),
                         });
                    }
               }
          });

          if (this.state.discoveryVersion <= '22.09.00') {
               await this.loadItemDetails(libraryUrl);
          }
     };

     _fetchLocations = async () => {
          let locations = [];
          const tmp = await AsyncStorage.getItem('@pickupLocations');
          if (tmp) {
               locations = JSON.parse(tmp);
          } else {
               locations = PATRON.pickupLocations;
          }
          this.setState({
               locations,
               hasError: false,
               error: null,
          });
     };

     _fetchLinkedAccounts = async () => {
          const { navigation, route } = this.props;
          const libraryUrl = this.state.library.baseUrl;

          await getLinkedAccounts(libraryUrl).then((response) => {
               this.setState({
                    linkedAccounts: response,
                    //numLinkedAccounts:  Object.keys(response).length,
               });
          });
     };

     // shows the author information on the screen and allows the link to be clickable. hides it if there is no author.
     showAuthor = (libraryUrl) => {
          if (this.state.data.author) {
               return (
                    <Button
                         pt={2}
                         size="sm"
                         variant="link"
                         colorScheme="tertiary"
                         _text={{
                              fontWeight: '600',
                         }}
                         leftIcon={<Icon as={MaterialIcons} name="search" size="xs" mr="-1" />}
                         onPress={() => this.authorSearch(this.state.data.author, libraryUrl)}>
                         {this.state.data.author}
                    </Button>
               );
          }
     };

     formatOptions = () => {
          return this.state.formats.map((format, index) => {
               const btnVariant = this.state.format === format.format ? 'solid' : 'outline';

               return (
                    <Button key={index} variant={btnVariant} size="sm" mb={1} onPress={() => this.setState({ format: format.format })}>
                         {format.format}
                    </Button>
               );
          });
     };

     languageOptions = () => {
          return this.state.languages.map((language, index) => {
               const btnVariant = this.state.language === language.language ? 'solid' : 'outline';

               return (
                    <Button key={index} variant={btnVariant} size="sm" onPress={() => this.setState({ language: language.language })}>
                         {language.language}
                    </Button>
               );
          });
     };

     showAlert = (response) => {
          if (!_.isUndefined(response.message)) {
               this.setState({
                    alert: true,
                    alertTitle: response.title,
                    alertMessage: response.message,
                    alertAction: response.action,
                    alertStatus: response.success,
               });

               if (response.action) {
                    if (response.action.includes('Checkouts')) {
                         this.setState({
                              alertNavigateTo: 'CheckedOut',
                         });
                    } else if (response.action.includes('Holds')) {
                         this.setState({
                              alertNavigateTo: 'Holds',
                         });
                    }
               }
          } else if (response.getPrompt === true) {
               this.setState({
                    prompt: true,
                    promptItemId: response.itemId,
                    promptSource: response.source,
                    promptPatronId: response.patronId,
                    promptTitle: getTermFromDictionary(this.state.userLanguage, 'hold_options'),
               });
          }
     };

     hideAlert = () => {
          this.setState({ alert: false });
          setTimeout(
               function () {
                    this._fetchItemData();
               }.bind(this),
               1000
          );
     };

     hidePrompt = () => {
          this.setState({ prompt: false });
          setTimeout(
               function () {
                    this._fetchItemData();
               }.bind(this),
               1000
          );
     };

     // Trigger a context refresh
     updateProfile = async () => {
          console.log('Getting new profile data from item details...');
          /*await getProfile().then((response) => {
		 this.context.user = response;
		 }); */
     };

     cancelRef = () => {
          useEffect(() => {
               React.useRef();
          });
     };

     initialRef = () => {
          useEffect(() => {
               React.useRef();
          });
     };

     // handles the opening or closing of the GetOverDriveSettings() modal
     handleOverDriveSettings = (newState) => {
          //console.log("updating modal state...")
          this.setState({
               showOverDriveSettings: newState,
          });
     };

     loadItemDetails = async (libraryUrl) => {
          await getItemDetails(libraryUrl, this.state.groupedWorkId, this.state.format).then((response) => {
               this.setState({
                    itemDetails: response,
                    isLoading: false,
               });
          });
     };

     setEmail = (email) => {
          this.setState({ overdriveEmail: email });
     };

     setRememberPrompt = (remember) => {
          this.setState({ promptForOverdriveEmail: remember });
     };

     render() {
          const user = this.state.user;
          const library = this.state.library;

          if (this.state.isLoading) {
               return loadingSpinner();
          }

          if (this.state.hasError) {
               return loadError(this.state.error, this._fetchResults);
          }

          let displayTitle = this.state.data.title;
          if (this.state.data.subtitle && this.state.data.subtitle !== '') {
               displayTitle = displayTitle.concat(': ', this.state.data.subtitle);
          }

          let ratingCount = 0;
          if (this.state.ratingData != null) {
               ratingCount = this.state.ratingData.count;
          }

          let ratingAverage = 0;
          if (this.state.ratingData != null) {
               ratingAverage = this.state.ratingData.average;
          }

          let discoveryVersion;
          if (library.discoveryVersion) {
               let version = library.discoveryVersion;
               version = version.split(' ');
               discoveryVersion = version[0];
          } else {
               discoveryVersion = '22.06.00';
          }

          //console.log(this.state.data);

          return (
               <SafeAreaView style={{ flex: 1 }}>
                    <ScrollView nestedScrollEnabled={true}>
                         <Box
                              h={{
                                   base: 125,
                                   lg: 200,
                              }}
                              w="100%"
                              bgColor="warmGray.200"
                              _dark={{ bgColor: 'coolGray.900' }}
                              zIndex={-1}
                              position="absolute"
                              left={0}
                              top={0}
                         />
                         <Box flex={1} safeArea={5}>
                              <Center mt={5}>
                                   <Box
                                        w={{
                                             base: 200,
                                             lg: 300,
                                        }}
                                        h={{
                                             base: 250,
                                             lg: 350,
                                        }}
                                        shadow={3}>
                                        <Image
                                             alt={this.state.data.title}
                                             source={{ uri: this.state.data.cover }}
                                             style={{
                                                  width: '100%',
                                                  height: '100%',
                                                  borderRadius: 4,
                                             }}
                                        />
                                   </Box>
                                   <Text
                                        fontSize={{
                                             base: 'lg',
                                             lg: '2xl',
                                        }}
                                        bold
                                        pt={5}
                                        alignText="center">
                                        {displayTitle}
                                   </Text>
                                   {this.showAuthor(library.baseUrl)}
                                   {ratingCount > 0 ? <Rating imageSize={10} readonly count={ratingCount} startingValue={ratingAverage} type="custom" tintColor="white" ratingBackgroundColor="transparent" style={{ paddingTop: 5 }} /> : null}
                              </Center>
                              <Text
                                   fontSize={{
                                        base: 'xs',
                                        lg: 'md',
                                   }}
                                   bold
                                   mt={3}
                                   mb={1}>
                                   {getTermFromDictionary(this.state.userLanguage, 'format')}
                              </Text>
                              {this.state.formats ? (
                                   <Button.Group
                                        colorScheme="secondary"
                                        style={{
                                             flex: 1,
                                             flexWrap: 'wrap',
                                        }}>
                                        {this.formatOptions()}
                                   </Button.Group>
                              ) : null}
                              <Text
                                   fontSize={{
                                        base: 'xs',
                                        lg: 'md',
                                   }}
                                   bold
                                   mt={3}
                                   mb={1}>
                                   {getTermFromDictionary(this.state.userLanguage, 'language')}
                              </Text>
                              {this.state.languages && discoveryVersion <= '22.05.00' ? <Button.Group colorScheme="secondary">{this.languageOptions()}</Button.Group> : null}

                              {discoveryVersion >= '22.06.00' && this.state.data.language ? (
                                   <Text
                                        fontSize={{
                                             base: 'xs',
                                             lg: 'md',
                                        }}
                                        mt={3}
                                        mb={1}>
                                        {this.state.data.language}
                                   </Text>
                              ) : null}

                              {this.state.variations ? (
                                   <Manifestation
                                        key={this.state.groupedWorkId}
                                        navigation={this.props.navigation}
                                        data={this.state.variations}
                                        format={this.state.format}
                                        language={this.state.language}
                                        patronId={user.id}
                                        locations={this.state.locations}
                                        showAlert={this.showAlert}
                                        itemDetails={this.state.itemDetails}
                                        groupedWorkId={this.state.groupedWorkId}
                                        groupedWorkTitle={this.state.groupedWorkTitle}
                                        groupedWorkAuthor={this.state.data.author}
                                        groupedWorkISBN={this.state.data.isbn}
                                        user={user}
                                        library={library}
                                        linkedAccounts={this.state.linkedAccounts}
                                        discoveryVersion={discoveryVersion}
                                        updateProfile={this.updateProfile}
                                        openHolds={this.openHolds}
                                        openCheckouts={this.openCheckouts}
                                        userLanguage="en"
                                   />
                              ) : null}

                              <AddToList itemId={this.state.groupedWorkId} btnStyle="lg" />

                              <Text
                                   mt={5}
                                   mb={5}
                                   fontSize={{
                                        base: 'md',
                                        lg: 'lg',
                                   }}
                                   lineHeight={{
                                        base: '22px',
                                        lg: '26px',
                                   }}>
                                   {this.state.data.description}
                              </Text>
                         </Box>
                         <Center>
                              <AlertDialog leastDestructiveRef={this.cancelRef} isOpen={this.state.alert}>
                                   <AlertDialog.Content>
                                        <AlertDialog.Header fontSize="lg" fontWeight="bold">
                                             {this.state.alertTitle}
                                        </AlertDialog.Header>
                                        <AlertDialog.Body>{this.state.alertMessage}</AlertDialog.Body>
                                        <AlertDialog.Footer>
                                             {this.state.alertAction ? (
                                                  <Button
                                                       onPress={() => {
                                                            this.setState({ alert: false });
                                                            this.props.navigation.navigate('AccountScreenTab', {
                                                                 screen: this.state.alertNavigateTo,
                                                                 params: { libraryUrl: library.baseUrl },
                                                            });
                                                       }}>
                                                       {this.state.alertAction}
                                                  </Button>
                                             ) : null}
                                             <Button onPress={this.hideAlert} ml={3} variant="outline" colorScheme="primary">
                                                  {getTermFromDictionary(this.state.userLanguage, 'button_ok')}
                                             </Button>
                                        </AlertDialog.Footer>
                                   </AlertDialog.Content>
                              </AlertDialog>
                         </Center>
                         <GetOverDriveSettings
                              promptTitle={this.state.promptTitle}
                              promptItemId={this.state.promptItemId}
                              promptSource={this.state.promptSource}
                              promptPatronId={this.state.promptPatronId}
                              overdriveEmail={this.state.overdriveEmail}
                              promptForOverdriveEmail={this.state.promptForOverdriveEmail}
                              showAlert={this.showAlert}
                              setEmail={this.setEmail}
                              setRememberPrompt={this.setRememberPrompt}
                              showOverDriveSettings={this.state.showOverDriveSettings}
                              handleOverDriveSettings={this.handleOverDriveSettings}
                              libraryUrl={library.baseUrl}
                              language="en"
                         />
                    </ScrollView>
               </SafeAreaView>
          );
     }
}