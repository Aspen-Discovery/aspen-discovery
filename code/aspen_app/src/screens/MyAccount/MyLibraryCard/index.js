import _ from 'lodash';
import moment from 'moment';
import { Center, Flex, Image, Text } from 'native-base';
import React, { Component } from 'react';
import Barcode from 'react-native-barcode-expo';

// custom components and helper files
import { loadError } from '../../../components/loadError';
import { loadingSpinner } from '../../../components/loadingSpinner';
import { userContext } from '../../../context/user';
import { translate } from '../../../translations/translations';
import { UserContext } from '../../../context/initialContext';

export default class LibraryCard extends Component {
     static contextType = userContext;

     constructor(props) {
          super(props);
          this.state = {
               isLoading: true,
               hasError: false,
               error: null,
               barcodeStyleInvalid: false,
               library: [],
               location: [],
          };
     }

     // store the values into the state
     componentDidMount = async () => {
          const libraryContext = JSON.parse(this.props.route.params.libraryContext);
          this.setState({
               isLoading: false,
               library: libraryContext.library,
          });
     };

     invalidFormat = () => {
          this.setState({
               barcodeStyleInvalid: true,
          });
     };

     render() {
          const user = this.context.user;
          const library = this.state.library;

          const barcodeStyle = _.toString(library.barcodeStyle);

          let doesNotExpire = false;
          if (!_.isUndefined(user.expired)) {
               if (user.expired === 0) {
                    const now = moment().format('MMM D, YYYY');
                    const expirationDate = new Date(user.expires);
                    const isExpired = moment(expirationDate).isBefore(now);
                    if (isExpired) {
                         doesNotExpire = true;
                    }
               }
          }

          let icon = library.favicon;
          if (library.logoApp) {
               icon = library.logoApp;
          }

          let barcodeValue = 'UNKNOWN';
          if (user.cat_username) {
               barcodeValue = user.cat_username;
          }

          if (this.state.isLoading || user.cat_username === '') {
               return loadingSpinner();
          }

          if (this.state.hasError) {
               return loadError(this.state.error);
          }

          if (_.isNull(barcodeStyle) || this.state.barcodeStyleInvalid) {
               return (
                    <Center flex={1} px={3}>
                         <Flex direction="column" bg="white" maxW="90%" px={8} py={5} borderRadius={20}>
                              <Center>
                                   <Flex direction="row">
                                        <Image source={{ uri: icon }} fallbackSource={require('../../../themes/default/aspenLogo.png')} w={42} h={42} alt={translate('user_profile.library_card')} />
                                        <Text bold ml={3} mt={2} fontSize="lg" color="darkText">
                                             {library.displayName}
                                        </Text>
                                   </Flex>
                              </Center>
                              <Center pt={8}>
                                   <Text pb={2} color="darkText">
                                        {user.displayName}
                                   </Text>
                                   <Text color="darkText" bold fontSize="xl">
                                        {user.cat_username}
                                   </Text>
                                   {user.expires && !doesNotExpire ? (
                                        <Text color="darkText" fontSize={10}>
                                             Expires on {user.expires}
                                        </Text>
                                   ) : null}
                              </Center>
                         </Flex>
                    </Center>
               );
          }

          return (
               <Center flex={1} px={3}>
                    <Flex direction="column" bg="white" maxW="95%" px={8} py={5} borderRadius={20}>
                         <Center>
                              <Flex direction="row">
                                   <Image source={{ uri: icon }} fallbackSource={require('../../../themes/default/aspenLogo.png')} w={42} h={42} alt={translate('user_profile.library_card')} />
                                   <Text bold ml={3} mt={2} fontSize="lg" color="darkText">
                                        {library.displayName}
                                   </Text>
                              </Flex>
                         </Center>
                         <Center pt={8}>
                              <Barcode value={barcodeValue} format={barcodeStyle} text={barcodeValue} background="warmGray.100" onError={() => this.invalidFormat()} />
                              {user.expires && !doesNotExpire ? (
                                   <Text color="darkText" fontSize={10} pt={2}>
                                        Expires on {user.expires}
                                   </Text>
                              ) : null}
                         </Center>
                    </Flex>
               </Center>
          );
     }
}
LibraryCard.contextType = UserContext;
/*
 export const LibraryCardScreen = () => {
 const navigation = useNavigation();
 const [loading, setLoading] = React.useState(false);
 const { user } = React.useContext(UserContext);
 const { library } = React.useContext(LibrarySystemContext);
 const { location } = React.useContext(LibraryBranchContext);
 const [hasBarcode, setBarcode] = React.useState(false);
 const [style, setBarcodeStyle] = React.useState();
 const [neverExpire, setCanExpire] = React.useState(false);
 const [value, setBarcodeValue] = React.useState();
 const [date, setExpiration] = React.useState();
 const [icon, setCardIcon] = React.useState();

 const setupCard = () => {
 let barcodeStyle;
 if (!_.isUndefined(library.barcodeStyle)) {
 barcodeStyle = _.toString(library.barcodeStyle);
 }
 setBarcodeStyle(barcodeStyle);

 let barcodeValue = 'UNKNOWN';
 if (!_.isUndefined(user.cat_username)) {
 barcodeValue = user.cat_username;
 }
 setBarcodeValue(barcodeValue);

 let expirationDate;
 if (!_.isUndefined(user.expires)) {
 expirationDate = new Date(user.expires);
 setExpiration(expirationDate);
 }

 let cardHasExpired = 0;
 if (!_.isUndefined(user.expired)) {
 cardHasExpired = user.expired;
 }

 let neverExpires = false;
 if (cardHasExpired === 0 && _.isDate(expirationDate)) {
 const now = moment().format('MMM D, YYYY');
 const hasExpired = moment(expirationDate).isBefore(now);
 if (hasExpired) {
 neverExpires = true;
 }
 }
 setCanExpire(neverExpires);

 let image = library.favicon;
 if (library.logoApp) {
 image = library.logoApp;
 }
 setCardIcon(image);
 };
 setupCard();

 const invalidBarcodeFormat = () => {
 setBarcode(false);
 };

 const showLibraryCardWithBarcode = () => {
 return (
 <Center flex={1} px={3}>
 <Flex direction="column" bg="white" maxW="95%" px={8} py={5} borderRadius={20}>
 <Center>
 <Flex direction="row">
 {icon ? <Image source={{ uri: icon }} fallbackSource={require('../../../themes/default/aspenLogo.png')} w={42} h={42} alt={translate('user_profile.library_card')} /> : null}
 <Text bold ml={3} mt={2} fontSize="lg" color="darkText">
 {library.displayName}
 </Text>
 </Flex>
 </Center>
 <Center pt={8}>
 <Barcode value={value} format={style} text={value} background="warmGray.100" onError={() => invalidBarcodeFormat()} />
 {date && !neverExpire ? (
 <Text color="darkText" fontSize={10} pt={2}>
 Expires on {date}
 </Text>
 ) : null}
 </Center>
 </Flex>
 </Center>
 );
 };

 const showLibraryCardWithoutBarcode = () => {
 return (
 <Center flex={1} px={3}>
 <Flex direction="column" bg="white" maxW="90%" px={8} py={5} borderRadius={20}>
 <Center>
 <Flex direction="row">
 {icon ? <Image source={{ uri: icon }} fallbackSource={require('../../../themes/default/aspenLogo.png')} w={42} h={42} alt={translate('user_profile.library_card')} /> : null}
 <Text bold ml={3} mt={2} fontSize="lg" color="darkText">
 {library.displayName}
 </Text>
 </Flex>
 </Center>
 <Center pt={8}>
 <Text pb={2} color="darkText">
 {user.displayName}
 </Text>
 <Text color="darkText" bold fontSize="xl">
 {value}
 </Text>
 {date && !neverExpire ? (
 <Text color="darkText" fontSize={10}>
 Expires on {date}
 </Text>
 ) : null}
 </Center>
 </Flex>
 </Center>
 );
 };

 if (!hasBarcode || _.isNull(style)) {
 showLibraryCardWithoutBarcode();
 }

 if (hasBarcode || !_.isNull(style)) {
 showLibraryCardWithBarcode();
 }
 };*/