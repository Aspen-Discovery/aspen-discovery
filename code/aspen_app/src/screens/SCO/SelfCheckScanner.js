import React from 'react';
import { StyleSheet } from 'react-native';
import { Box, Button, View } from 'native-base';
import { BarCodeScanner } from 'expo-barcode-scanner';
import { loadingSpinner } from '../../components/loadingSpinner';
import { loadError } from '../../components/loadError';
import { navigate, navigateStack } from '../../helpers/RootNavigator';
import BarcodeMask from 'react-native-barcode-mask';
import { useNavigation, useRoute } from '@react-navigation/native';

export default function SelfCheckScanner() {
     //const navigation = useNavigation();
     //const allowCode39 = useRoute().params?.allowCode39 ?? false;
     const [hasPermission, setHasPermission] = React.useState(null);
     const [scanned, setScanned] = React.useState(false);
     let allowedBarcodes = [BarCodeScanner.Constants.BarCodeType.code128, BarCodeScanner.Constants.BarCodeType.codabar, BarCodeScanner.Constants.BarCodeType.ean13, BarCodeScanner.Constants.BarCodeType.ean8, BarCodeScanner.Constants.BarCodeType.itf14];

     React.useEffect(() => {
          (async () => {
               const { status } = await BarCodeScanner.requestPermissionsAsync();
               setHasPermission(status === 'granted');
          })();
     }, []);

     const handleBarCodeScanned = ({ type, data }) => {
          if (!scanned) {
               // do the checkout
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