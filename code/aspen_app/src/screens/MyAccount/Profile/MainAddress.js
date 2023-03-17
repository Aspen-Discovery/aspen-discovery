import { Box, Text } from "native-base";
import React from "react";
import {LanguageContext} from '../../../context/initialContext';
import {getTermFromDictionary} from '../../../translations/TranslationService';

// custom components and helper files

const Profile_MainAddress = (props) => {
    const { language } = React.useContext(LanguageContext);
  return (
    <Box py={5}>
      <Text bold>{getTermFromDictionary(language, 'patron_address')}</Text>
      <Text>{props.address}</Text>
      <Text bold mt={2}>
          {getTermFromDictionary(language, 'patron_city')}
      </Text>
      <Text>{props.city}</Text>
      <Text bold mt={2}>
          {getTermFromDictionary(language, 'patron_state')}
      </Text>
      <Text>{props.state}</Text>
      <Text bold mt={2}>
          {getTermFromDictionary(language, 'patron_zip')}
      </Text>
      <Text>{props.zipCode}</Text>
    </Box>
  );
};

export default Profile_MainAddress;