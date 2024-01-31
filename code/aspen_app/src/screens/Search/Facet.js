import _ from 'lodash';
import { Box, Button, Center, Checkbox, ChevronLeftIcon, Input, Pressable, View } from 'native-base';
import React, { Component } from 'react';

// custom components and helper files
import { ScrollView } from 'react-native';

import { loadingSpinner } from '../../components/loadingSpinner';
import { userContext } from '../../context/user';
import { getTermFromDictionary } from '../../translations/TranslationService';
import { LIBRARY } from '../../util/loadLibrary';
import { addAppliedFilter, buildParamsForUrl, removeAppliedFilter, SEARCH, searchAvailableFacets } from '../../util/search';
import Facet_Checkbox from './Facets/Checkbox';
import { Facet_Date } from './Facets/Date';
import Facet_RadioGroup from './Facets/RadioGroup';
import Facet_Rating from './Facets/Rating';
import Facet_Slider from './Facets/Slider';
import Facet_Year from './Facets/Year';
import { UnsavedChangesExit } from './UnsavedChanges';

export default class Facet extends Component {
     static contextType = userContext;

     constructor(props, context) {
          super(props, context);
          this.state = {
               isLoading: true,
               term: this.props.route.params?.term,
               data: this.props.route.params?.data ?? [],
               title: this.props.route.params?.extra['label'] ?? 'Filter',
               facets: this.props.route.params?.facets ?? [],
               facetsOriginal: this.props.route.params?.facets ?? [],
               numFacets: 0,
               category: this.props.route.params?.extra['field'] ?? '',
               applied: [],
               multiSelect: Boolean(this.props.route.params?.extra['multiSelect']),
               pendingFilters: SEARCH.pendingFilters,
               filterByQuery: '',
               hasPendingChanges: false,
               showWarning: false,
               isUpdating: false,
               resetOptions: false,
               values: [],
               pending: [],
               valuesDefault: [],
               language: this.props.route.params?.language ?? 'en',
          };
          this._isMounted = false;
     }

     componentDidMount = async () => {
          this._isMounted = true;

          const data = _.filter(SEARCH.availableFacets, ['field', this.state.category]);
          if (data[0]) {
               this.setState({
                    facets: data[0]['facets'],
                    numFacets: _.size(data[0]['facets']),
               });
          }

          this.preselectValues();

          this.setState({
               isLoading: false,
          });
     };

     componentDidUpdate(prevProps, prevState) {
          const { navigation } = this.props;
          const routes = navigation.getState()?.routes;
          const prevRoute = routes[routes.length - 2];
          if (prevRoute) {
               navigation.setOptions({
                    headerLeft: () => (
                         <Pressable
                              mr={3}
                              onPress={() => {
                                   this.updateGlobal();
                                   this.props.navigation.navigate('Filters', {
                                        pendingFilters: SEARCH.pendingFilters,
                                   });
                              }}
                              hitSlop={{ top: 12, bottom: 12, left: 12, right: 12 }}>
                              <ChevronLeftIcon size={5} color="primary.baseContrast" />
                         </Pressable>
                    ),
                    headerRight: () => <UnsavedChangesExit updateSearch={this.updateSearch} discardChanges={this.discardChanges} updateGlobal={this.updateGlobal} prevRoute="Filters" language={this.state.language} />,
               });
          } else {
               navigation.setOptions({
                    headerLeft: () => <Box />,
                    headerRight: () => <UnsavedChangesExit updateSearch={this.updateSearch} discardChanges={this.discardChanges} prevRoute="Filters" language={this.state.language} />,
               });
          }
     }

     componentWillUnmount() {
          this._isMounted = false;
     }

     async filterFacets() {
          await searchAvailableFacets(this.state.category, this.state.title, this.state.filterByQuery, LIBRARY.url, this.state.language).then((result) => {
               if (result.success === false) {
                    this.setState({
                         isLoading: false,
                    });
               } else {
                    this.setState({
                         facets: result['facets'],
                         numFacets: _.size(result['facets']),
                         isLoading: false,
                    });
               }
          });
          /*     if (sorted) {
		 const sortedList = _.orderBy(
		 list,
		 ["isApplied", "count", "display"],
		 ["desc", "desc", "asc"]
		 );
		 return _.filter(sortedList, function (facet) {
		 return facet.display.indexOf(filterByQuery) > -1;
		 });
		 }

		 return _.filter(list, function (facet) {
		 return facet.display.indexOf(filterByQuery) > -1;
		 }); */
     }

     searchBar = () => {
          const placeHolder = getTermFromDictionary(this.state.language, 'search') + ' ' + this.state.title;
          /* always display the search bar */
          if (this.state.numFacets >= 0) {
               return (
                    <Box safeArea={5}>
                         <Input
                              value={this.state.filterByQuery}
                              name="filterSearchBar"
                              onChangeText={(filterByQuery) => this.setState({ filterByQuery })}
                              size="lg"
                              autoCorrect={false}
                              variant="outline"
                              returnKeyType="search"
                              placeholder={placeHolder}
                              _dark={{
                                   color: 'muted.50',
                                   borderColor: 'muted.50',
                              }}
                              onSubmitEditing={async () => {
                                   this.setState({
                                        isLoading: true,
                                   });
                                   await this.filterFacets();
                              }}
                         />
                    </Box>
               );
          } else {
               return <Box pb={5} />;
          }
     };

