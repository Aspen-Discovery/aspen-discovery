import { Icon, ChevronDownIcon, FormControl, SelectScrollView, FormControlLabel, FormControlLabelText, Select, SelectTrigger, SelectInput, SelectIcon, SelectPortal, SelectBackdrop, SelectContent, SelectDragIndicatorWrapper, SelectDragIndicator, SelectItem, CheckIcon, Radio, RadioGroup, RadioIndicator, RadioIcon, RadioLabel, CircleIcon } from '@gluestack-ui/themed';
import React from 'react';
import { Platform } from 'react-native';
import _ from 'lodash';
import { getTermFromDictionary } from '../../../translations/TranslationService';

export const SelectItemHold = (props) => {
     const { id, data, item, setItem, setHoldType, showModal, holdTypeForFormat, language, url, textColor, theme } = props;

     let holdType = props.holdType;
     let copies = data.copies;
     let copyKeys = Object.keys(copies);
     let key = copyKeys[0];
     let defaultItem = copies[key].id;

     if (holdType === 'either') {
          holdType = 'default';
     }

     if (item) {
          defaultItem = item;
     }

     return (
          <>
               {holdTypeForFormat === 'either' ? (
                    <FormControl>
                         <RadioGroup
                              name="holdTypeGroup"
                              value={holdType}
                              onChange={(nextValue) => {
                                   setHoldType(nextValue);
                                   setItem('');
                              }}
                              accessibilityLabel="">
                              <Radio value="default" my="$1" size="sm">
                                   <RadioIndicator mr="$1">
                                        <RadioIcon as={CircleIcon} strokeWidth={1} />
                                   </RadioIndicator>
                                   <RadioLabel color={textColor}>{getTermFromDictionary(language, 'first_available')}</RadioLabel>
                              </Radio>
                              <Radio value="item" my="$1" size="sm">
                                   <RadioIndicator mr="$1">
                                        <RadioIcon as={CircleIcon} strokeWidth={1} />
                                   </RadioIndicator>
                                   <RadioLabel color={textColor}>{getTermFromDictionary(language, 'specific_item')}</RadioLabel>
                              </Radio>
                         </RadioGroup>
                    </FormControl>
               ) : null}
               {holdTypeForFormat === 'item' || holdType === 'item' ? (
                    <FormControl>
                         <FormControlLabel>
                              <FormControlLabelText color={textColor}>{getTermFromDictionary(language, 'select_item')}</FormControlLabelText>
                         </FormControlLabel>
                         <Select name="itemForHold" selectedValue={defaultItem} minWidth={200} accessibilityLabel={getTermFromDictionary(language, 'select_item')} mt="$1" mb="$2" onValueChange={(itemValue) => setItem(itemValue)}>
                              <SelectTrigger variant="outline" size="md">
                                   {_.map(Object.keys(copies), function (item, index, array) {
                                        let copy = copies[item];
                                        if (copy.id === defaultItem) {
                                             setItem(defaultItem);
                                             return <SelectInput value={copy.location} color={textColor} />;
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
                                             {_.map(Object.keys(copies), function (item, index, array) {
                                                  let copy = copies[item];
                                                  if (copy.id === defaultItem) {
                                                       return <SelectItem label={copy.location} value={copy.id} key={copy.id} bgColor={theme['colors']['tertiary']['300']} />;
                                                  }
                                                  return <SelectItem label={copy.location} value={copy.id} key={copy.id} />;
                                             })}
                                        </SelectScrollView>
                                   </SelectContent>
                              </SelectPortal>
                         </Select>
                    </FormControl>
               ) : null}
          </>
     );
};