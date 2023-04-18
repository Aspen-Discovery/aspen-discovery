import {Button} from 'native-base';
import React from 'react';
import {navigate} from '../../../helpers/RootNavigator';

export const StartVDXRequest = (props) => {
	const openVDXRequest = () => {
		navigate('CreateVDXRequest', {
			id: props.id,
			workTitle: props.workTitle,
			author: props.author,
			publisher: props.publisher,
			isbn: props.isbn,
			oclcNumber: props.oclcNumber
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