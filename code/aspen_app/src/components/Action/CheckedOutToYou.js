import { Button } from 'native-base';

// custom components and helper files
import {navigate, navigateStack} from '../../helpers/RootNavigator';

export const CheckedOutToYou = (props) => {
	const handleNavigation = () => {
		if (props.prevRoute === 'DiscoveryScreen' || props.prevRoute === 'SearchResults') {
			navigateStack('AccountScreenTab', 'MyCheckouts', {});
		} else {
			navigate('MyCheckouts', {});
		}
	};

	return (
		<Button
			size="md"
			colorScheme="primary"
			variant="solid"
			_text={{
				padding: 0,
				textAlign: 'center',
			}}
			style={{
				flex: 1,
				flexWrap: 'wrap',
			}}
			onPress={handleNavigation}>
			{props.title}
		</Button>
	);
};