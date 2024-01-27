import Constants from 'expo-constants';
import { Center, Image, Spinner, VStack } from 'native-base';
import { getTermFromDictionary } from '../../translations/TranslationService';

const splashImage = Constants.expoConfig.extra.loginLogo;
const splashBackgroundColor = Constants.expoConfig.splash.backgroundColor;

export const SplashScreen = () => {
     return (
          <Center flex={1} px="3" bgColor={splashBackgroundColor}>
               <VStack space={2} alignItems="center">
                    <Image source={{ uri: splashImage }} size={200} alt={getTermFromDictionary('en', 'app_name')} fallbackSource={require('../../themes/default/aspenLogo.png')} />
                    <Spinner size="sm" accessibilityLabel="Loading..." />
               </VStack>
          </Center>
     );
};