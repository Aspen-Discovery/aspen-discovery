import {Button} from 'native-base';
import React from 'react';
import {navigate} from '../../../helpers/RootNavigator';

export const StartVDXRequest = (props) => {
	console.log(props);
	const openVDXRequest = () => {
		navigate('CreateVDXRequest', {
			id: props.record
		});
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
			onPress={openVDXRequest}>
			{props.title}
		</Button>
	);
};