import React from 'react';
import { StyleSheet } from 'react-native';
import { Box, Button, View } from 'native-base';
import { BarCodeScanner } from 'expo-barcode-scanner';
import { loadingSpinner } from '../../components/loadingSpinner';
import { loadError } from '../../components/loadError';
import { navigate, navigateStack } from '../../helpers/RootNavigator';
import BarcodeMask from 'react-native-barcode-mask';
import { CommonActions, useNavigation, useRoute } from '@react-navigation/native';
import { LibrarySystemContext } from '../../context/initialContext';
import { checkoutItem } from '../../util/recordActions';

export default function SelfCheckScanner() {
     //const navigation = useNavigation();
     const { library } = React.useContext(LibrarySystemContext);
     const [hasPermission, setHasPermission] = React.useState(null);
     const [scanned, setScanned] = React.useState(false);
     let allowedBarcodes = [BarCodeScanner.Constants.BarCodeType.upc_a, BarCodeScanner.Constants.BarCodeType.upc_e, BarCodeScanner.Constants.BarCodeType.upc_ean, BarCodeScanner.Constants.BarCodeType.ean13, BarCodeScanner.Constants.BarCodeType.ean8];

     React.useEffect(() => {
          (async () => {
               const { status } = await BarCodeScanner.requestPermissionsAsync();
               setHasPermission(status === 'granted');
          })();
     }, []);

     const handleBarCodeScanned = async ({ type, data }) => {
          if (!scanned) {
               setScanned(true);

               // do the checkout
               await checkoutItem(library.baseUrl, data, 'ils', 'x', data, 'location').then((result) => {
                    let hasError = false;
                    let errorMessageBody = null;
                    let errorMessageTitle = null;
                    if (!result.success) {
                         hasError = true;
                         errorMessageBody = result.message ?? 'Unknown error while trying to checkout title';
                         errorMessageTitle = result.title ?? 'Unable to checkout title';
                    }
                    navigate('SelfCheckout', {
                         checkoutResult: result.itemData,
                         checkoutHasError: hasError,
                         checkoutErrorMessageBody: errorMessageBody,
                         checkoutErrorMessageTitle: errorMessageTitle,
                    });
               });
          }
     };

     if (hasPermission === null) {
          return loadingSpinner('Requesting for camera permissions');
     }

     if (hasPermission === false) {
          return loadError('No access to camera');
     }

     return (
          <View style={{ flex: 1 }}>
               <BarCodeScanner onBarCodeScanned={scanned ? undefined : handleBarCodeScanned} style={[StyleSheet.absoluteFillObject, styles.container]} barCodeTypes={allowedBarcodes}>
                    <BarcodeMask edgeColor="#62B1F6" showAnimatedLine={false} />
                    {scanned && <Button onPress={() => setScanned(false)}>Scan Different Item</Button>}
               </BarCodeScanner>
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