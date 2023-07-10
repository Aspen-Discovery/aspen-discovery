import React from 'react';
import { StyleSheet } from 'react-native';
import { Box, Button, Text, View } from 'native-base';
import { BarCodeScanner } from 'expo-barcode-scanner';

export default function Scanner() {
     const [hasPermission, setHasPermission] = React.useState(null);
     const [scanned, setScanned] = React.useState(false);

     React.useEffect(() => {
          (async () => {
               const { status } = await BarCodeScanner.requestPermissionsAsync();
               setHasPermission(status === 'granted');
          })();
     }, []);

     const handleBarCodeScanned = ({ type, data }) => {
          setScanned(true);
          console.log(`Barcode with type ${type} and data ${data} has been scanned!`);
     };

     if (hasPermission === null) {
          return <Text>Requesting for camera permissions</Text>;
     }

     if (hasPermission === false) {
          return <Text>No access to camera</Text>;
     }

     return (
          <View style={styles.container}>
               <BarCodeScanner onBarCodeScanned={scanned ? undefined : handleBarCodeScanned} style={StyleSheet.absoluteFillObject} />
               {scanned && <Button onPress={() => setScanned(false)}>Scan again</Button>}
          </View>
     );
}

const styles = StyleSheet.create({
     container: {
          flex: 1,
          flexDirection: 'column',
          justifyContent: 'center',
     },
});