     preselectValues = () => {
          let values = [];
          const { category, multiSelect } = this.state;
          const cluster = _.filter(SEARCH.pendingFilters, ['field', category]);
          _.map(cluster, function (item, index, collection) {
               const facets = item['facets'];
               if (_.size(facets) > 0) {
                    _.forEach(facets, function (value, key) {
                         if (multiSelect) {
                              values = _.concat(values, value);
                         } else {
                              values = value;
                         }
                    });
               }
          });
          this.setState({
               values,
               valuesDefault: values,
          });
     };

     updateSearch = (resetFacetGroup = false, toFilters = false) => {
          const params = buildParamsForUrl();
          //console.log(params);
          SEARCH.hasPendingChanges = false;
          const { navigation } = this.props;
          if (toFilters) {
               navigation.navigate('Filters', {
                    term: SEARCH.term,
               });
          } else {
               navigation.navigate('SearchResults', {
                    term: SEARCH.term,
                    pendingParams: params,
               });
          }
     };

     updateLocalValues = (group, values) => {
          SEARCH.hasPendingChanges = true;
          this.updateGlobal(group, values);
          this.setState({
               values,
          });
     };

     updateGlobal = (group, values) => {
          const multiSelect = this.state.multiSelect;
          const prevSelections = this.state.values;
          addAppliedFilter(group, values, multiSelect);
          if (multiSelect) {
               const difference = _.difference(prevSelections, values);
               if (difference) {
                    removeAppliedFilter(group, difference);
               }
          }
     };

     discardChanges = () => {
          SEARCH.hasPendingChanges = true;
          const { values, category, valuesDefault } = this.state;
          const difference = _.difference(values, valuesDefault);
          if (difference) {
               removeAppliedFilter(category, difference);
          }
          this.setState({
               values: [],
          });
     };

     resetCluster = () => {
          SEARCH.hasPendingChanges = true;
          const { values, category } = this.state;
          removeAppliedFilter(category, values);
          this.setState({
               values: [],
          });
          this.updateSearch();
     };

     actionButtons = () => {
          return (
               <Box safeArea={3} _light={{ bg: 'coolGray.50' }} _dark={{ bg: 'coolGray.700' }} shadow={1}>
                    <Center>
                         <Button.Group size="lg">
                              <Button variant="unstyled" onPress={() => this.resetCluster()}>
                                   {getTermFromDictionary(this.state.language, 'reset')}
                              </Button>
                              <Button
                                   isLoading={this.state.isUpdating}
                                   isLoadingText={getTermFromDictionary(this.state.language, 'updating', true)}
                                   onPress={() => {
                                        this.updateSearch();
                                   }}>
                                   {getTermFromDictionary(this.state.language, 'update')}
                              </Button>
                         </Button.Group>
                    </Center>
               </Box>
          );
     };

     render() {
          const { facets, category, multiSelect } = this.state;

          if (this.state.isLoading) {
               return loadingSpinner();
          }

          if (category === 'publishDate' || category === 'birthYear' || category === 'deathYear' || category === 'publishDateSort') {
               return (
                    <View style={{ flex: 1 }}>
                         <ScrollView>
                              <Box safeArea={5}>
                                   <Facet_Year category={category} updater={this.updateLocalValues} data={facets} language={this.state.language} />
                              </Box>
                         </ScrollView>
                         {this.actionButtons()}
                    </View>
               );
          } else if (category === 'start_date') {
               return (
                    <View style={{ flex: 1 }}>
                         <ScrollView>
                              <Box safeArea={5}>
                                   <Facet_Date category={category} updater={this.updateLocalValues} data={facets} />
                              </Box>
                         </ScrollView>
                         {this.actionButtons()}
                    </View>
               );
          } else if (category === 'rating_facet') {
               return (
                    <View style={{ flex: 1 }}>
                         <ScrollView>
                              <Box safeArea={5}>
                                   <Facet_Rating category={category} updater={this.updateLocalValues} data={facets} />
                              </Box>
                         </ScrollView>
                         {this.actionButtons()}
                    </View>
               );
          } else if (category === 'lexile_score' || category === 'accelerated_reader_point_value' || category === 'accelerated_reader_reading_level') {
               return (
                    <View style={{ flex: 1 }}>
                         <ScrollView>
                              <Box safeArea={5}>
                                   <Facet_Slider category={category} data={facets} updater={this.updateLocalValues} language={this.state.language} />
                              </Box>
                         </ScrollView>
                         {this.actionButtons()}
                    </View>
               );
          } else if (multiSelect) {
               return (
                    <View style={{ flex: 1 }}>
                         {this.searchBar()}
                         <ScrollView>
                              <Box safeAreaX={5}>
                                   <Checkbox.Group name={category} value={this.state.values} accessibilityLabel={getTermFromDictionary(this.state.language, 'filter_by')} onChange={(values) => this.updateLocalValues(category, values)}>
                                        {_.orderBy(facets, ['isApplied', 'display'], ['desc', 'asc']).map((item, index, array) => (
                                             <Facet_Checkbox key={index} data={item} language={this.state.language} />
                                        ))}
                                   </Checkbox.Group>
                              </Box>
                         </ScrollView>
                         {this.actionButtons()}
                    </View>
               );
          } else {
               return (
                    <View style={{ flex: 1 }}>
                         {this.searchBar()}
                         <ScrollView>
                              <Box safeAreaX={5}>
                                   <Facet_RadioGroup data={facets} category={category} title={this.state.title} applied={this.state.values} updater={this.updateLocalValues} language={this.state.language} />
                              </Box>
                         </ScrollView>
                         {this.actionButtons()}
                    </View>
               );
          }
     }
}