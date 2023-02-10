import React from 'react';
import { Button } from 'native-base';

// custom components and helper files
import {openSideLoad} from '../../util/recordActions';

export const OpenSideLoad = (props) => {
	const [loading, setLoading] = React.useState(false);

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
			isLoading={loading}
			isLoadingText="Opening..."
			onPress={async () => {
				setLoading(true);
				await openSideLoad(props.url).then((r) => setLoading(false));
			}}>
			{props.title}
		</Button>
	);
};