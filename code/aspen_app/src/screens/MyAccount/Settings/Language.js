import { Box, HStack, Text } from 'native-base';
import React from 'react';
import { LanguageContext, LibraryBranchContext, LibrarySystemContext } from '../../../context/initialContext';
// custom components and helper files
import { getLanguageDisplayName, getTranslatedTermsForUserPreferredLanguage, LanguageSwitcher, translationsLibrary } from '../../../translations/TranslationService';
import { saveLanguage } from '../../../util/api/user';

export const Settings_LanguageScreen = () => {
     const { library } = React.useContext(LibrarySystemContext);
     const { location } = React.useContext(LibraryBranchContext);
     const { language, updateLanguage, languages, updateDictionary } = React.useContext(LanguageContext);
     const [label, setLabel] = React.useState(getLanguageDisplayName(language, languages));

     const changeLanguage = async (val) => {
          await saveLanguage(val, library.baseUrl).then(async (result) => {
               if (result) {
                    updateLanguage(val);
                    setLabel(getLanguageDisplayName(val, languages));
                    await getTranslatedTermsForUserPreferredLanguage(val, library.baseUrl).then(() => {
                         updateDictionary(translationsLibrary);
                    });
               } else {
                    console.log('there was an error updating the language...');
               }
          });
     };

     return (
          <Box safeArea={5}>
               <HStack justifyContent="space-between" alignItems="center">
                    <Text bold>Language</Text>
                    <LanguageSwitcher />
               </HStack>
          </Box>
     );
};