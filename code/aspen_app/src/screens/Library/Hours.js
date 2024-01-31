import _ from 'lodash';
import moment from 'moment';
import { Box, FlatList, Heading, HStack, Text, VStack } from 'native-base';
import React from 'react';

// custom components and helper files
import { LanguageContext } from '../../context/initialContext';
import { getTermFromDictionary } from '../../translations/TranslationService';

const Hours = (data) => {
     const { language } = React.useContext(LanguageContext);
     const location = data.data;

     /* location.hours */

     if (location.showInLocationsAndHoursList === '1' || location.showInLocationsAndHoursList === 1) {
          if (_.isArrayLikeObject(location.hours)) {
               return (
                    <Box>
                         <Heading mb={2}>{getTermFromDictionary(language, 'library_hours')}</Heading>
                         <FlatList data={location.hours} renderItem={({ item }) => <Day hours={item} />} />
                    </Box>
               );
          }
     }

     return null;
};

const Day = (data) => {
     const { language } = React.useContext(LanguageContext);
     const hours = data.hours;

     function formatTime(time) {
          let arr = time.split(':');
          let timeString = moment().set({ hour: arr[0], minute: arr[1] });
          return moment(timeString).format('h:mm A');
     }

     return (
          <VStack mb={2}>
               <HStack justifyContent="space-between">
                    <Text bold>{hours.dayName}</Text>
                    {!hours.isClosed ? (
                         <Text>
                              {formatTime(hours.open)} - {formatTime(hours.close)}
                         </Text>
                    ) : (
                         <Text>{getTermFromDictionary(language, 'location_closed')}</Text>
                    )}
               </HStack>
               {hours.notes !== '' ? (
                    <Text fontSize="xs" italic>
                         {hours.notes}
                    </Text>
               ) : null}
          </VStack>
     );
};

export default Hours;