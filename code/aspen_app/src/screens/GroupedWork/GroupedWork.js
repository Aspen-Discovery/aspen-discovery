import { MaterialIcons } from '@expo/vector-icons';
import AsyncStorage from '@react-native-async-storage/async-storage';
import _ from 'lodash';
import { AlertDialog, Box, Button, Center, Icon, Image, ScrollView, Text } from 'native-base';
import React, { Component, useEffect } from 'react';
import { SafeAreaView } from 'react-native';
import { Rating } from 'react-native-elements';

// custom components and helper files
import { loadError } from '../../components/loadError';
import { loadingSpinner } from '../../components/loadingSpinner';
import { userContext } from '../../context/user';
import { translate } from '../../translations/translations';
import { getLinkedAccounts, getProfile, PATRON } from '../../util/loadPatron';
import { getGroupedWork, getItemDetails } from '../../util/recordActions';
import Manifestation from './Manifestation';
import { GetOverDriveSettings } from './OverDriveSettings';
import AddToList from '../Search/AddToList';

export default class GroupedWork extends Component {
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
          };
          this.locations = [];
          this._isMounted = false;
     }

     authorSearch = (author, libraryUrl) => {
          const { navigation } = this.props;
          navigation.navigate('SearchTab', {
               screen: 'SearchResults',
               params: {
                    term: author,
                    libraryUrl: libraryUrl,
               },
          });
     };

     openCheckouts = () => {
          this.props.navigation.navigate('CheckedOut');
     };

     openHolds = () => {
          this.props.navigation.navigate('Holds');
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

          await getGroupedWork(libraryUrl, givenItem).then((response) => {
               if (response === 'TIMEOUT_ERROR') {
                    this.setState({
                         hasError: true,
                         error: translate('error.timeout'),
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
                              error: translate('error.no_data'),
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
                    promptTitle: translate('holds.hold_options'),
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
          /* await getProfile().then((response) => {
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
                                   {translate('grouped_work.format')}
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
                                   {translate('grouped_work.language')}
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
                                                  {translate('general.button_ok')}
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
                         />
                    </ScrollView>
               </SafeAreaView>
          );
     }
}