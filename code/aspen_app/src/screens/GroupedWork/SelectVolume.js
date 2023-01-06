import { FormControl, Select, CheckIcon, Radio } from 'native-base';
import React from 'react';
import { useQuery } from '@tanstack/react-query';
import { translate } from '../../translations/translations';
import {LibrarySystemContext} from '../../context/initialContext';
import {getVolumes} from '../../util/api/item';
import {loadingSpinner} from '../../components/loadingSpinner';
import {loadError} from '../../components/loadError';
import _ from 'lodash';

export const SelectVolume = (props) => {
	const { id, holdType, setHoldType, volume, setVolume, shouldLoad, promptForHoldType } = props;
	const { library } = React.useContext(LibrarySystemContext);

	const { status, data, error, isFetching } = useQuery({
		queryKey: ['volumes', id, library.baseUrl],
		queryFn: () => getVolumes(id, library.baseUrl),
		enabled: !!shouldLoad,
	});

	return (
		<>
			{status === 'loading' || isFetching ? loadingSpinner() : status === 'error' ? loadError('Error', '') : (
				<>
					{promptForHoldType ? (
					<FormControl>
						<Radio.Group
							name="holdTypeGroup"
							defaultValue={holdType}
							value={holdType}
							onChange={(nextValue) => {
								setHoldType(nextValue);
							}}
							accessibilityLabel="">
							<Radio value="item" my={1} size="sm">
								{translate('grouped_work.first_available')}
							</Radio>
							<Radio value="volume" my={1} size="sm">
								{translate('grouped_work.specific_volume')}
							</Radio>
						</Radio.Group>
					</FormControl>
					) : null}
					{holdType === 'volume' ? (
						<FormControl>
							<FormControl.Label>{translate('grouped_work.select_volume')}</FormControl.Label>
							<Select
								name="volumeForHold"
								selectedValue={volume}
								minWidth="200"
								accessibilityLabel="Select a Volume"
								_selectedItem={{
									bg: 'tertiary.300',
									endIcon: <CheckIcon size="5" />,
								}}
								mt={1}
								mb={2}
								onValueChange={(itemValue) => setVolume(itemValue)}>
								{_.map(data, function (item, index, array) {
									return <Select.Item label={item.label} value={item.volumeId} key={index} />;
								})}
							</Select>
						</FormControl>
					) : null}
				</>
			)}
		</>
	);
};