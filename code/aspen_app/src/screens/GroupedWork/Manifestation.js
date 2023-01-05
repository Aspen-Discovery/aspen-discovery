import { Center, Text, VStack } from 'native-base';
import { useNavigation } from '@react-navigation/native';
import React from 'react';

// custom components and helper files
import { translate } from '../../translations/translations';
import { Record } from './Record';
import { LibraryBranchContext, LibrarySystemContext, UserContext } from '../../context/initialContext';
import { LIBRARY } from '../../util/loadLibrary';
import { SafeAreaView } from 'react-native';

const Manifestation = (props) => {
     const navigation = useNavigation();
     const { user } = React.useContext(UserContext);
     const { library } = React.useContext(LibrarySystemContext);
     const { location } = React.useContext(LibraryBranchContext);

     let arrayToSearch = [];
     const { data, format, language, locations, showAlert, groupedWorkTitle, groupedWorkAuthor, groupedWorkISBN, itemDetails, groupedWorkId, linkedAccounts, openHolds, openCheckouts, updateProfile } = props;

     if (typeof data[format] !== 'undefined') {
          arrayToSearch = data[format];
     }

     let locationCount = 1;
     if (typeof locations !== 'undefined') {
          locationCount = locations.length;
     }

     let variation = arrayToSearch.filter(function (item) {
          return item.format === format;
     });

     variation = variation.filter(function (item) {
          return item.language === language;
     });

     let copyDetails = [];

     variation.map((item) => {
          let copyDetail = [];
          if (LIBRARY.version >= '22.09.00') {
               copyDetail = {
                    id: item.id,
                    format: item.format,
                    totalCopies: item.totalCopies,
                    availableCopies: item.availableCopies,
                    shelfLocation: item.shelfLocation,
                    callNumber: item.callNumber,
               };
          }

          copyDetails.push(copyDetail);
     });

     if (variation.length === 0) {
          return (
               <Center mt={5} mb={0} bgColor="white" _dark={{ bgColor: 'coolGray.900' }} p={3} rounded="8px">
                    <VStack
                         alignItems="center"
                         width={{
                              base: '100%',
                              lg: '75%',
                         }}>
                         <Text bold textAlign="center">
                              {translate('grouped_work.no_matches', {
                                   language,
                                   format,
                              })}
                         </Text>
                    </VStack>
               </Center>
          );
     }

     return variation.map((item, index) => {
          let volumes = [];
          let majorityOfItemsHaveVolumes = false;
          let hasItemsWithoutVolumes = false;
          if (LIBRARY.version >= '22.06.00') {
               volumes = item.volumes;
               majorityOfItemsHaveVolumes = item.majorityOfItemsHaveVolumes;
               hasItemsWithoutVolumes = item.hasItemsWithoutVolumes;
          }

          return (
               <SafeAreaView>
                    <Record
                         key={index}
                         available={item.available}
                         availableOnline={item.availableOnline}
                         actions={item.action}
                         edition={item.edition}
                         format={item.format}
                         publisher={item.publisher}
                         publicationDate={item.publicationDate}
                         status={item.status}
                         copiesMessage={item.copiesMessage}
                         source={item.source}
                         id={item.id}
                         title={groupedWorkTitle}
                         locationCount={locationCount}
                         locations={locations}
                         showAlert={showAlert}
                         itemDetails={itemDetails}
                         user={user}
                         groupedWorkId={groupedWorkId}
                         groupedWorkAuthor={groupedWorkAuthor}
                         groupedWorkISBN={groupedWorkISBN}
                         library={library}
                         linkedAccounts={linkedAccounts}
                         openCheckouts={openCheckouts}
                         openHolds={openHolds}
                         hasItemsWithoutVolumes={hasItemsWithoutVolumes}
                         majorityOfItemsHaveVolumes={majorityOfItemsHaveVolumes}
                         volumes={volumes}
                         discoveryVersion={LIBRARY.version}
                         updateProfile={updateProfile}
                         navigation={navigation}
                         recordData={item}
                         copyDetails={copyDetails}
                         libraryUrl={library.baseUrl}
                    />
               </SafeAreaView>
          );
     });
};

export default Manifestation;