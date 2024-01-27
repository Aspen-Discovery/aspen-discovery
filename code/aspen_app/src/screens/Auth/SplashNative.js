import Constants from 'expo-constants';
import { Image, View } from 'react-native';
import { getTermFromDictionary } from '../../translations/TranslationService';

const splashImage = Constants.expoConfig.extra.loginLogo;
const splashBackgroundColor = Constants.expoConfig.splash.backgroundColor;

export const SplashScreenNative = () => {
     return (
          <View style={{ flex: 1, alignItems: 'center', justifyContent: 'center', backgroundColor: splashBackgroundColor }}>
               <Image source={{ uri: splashImage }} size={200} alt={getTermFromDictionary('en', 'app_name')} fallbackSource={require('../../themes/default/aspenLogo.png')} />
          </View>
     );
};