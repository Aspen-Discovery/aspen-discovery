import _ from 'lodash';
import { Box, Divider } from 'native-base';
import React, { Component } from 'react';

// custom components and helper files
import { userContext } from '../../../context/user';
import Profile_ContactInformation from './ContactInformation';
import Profile_Identity from './Identity';
import Profile_MainAddress from './MainAddress';
import { UserContext } from '../../../context/initialContext';
import DrawerContent from '../../../navigations/drawer/DrawerContent';

export default class Profile extends Component {
     static contextType = userContext;

     constructor(props) {
          super(props);
          this.state = {
               isLoading: true,
               hasError: false,
               error: null,
               hasUpdated: false,
               isRefreshing: false,
          };
     }

     componentDidMount = async () => {
          this.setState({
               isLoading: false,
          });
     };

     render() {
          const user = this.context.user;
          let firstname = '';
          if (!_.isUndefined(user.firstname)) {
               firstname = user.firstname;
          }
          return (
               <Box flex={1} safeArea={5}>
                    <Profile_Identity firstName={firstname} lastName={user.lastname} />
                    <Divider />
                    <Profile_MainAddress address={user.address1} city={user.city} state={user.state} zipCode={user.zip} />
                    <Divider />
                    <Profile_ContactInformation email={user.email} phone={user.phone} />
               </Box>
          );
     }
}
Profile.contextType = UserContext;