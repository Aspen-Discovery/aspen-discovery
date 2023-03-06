import { Button } from 'native-base';
import React from 'react';
import _ from 'lodash';

// custom components and helper files
import {LibraryBranchContext, LibrarySystemContext, UserContext} from '../../../context/initialContext';
import {completeAction} from '../../../screens/GroupedWork/Record';
import {refreshProfile} from '../../../util/api/user';
import {HoldPrompt} from '../Holds/HoldPrompt';

export const CheckOut = (props) => {
	const { id, title, type, record, prevRoute, response, setResponse, responseIsOpen, setResponseIsOpen, onResponseClose, cancelResponseRef } = props;
	const { user, updateUser, accounts } = React.useContext(UserContext);
	const { library } = React.useContext(LibrarySystemContext);
	const [loading, setLoading] = React.useState(false);

	const volumeInfo = {
		numItemsWithVolumes: 0,
		numItemsWithoutVolumes: 1,
		hasItemsWithoutVolumes: true,
		majorityOfItemsHaveVolumes: false,
	}

	if(_.size(accounts) > 0) {
		return <HoldPrompt id={record} title={title} action={type} volumeInfo={volumeInfo} prevRoute={prevRoute} isEContent={true} setResponseIsOpen={setResponseIsOpen} responseIsOpen={responseIsOpen} onResponseClose={onResponseClose} cancelResponseRef={cancelResponseRef} response={response} setResponse={setResponse}/>
	} else {
		return (
			<>
				<Button
					size="md"
					colorScheme="primary"
					variant="solid"
					_text={{
						padding: 0,
						textAlign: 'center',
					}}
					isLoading={loading}
					isLoadingText="Checking out..."
					style={{
						flex: 1,
						flexWrap: 'wrap',
					}}
					onPress={async () => {
						setLoading(true);
						await completeAction(record, type, user.id, null, null, null, library.baseUrl).then(async (eContentResponse) => {
							setResponse(eContentResponse);
							if (eContentResponse.success) {
								await refreshProfile(library.baseUrl).then((result) => {
									updateUser(result);
								});
							}
							setLoading(false);
							setResponseIsOpen(true);
						});
					}}>
					{title}
				</Button>
			</>
		);
	}
};