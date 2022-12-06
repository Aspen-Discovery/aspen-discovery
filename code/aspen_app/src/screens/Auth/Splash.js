import { Box, Image, Center } from 'native-base';
import Constants from 'expo-constants';
import { View } from 'react-native';

export const SplashScreen = () => {
     const splashImage = Constants.manifest2?.extra?.expoClient?.extra?.loginLogo;
     const splashBackgroundColor = Constants.manifest2?.extra?.expoClient?.splash?.backgroundColor ?? '#ffffff';
     return (
          <Box
               style={{
                    flex: 1,
                    alignItems: 'center',
                    justifyContent: 'center',
                    backgroundColor: splashBackgroundColor,
               }}>
               <Image resizeMode="contain" source={{ uri: splashImage }} fallbackSource={require('../../themes/default/aspenLogo.png')} />
          </Box>
     );
};