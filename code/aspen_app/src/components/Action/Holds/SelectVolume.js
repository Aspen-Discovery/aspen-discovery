import { Icon, ChevronDownIcon, FormControl, FormControlLabel, FormControlLabelText, SelectScrollView, Select, SelectTrigger, SelectInput, SelectIcon, SelectPortal, SelectBackdrop, SelectContent, SelectDragIndicatorWrapper, SelectDragIndicator, SelectItem, CheckIcon, Radio, RadioGroup, RadioIndicator, RadioIcon, RadioLabel, CircleIcon } from '@gluestack-ui/themed';
import React from 'react';
import { Platform } from 'react-native';
import { useQuery } from '@tanstack/react-query';
import { getVolumes } from '../../../util/api/item';
import { loadingSpinner } from '../../loadingSpinner';
import { loadError } from '../../loadError';
import _ from 'lodash';
import { getTermFromDictionary } from '../../../translations/TranslationService';

export const SelectVolume = (props) => {
     const { id, volume, setVolume, showModal, promptForHoldType, holdType, setHoldType, language, url, textColor, theme } = props;

     const { status, data, error, isFetching } = useQuery({
          queryKey: ['volumes', id, url],
          queryFn: () => getVolumes(id, url),
          enabled: !!showModal,
     });

     if (!isFetching && data && _.isEmpty(volume)) {
          let volumesKeys = Object.keys(data);
          let key = volumesKeys[0];
          setVolume(data[key].volumeId);
     }

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
                                   <RadioGroup
                                        name="holdTypeGroup"
                                        defaultValue={holdType}
                                        value={holdType}
                                        onChange={(nextValue) => {
                                             setHoldType(nextValue);
                                             setVolume('');
                                        }}>
                                        <Radio value="item" my="$1" size="sm">
                                             <RadioIndicator mr="$1">
                                                  <RadioIcon as={CircleIcon} strokeWidth={1} />
                                             </RadioIndicator>
                                             <RadioLabel color={textColor}>{getTermFromDictionary(language, 'first_available')}</RadioLabel>
                                        </Radio>
                                        <Radio value="volume" my="$1" size="sm">
                                             <RadioIndicator mr="$1">
                                                  <RadioIcon as={CircleIcon} strokeWidth={1} />
                                             </RadioIndicator>
                                             <RadioLabel color={textColor}>{getTermFromDictionary(language, 'specific_volume')}</RadioLabel>
                                        </Radio>
                                   </RadioGroup>
                              </FormControl>
                         ) : null}
                         {holdType === 'volume' ? (
                              <FormControl>
                                   <FormControlLabel>
                                        <FormControlLabelText color={textColor}>{getTermFromDictionary(language, 'select_volume')}</FormControlLabelText>
                                   </FormControlLabel>
                                   <Select
                                        name="volumeForHold"
                                        selectedValue={volume}
                                        defaultValue={volume}
                                        minWidth="200"
                                        accessibilityLabel={getTermFromDictionary(language, 'select_volume')}
                                        _selectedItem={{
                                             bg: 'tertiary.300',
                                             endIcon: <CheckIcon size="5" />,
                                        }}
                                        mt={1}
                                        mb={2}
                                        onValueChange={(itemValue) => setVolume(itemValue)}>
                                        <SelectTrigger variant="outline" size="md">
                                             {_.map(data, function (item, index, array) {
                                                  if (item.volumeId === volume) {
                                                       return <SelectInput value={item.label} color={textColor} />;
                                                  }
                                             })}
                                             <SelectIcon mr="$3">
                                                  <Icon as={ChevronDownIcon} color={textColor} />
                                             </SelectIcon>
                                        </SelectTrigger>
                                        <SelectPortal>
                                             <SelectBackdrop />
                                             <SelectContent p="$5">
                                                  <SelectDragIndicatorWrapper>
                                                       <SelectDragIndicator />
                                                  </SelectDragIndicatorWrapper>
                                                  <SelectScrollView>
                                                       {_.map(data, function (item, index, array) {
                                                            if (item.volumeId === volume) {
                                                                 return <SelectItem label={item.label} value={item.volumeId} key={index} bgColor={theme['colors']['tertiary']['300']} />;
                                                            }
                                                            return <SelectItem label={item.label} value={item.volumeId} key={index} />;
                                                       })}
                                                  </SelectScrollView>
                                             </SelectContent>
                                        </SelectPortal>
                                   </Select>
                              </FormControl>
                         ) : null}
                    </>
               )}
          </>
     );
};