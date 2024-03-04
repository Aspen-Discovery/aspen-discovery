import React from 'react';
import { useRoute, useNavigation, StackActions } from '@react-navigation/native';
import { getCleanTitle } from '../../../helpers/item';
import {LibrarySystemContext} from '../../../context/initialContext';

export const LoadSavedSearch = () => {
     const navigation = useNavigation();
     const id = useRoute().params.search ?? 0;
     const title = useRoute().params.name ?? 'Saved Search Results';
     const { library } = React.useContext(LibrarySystemContext);
     const url = library.baseUrl;

     const pushAction = StackActions.push('MySavedSearch',
         {
              id: id,
              title: getCleanTitle(title),
              libraryUrl: url,
              prevRoute: 'NONE'
         });

     navigation.dispatch(pushAction);

}