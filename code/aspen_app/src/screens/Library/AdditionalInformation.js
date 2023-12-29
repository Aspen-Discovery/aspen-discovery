import { Box, Divider, Heading, Text } from 'native-base';
import React from 'react';
import { LanguageContext, LibraryBranchContext } from '../../context/initialContext';
import { getTermFromDictionary } from '../../translations/TranslationService';

// custom components and helper files
import { stripHTML } from '../../util/apiAuth';

const AdditionalInformation = () => {
     const { location } = React.useContext(LibraryBranchContext);
     const { language } = React.useContext(LanguageContext);

     if (location.description) {
          return (
               <Box>
                    <Divider mb={2} />
                    <Heading mb={2}>{getTermFromDictionary(language, 'additional_information')}</Heading>
                    <Text>{stripHTML(location.description)}</Text>
               </Box>
          );
     }

     return null;
};

export default AdditionalInformation;