import { useIsFocused, useRoute } from '@react-navigation/native';
import { BarCodeScanner } from 'expo-barcode-scanner';
import _ from 'lodash';
import { Button, Center, View } from 'native-base';
import React from 'react';
import { StyleSheet } from 'react-native';
import BarcodeMask from 'react-native-barcode-mask';
import { loadError } from '../../components/loadError';
import { loadingSpinner } from '../../components/loadingSpinner';
import { LanguageContext, LibraryBranchContext, LibrarySystemContext } from '../../context/initialContext';
import { navigate } from '../../helpers/RootNavigator';
import { getTermFromDictionary } from '../../translations/TranslationService';

export default function SelfCheckScanner() {
     //const navigation = useNavigation();
     const isFocused = useIsFocused();
     const [isLoading, setIsLoading] = React.useState(false);
     const { language } = React.useContext(LanguageContext);
     const { library } = React.useContext(LibrarySystemContext);
     const { selfCheckSettings } = React.useContext(LibraryBranchContext);
     const [hasPermission, setHasPermission] = React.useState(null);
     const [scanned, setScanned] = React.useState(false);

     let allowedBarcodes = [BarCodeScanner.Constants.BarCodeType.upc_a, BarCodeScanner.Constants.BarCodeType.upc_e, BarCodeScanner.Constants.BarCodeType.upc_ean, BarCodeScanner.Constants.BarCodeType.ean13, BarCodeScanner.Constants.BarCodeType.ean8, BarCodeScanner.Constants.BarCodeType.codabar];
     if (selfCheckSettings.barcodeStyles && _.isArray(selfCheckSettings.barcodeStyles)) {
          const barcodeStyles = selfCheckSettings.barcodeStyles;
          allowedBarcodes = [];
          _.map(barcodeStyles, function (item, index, collection) {
               allowedBarcodes = _.concat(barcodeStyles, 'BarCodeScanner.Constants.BarCodeType.' + item);
          });
     }

     let activeAccount = useRoute().params?.activeAccount ?? false;

     const testBarcodes = ['9031105', '9031106', '9031107'];

     React.useEffect(() => {
          (async () => {
               const { status } = await BarCodeScanner.requestPermissionsAsync();
               setHasPermission(status === 'granted');
               /* for testing on simulators, assign a random barcode from array since camera does not work */
               /*if (!Device.isDevice) {
			 setScanned(true);
			 navigate('SelfCheckOut', {
			 barcode: _.sample(_.shuffle(testBarcodes)),
			 type: '',
			 activeAccount,
			 startNew: false,
			 });
			 }*/
          })();
     }, []);

     const handleBarCodeScanned = async ({ type, data }) => {
          setIsLoading(true);
          if (!scanned) {
               if (type === '8' || type === 8 || type === '64' || type === 64 || type === 'org.gs1.EAN-8') {
                    data = cleanBarcode(data, type);
               }
               setScanned(true);
               navigate('SelfCheckOut', {
                    barcode: data,
                    type: type,
                    activeAccount,
                    startNew: false,
               });
               setIsLoading(false);
          } else {
               setIsLoading(false);
          }
     };

     if (hasPermission === null) {
          return loadingSpinner('Requesting for camera permissions');
     }

     if (hasPermission === false) {
          return loadError('No access to camera');
     }

     if (isLoading) {
          return loadingSpinner();
     }

     return (
          <View style={{ flex: 1, flexDirection: 'column', justifyContent: 'flex-end' }}>
               {isFocused && (
                    <>
                         <BarCodeScanner onBarCodeScanned={scanned ? undefined : handleBarCodeScanned} style={[StyleSheet.absoluteFillObject, styles.container]} barCodeTypes={allowedBarcodes}>
                              <BarcodeMask edgeColor="#62B1F6" showAnimatedLine={false} />
                         </BarCodeScanner>
                         {scanned && (
                              <Center pb={20}>
                                   <Button onPress={() => setScanned(false)}>{getTermFromDictionary(language, 'scan_again')}</Button>
                              </Center>
                         )}
                    </>
               )}
          </View>
     );
}

const styles = StyleSheet.create({
     container: {
          flex: 1,
          alignItems: 'center',
          justifyContent: 'center',
     },
});

function cleanBarcode(barcode, type) {
     barcode = barcode.toUpperCase();
     if (type === '8' || type === 8) {
          let firstValue = barcode.charAt(0);
          if (firstValue === 'A' || firstValue === 'B' || firstValue === 'C' || firstValue === 'D') {
               barcode = barcode.substring(1);
          }

          let lastValue = barcode.charAt(barcode.length - 1);
          if (lastValue === 'A' || lastValue === 'B' || lastValue === 'C' || lastValue === 'D') {
               barcode = barcode.substring(0, barcode.length - 1);
          }
     }

     if (type === '64' || type === 64 || type === 'org.gs1.EAN-8') {
          barcode = barcode.substring(0, barcode.length - 1);
     }

     return barcode;
}