import { Box, Text } from "native-base";
import React from "react";
import {LanguageContext} from '../../../context/initialContext';
import {getTermFromDictionary} from '../../../translations/TranslationService';

// custom components and helper files

const Profile_Identity = (props) => {
    const { language } = React.useContext(LanguageContext);
  return (
    <Box pb={5}>
      <Text bold>{getTermFromDictionary(language, 'patron_full_name')}</Text>
      <Text>{props.firstName} {props.lastName}</Text>
    </Box>
  );
};

export default Profile_Identity;