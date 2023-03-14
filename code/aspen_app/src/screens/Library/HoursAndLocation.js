import { MaterialIcons } from '@expo/vector-icons';
import { Box, Center, Divider, HStack, Icon, Text } from 'native-base';
import React from 'react';

// custom components and helper files
import { stripHTML } from '../../util/apiAuth';
import {LanguageContext} from '../../context/initialContext';
import {getTermFromDictionary} from '../../translations/TranslationService';

const HoursAndLocation = (props) => {
     const { language } = React.useContext(LanguageContext);
     const { hoursMessage, description } = props;

     return (
          <>
               <Box mb={4}>
                    <Center>
                         <HStack space={2} alignItems="center">
                              <Icon as={MaterialIcons} name="schedule" size="sm" mt={0.3} mr={-1} />
                              <Text fontSize="md" bold>
                                   {getTermFromDictionary(language, 'today_hours')}
                              </Text>
                         </HStack>
                         <Text>{stripHTML(description)}</Text>
                         <Text alignText="center" italic>
                              {hoursMessage}
                         </Text>
                    </Center>
               </Box>
               <Divider mb={10} />
          </>
     );
};

export default HoursAndLocation;