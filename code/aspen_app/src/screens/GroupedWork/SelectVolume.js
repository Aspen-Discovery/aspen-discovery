import { FormControl, Select, CheckIcon, Radio } from 'native-base';
import React from 'react';
import { useQuery } from '@tanstack/react-query';
import { Platform } from 'react-native';
import { LibrarySystemContext } from '../../context/initialContext';
import { getVolumes } from '../../util/api/item';
import { loadingSpinner } from '../../components/loadingSpinner';
import { loadError } from '../../components/loadError';
import _ from 'lodash';
import { getTermFromDictionary } from '../../translations/TranslationService';

export const SelectVolume = (props) => {
     const { language, id, holdType, setHoldType, volume, setVolume, shouldLoad, promptForHoldType } = props;
     const { library } = React.useContext(LibrarySystemContext);

     const { status, data, error, isFetching } = useQuery({
          queryKey: ['volumes', id, library.baseUrl],
          queryFn: () => getVolumes(id, library.baseUrl),
          enabled: !!shouldLoad,
     });

     return (
          <>
               {status === 'loading' || isFetching ? (
                    loadingSpinner()
               ) : status === 'error' ? (
                    loadError('Error', '')
               ) : (
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
                                             {getTermFromDictionary(language, 'first_available')}
                                        </Radio>
                                        <Radio value="volume" my={1} size="sm">
                                             {getTermFromDictionary(language, 'specific_volume')}
                                        </Radio>
                                   </Radio.Group>
                              </FormControl>
                         ) : null}
                         {holdType === 'volume' ? (
                              <FormControl>
                                   <FormControl.Label>{getTermFromDictionary(language, 'select_volume')}</FormControl.Label>
                                   <Select
                                        isReadOnly={Platform.OS === 'android'}
                                        name="volumeForHold"
                                        selectedValue={volume}
                                        minWidth="200"
                                        accessibilityLabel={getTermFromDictionary(language, 'select_volume')}
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