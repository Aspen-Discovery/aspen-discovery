import { useFocusEffect } from '@react-navigation/native';
import _ from 'lodash';
import moment from 'moment/moment';
import { Box, Button, FormControl, HStack, Text, useColorModeValue, useToken } from 'native-base';
import React from 'react';
import { ScrollView } from 'react-native';
import DateTimePickerModal from 'react-native-modal-datetime-picker';
import { LanguageContext, LibrarySystemContext, UserContext } from '../../../context/initialContext';
import { getTermFromDictionary } from '../../../translations/TranslationService';
import { addAppliedFilter } from '../../../util/search';

// custom components and helper files

export const Facet_Date = (props) => {
     const { data, category, updater } = props;
     const { library } = React.useContext(LibrarySystemContext);
     const { user, updateUser } = React.useContext(UserContext);
     const { language } = React.useContext(LanguageContext);

     const [loading, setLoading] = React.useState(false);

     const textColor = useToken('colors', useColorModeValue('text.500', 'text.50'));
     const colorMode = useColorModeValue(false, true);

     const today = new Date();
     const [fromValue, setFrom] = React.useState(today);
     const [toValue, setTo] = React.useState(today);
     const [fromFacet, setFromFacet] = React.useState('*');
     const [toFacet, setToFacet] = React.useState('*');
     const [isFromDatePickerVisible, setFromDatePickerVisibility] = React.useState(false);
     const [isToDatePickerVisible, setToDatePickerVisibility] = React.useState(false);

     useFocusEffect(
          React.useCallback(() => {
               if (_.find(data, ['isApplied', true])) {
                    const appliedFilterObj = _.find(data, ['isApplied', true]);
                    let value = appliedFilterObj['value'];
                    value = _.trimStart(value, '[');
                    value = _.trimEnd(value, ']');
                    const arr = _.split(value, ' TO ');
                    if (arr[0] !== '*') {
                         const tmp = moment(arr[0]);
                         setFrom(tmp);
                         setFromFacet(tmp);
                    }

                    if (arr[1] !== '*') {
                         const tmp = moment(arr[1]);
                         setTo(tmp);
                         setToFacet(tmp);
                    }
               }
          }, [data])
     );

     const toggleFromDatePicker = () => {
          setFromDatePickerVisibility(!isFromDatePickerVisible);
     };

     const onSelectFromDate = (date) => {
          toggleFromDatePicker();
          setLoading(true);
          setFrom(date);
          let tmp = moment(date).format('YYYY-MM-DDTHH:mm:ss');
          tmp = _.toString(tmp) + 'Z';
          setFromFacet(tmp);
          const facet = '[' + tmp + '+TO+' + toFacet + ']';
          addAppliedFilter(category, facet, false);
          addAppliedFilter('sort_by', 'start_date_sort asc', false);
          updater(category, facet);
     };

     const toggleToDatePicker = () => {
          setToDatePickerVisibility(!isToDatePickerVisible);
     };

     const onSelectToDate = (date) => {
          toggleToDatePicker();
          setLoading(true);
          setTo(date);
          let tmp = moment(date).format('YYYY-MM-DDTHH:mm:ss');
          tmp = _.toString(tmp) + 'Z';
          setToFacet(tmp);
          const facet = '[' + fromFacet + '+TO+' + tmp + ']';
          addAppliedFilter(category, facet, false);
          addAppliedFilter('sort_by', 'start_date_sort asc', false);
          updater(category, facet);
     };

     return (
          <ScrollView>
               <Box safeArea={5}>
                    <FormControl mb={2}>
                         <HStack space={3} alignItems="center" justifyContent="center">
                              <Button variant="outline" onPress={() => toggleFromDatePicker()}>
                                   {moment(fromValue).format('MM/DD/YYYY')}
                              </Button>
                              <Text>to</Text>
                              <Button variant="outline" onPress={() => toggleToDatePicker()}>
                                   {toFacet === '*' ? 'MM/DD/YYYY' : moment(toValue).format('MM/DD/YYYY')}
                              </Button>
                         </HStack>
                    </FormControl>
                    <DateTimePickerModal isVisible={isFromDatePickerVisible} date={fromValue} mode="date" onConfirm={onSelectFromDate} onCancel={toggleFromDatePicker} isDarkModeEnabled={colorMode} minimumDate={today} textColor={textColor} confirmTextIOS={getTermFromDictionary(language, 'update')} />
                    <DateTimePickerModal isVisible={isToDatePickerVisible} date={toValue} mode="date" onConfirm={onSelectToDate} onCancel={toggleToDatePicker} isDarkModeEnabled={colorMode} minimumDate={today} textColor={textColor} confirmTextIOS={getTermFromDictionary(language, 'update')} />
               </Box>
          </ScrollView>
     );
};