import { Box, Text } from "native-base";
import React from "react";
import {LanguageContext} from '../../../context/initialContext';
import {getTermFromDictionary} from '../../../translations/TranslationService';

// custom components and helper files

const Profile_ContactInformation = (props) => {
    const { language } = React.useContext(LanguageContext);
  return (
    <Box py={5}>
      <Text bold>{getTermFromDictionary(language, 'patron_primary_phone')}</Text>
      <Text>{props.phone}</Text>
      <Text bold mt={2}>
          {getTermFromDictionary(language, 'patron_email')}
      </Text>
      <Text>{props.email}</Text>
    </Box>
  );
};

export default Profile_ContactInformation;