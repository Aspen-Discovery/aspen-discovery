import React from 'react';
import { Button } from 'native-base';

// custom components and helper files
import {LibrarySystemContext, UserContext} from '../../context/initialContext';
import {completeAction} from '../../screens/GroupedWork/Record';

export const LoadOverDriveSample = (props) => {
	const { user } = React.useContext(UserContext);
	const { library } = React.useContext(LibrarySystemContext);
	const [loading, setLoading] = React.useState(false);

	return (
		<Button
			size="xs"
			colorScheme="primary"
			variant="outline"
			_text={{
				padding: 0,
				textAlign: 'center',
				fontSize: 12,
			}}
			style={{
				flex: 1,
				flexWrap: 'wrap',
			}}
			isLoading={loading}
			isLoadingText="Opening..."
			onPress={() => {
				setLoading(true);
				completeAction(props.id, props.type, user.id, props.formatId, props.sampleNumber, null, library.baseUrl, null, null).then((r) => {
					setLoading(false);
				});
			}}>
			{props.title}
		</Button>
	);
};