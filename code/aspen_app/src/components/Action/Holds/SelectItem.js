import { FormControl, Select, CheckIcon, Radio } from 'native-base';
import React from 'react';
import { Platform } from 'react-native';
import _ from 'lodash';
import { getTermFromDictionary } from '../../../translations/TranslationService';

export const SelectItem = (props) => {
     const { id, data, item, setItem, holdType, setHoldType, showModal, holdTypeForFormat, language, url } = props;

     let copies = data.copies;
     let copyKeys = Object.keys(copies);
     let key = copyKeys[0];
     let defaultItem = copies[key].id;

     if (item) {
          defaultItem = item;
     }

     /*if (defaultItem && !item) {
          setItem(defaultItem);
     }*/

     return (
          <>
               {holdTypeForFormat === 'either' ? (
                    <FormControl>
                         <Radio.Group
                              name="holdTypeGroup"
                              value={holdType}
                              onChange={(nextValue) => {
                                   setHoldType(nextValue);
                                   setItem('');
                              }}
                              accessibilityLabel="">
                              <Radio value="default" my={1} size="sm">
                                   {getTermFromDictionary(language, 'first_available')}
                              </Radio>
                              <Radio value="item" my={1} size="sm">
                                   {getTermFromDictionary(language, 'specific_item')}
                              </Radio>
                         </Radio.Group>
                    </FormControl>
               ) : null}
               {holdTypeForFormat === 'item' || holdType === 'item' ? (
                    <FormControl>
                         <FormControl.Label>{getTermFromDictionary(language, 'select_item')}</FormControl.Label>
                         <Select
                              isReadOnly={Platform.OS === 'android'}
                              name="itemForHold"
                              selectedValue={defaultItem}
                              minWidth="200"
                              defaultValue={defaultItem}
                              accessibilityLabel={getTermFromDictionary(language, 'select_item')}
                              _selectedItem={{
                                   bg: 'tertiary.300',
                                   endIcon: <CheckIcon size="5" />,
                              }}
                              mt={1}
                              mb={2}
                              onValueChange={(itemValue) => setItem(itemValue)}>
                              {_.map(Object.keys(copies), function (item, index, array) {
                                   let copy = copies[item];
                                   console.log(copy);
                                   return <Select.Item label={copy.location} value={copy.id} key={copy.id} />;
                              })}
                         </Select>
                    </FormControl>
               ) : null}
          </>
     );
};