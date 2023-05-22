import _ from 'lodash';
import { Box, Divider } from 'native-base';
import React from 'react';
import { useNavigation, useFocusEffect } from '@react-navigation/native';

// custom components and helper files
import { userContext } from '../../../context/user';
import Profile_ContactInformation from './ContactInformation';
import Profile_Identity from './Identity';
import Profile_MainAddress from './MainAddress';
import { UserContext } from '../../../context/initialContext';
import DrawerContent from '../../../navigations/drawer/DrawerContent';

export const MyProfile = () => {
     const navigation = useNavigation();
     const { user } = React.useContext(UserContext);

     let firstname = '';
     if (!_.isUndefined(user.firstname)) {
          firstname = user.firstname;
     }

     let lastname = '';
     if (!_.isUndefined(user.lastname)) {
          lastname = user.lastname;
     }

     React.useLayoutEffect(() => {
          navigation.setOptions({
               headerLeft: () => <Box />,
          });
     }, [navigation]);

     return (
          <Box flex={1} safeArea={5}>
               <Profile_Identity firstName={firstname} lastName={lastname} />
               <Divider />
               <Profile_MainAddress address={user.address1} city={user.city} state={user.state} zipCode={user.zip} />
               <Divider />
               <Profile_ContactInformation email={user.email} phone={user.phone} />
          </Box>
     );
};