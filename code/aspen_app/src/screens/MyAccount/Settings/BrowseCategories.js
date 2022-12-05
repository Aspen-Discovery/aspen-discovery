import { Box, HStack, Switch, Text, FlatList } from 'native-base';
import React from 'react';
import { useFocusEffect } from '@react-navigation/native';

import { getBrowseCategoryListForUser, updateBrowseCategoryStatus } from '../../../util/loadPatron';
import { BrowseCategoryContext, LibrarySystemContext } from '../../../context/initialContext';
import { reloadBrowseCategories } from '../../../util/loadLibrary';

export const Settings_BrowseCategories = () => {
     const { library } = React.useContext(LibrarySystemContext);
     const { list, updateBrowseCategoryList } = React.useContext(BrowseCategoryContext);

     useFocusEffect(
          React.useCallback(() => {
               const update = async () => {
                    await getBrowseCategoryListForUser(library.baseUrl).then((result) => {
                         if (list !== result) {
                              updateBrowseCategoryList(result);
                         }
                    });
               };
               update().then(() => {
                    return () => update();
               });
          }, [])
     );

     return <FlatList keyExtractor={(item) => item.key} data={list} renderItem={({ item }) => <DisplayCategory data={item} />} />;
};

/* export default class Settings_BrowseCategoriesB extends Component {
 constructor(props, context) {
 super(props, context);
 this.state = {
 isLoading: true,
 hasError: false,
 error: null,
 browseCategories: PATRON.browseCategories ?? [],
 };
 this._isMounted = false;
 }

 componentDidMount() {
 this._isMounted = true;
 this.setState({
 isLoading: false,
 });
 }

 componentDidUpdate(prevProps, prevState) {
 if (prevState.browseCategories !== this.state.browseCategories) {
 }
 }

 componentWillUnmount() {
 this._isMounted = false;
 }

 _updateToggle = async (category, newValue) => {
 const key = category['sourceId'] ?? category['key'];
 const textId = category['key'];
 await updateBrowseCategoryStatus(key).then((response) => {
 this.setState({
 browseCategories: {
 ...this.state.browseCategories,
 [textId]: {
 ...this.state.browseCategories[textId],
 isHidden: newValue,
 },
 },
 });
 });
 };

 renderItem = (item) => {
 return (
 <Box borderBottomWidth="1" _dark={{ borderColor: 'gray.600' }} borderColor="coolGray.200" pl="4" pr="5" py="2">
 <HStack space={3} alignItems="center" justifyContent="space-between" pb={1}>
 <Text
 isTruncated
 bold
 maxW="80%"
 fontSize={{
 base: 'lg',
 lg: 'xl',
 }}>
 {item.title}
 </Text>
 <Switch size="md" onToggle={() => this._updateToggle(item)} />
 </HStack>
 </Box>
 );
 };

 static contextType = userContext;

 render() {
 const user = this.context.user;
 const location = this.context.location;
 const library = this.context.library;

 if (this.state.isLoading === true) {
 return loadingSpinner();
 }

 if (this.state.hasError) {
 return loadError(this.state.error, '');
 }

 return <FlashList data={this.state.browseCategories} renderItem={({ item }) => this.renderItem(item)} estimatedItemSize={100} />;
 }
 } */

const DisplayCategory = (data) => {
     const category = data.data;
     //console.log(category);
     const [toggled, setToggle] = React.useState(!category.isHidden);
     const toggleSwitch = () => setToggle((previousState) => !previousState);
     const { library } = React.useContext(LibrarySystemContext);
     const { list, updateBrowseCategoryList, updateBrowseCategories } = React.useContext(BrowseCategoryContext);

     const updateToggle = async (category) => {
          const key = category['key'] ?? category['sourceId'];

          await updateBrowseCategoryStatus(key, library.baseUrl).then(async (response) => {
               await getBrowseCategoryListForUser(library.baseUrl).then((result) => {
                    updateBrowseCategoryList(result);
               });
               await reloadBrowseCategories(6, library.baseUrl).then((result) => {
                    updateBrowseCategories(result);
               });
          });

          console.log(key + ', ' + category['isHidden']);
     };
     return (
          <Box borderBottomWidth="1" _dark={{ borderColor: 'gray.600' }} borderColor="coolGray.200" pl="4" pr="5" py="2">
               <HStack space={3} alignItems="center" justifyContent="space-between" pb={1}>
                    <Text
                         isTruncated
                         bold
                         maxW="80%"
                         fontSize={{
                              base: 'lg',
                              lg: 'xl',
                         }}>
                         {category.title}
                    </Text>
                    <Switch
                         size="md"
                         name={category.key}
                         onToggle={() => {
                              toggleSwitch();
                              updateToggle(category);
                         }}
                         isChecked={toggled}
                    />
               </HStack>
          </Box>
     );
};