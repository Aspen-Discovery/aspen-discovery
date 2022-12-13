import { VStack, Image, Center, Spinner } from 'native-base';
import Constants from 'expo-constants';
import { translate } from '../../translations/translations';

const splashImage = Constants.manifest2?.extra?.expoClient?.extra?.loginLogo ?? Constants.manifest.extra.loginLogo;
const splashBackgroundColor = Constants.manifest2?.extra?.expoClient?.splash?.backgroundColor ?? Constants.manifest.extra.backgroundColor;

export const SplashScreen = () => {
     return (
          <Center flex={1} px="3" bgColor={splashBackgroundColor}>
               <VStack space={2} alignItems="center">
                    <Image source={{ uri: splashImage }} size={200} alt={translate('app.name')} fallbackSource={require('../../themes/default/aspenLogo.png')} />
                    <Spinner size="sm" accessibilityLabel="Loading..." />
               </VStack>
          </Center>
     );
};