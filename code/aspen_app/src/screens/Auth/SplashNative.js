import Constants from 'expo-constants';
import { translate } from '../../translations/translations';
import {Image, View} from 'react-native';
import {getTermFromDictionary} from '../../translations/TranslationService';

const splashImage = Constants.manifest2?.extra?.expoClient?.extra?.loginLogo ?? Constants.manifest.extra.loginLogo;
const splashBackgroundColor = Constants.manifest2?.extra?.expoClient?.splash?.backgroundColor ?? Constants.manifest.extra.backgroundColor;

export const SplashScreenNative = () => {
	return (
		<View style={{ flex: 1, alignItems: 'center', justifyContent: 'center', backgroundColor: splashBackgroundColor }}>
			<Image source={{ uri: splashImage }} size={200} alt={getTermFromDictionary('en', 'app_name')} fallbackSource={require('../../themes/default/aspenLogo.png')} />
		</View>
	);
}