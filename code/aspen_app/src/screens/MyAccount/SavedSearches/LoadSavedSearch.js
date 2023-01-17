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

/*
export class LoadSavedSearchB extends Component {
     constructor(props, context) {
          super(props, context);
          this.state = {
               isLoading: true,
               hasError: false,
               error: null,
          };
     }

     componentDidMount = () => {
          const { route } = this.props;
          const libraryUrl = this.context.library.baseUrl;
          const id = route.params?.search ?? 0;
          const title = route.params?.name ?? '';

          this.setState({
               isLoading: false,
          });

          this.openSavedSearch(id, title, libraryUrl);
     };

     componentWillUnmount() {}

     openSavedSearch = (id, title, url) => {
          this.props.navigation.push('AccountScreenTab', {
               screen: 'MySavedSearch',
               params: { id, title: getCleanTitle(title), libraryUrl: url },
          });
     };

     static contextType = userContext;

     render() {
          const { route } = this.props;
          const url = this.context.library.baseUrl;
          const id = route.params?.search ?? 0;
          const title = route.params?.name ?? '';

          if (this.state.isLoading) {
               return loadingSpinner();
          } else {
               this.props.navigation.navigate('AccountScreenTab', {
                    screen: 'MySavedSearch',
                    params: { id, title: getCleanTitle(title), libraryUrl: url },
               });
          }

          return null;
     }
}*/