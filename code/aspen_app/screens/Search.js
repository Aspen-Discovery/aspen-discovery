import React, { Component } from 'react';
import { ActivityIndicator, FlatList, Image, KeyboardAvoidingView, Text, TextInput, TouchableOpacity, View } from 'react-native';
import AsyncStorage from '@react-native-async-storage/async-storage';
import Stylesheet from './Stylesheet';

export default class Search extends Component {
  // establishes the title for the window
  static navigationOptions = { title: 'Search' };

  constructor() {
    super();

    this.state = {
      isLoading: true,
      searchTerm: ''
    };
  }

  // handles the mount information, setting session variables, etc
  componentDidMount = async() =>{

    this.state = {
      pathLibrary: await AsyncStorage.getItem('library'),
      pathUrl: await AsyncStorage.getItem('url'),
    };
    
    // URL of the data to pull for custom serach lists
    const url = this.state.pathUrl + '/app/aspenSearchLists.php?library=' + this.state.pathLibrary;
      
    // grab the info
    fetch(url)
      .then(res => res.json())
      .then(res => {
        this.setState({
          data: res.list,
          isLoading: false,
        });
      })
      .catch(error => {
        console.log("get data error from:" + url + " error:" + error);
      });
  }

  initiateSearch = async() => {
    const { searchTerm } = this.state;
    this.props.navigation.navigate('ListResults', { searchTerm: searchTerm, searchType: this.state.searchType } );
  }

  // renders the items on the screen
  renderItem = (item) => {
    const { navigate } = this.props.navigation;
    return (
      <View behavior='padding' style={ Stylesheet.outerContainer }>
        <View style={ Stylesheet.btnContainer }>
        <TouchableOpacity style={ Stylesheet.btnFormat } onPress={ () => navigate('ListResults', { searchTerm: item.SearchTerm, searchType: this.state.searchType } )}>
          <Text style={ Stylesheet.btnText }>{ item.SearchName }</Text>
        </TouchableOpacity>
      </View>
      </View>
    );
  }

  clearText = () => {
    this.setState({ searchTerm: '' });
  }

  getHeader = () => {

    return (
      <KeyboardAvoidingView behavior='padding' style={ Stylesheet.outerContainer }>
        <Text style={ Stylesheet.spacer }>{ }</Text>
        
        <View style={ Stylesheet.searchWrapper }>
          <TextInput style={ Stylesheet.searchInput } 
            placeholder = 'Enter Search Term ...'
            placeholderTextColor = "#F0F0F0" 
            autoCapitalize = 'none' 
            onChangeText = { (searchTerm) => this.setState({ searchTerm }) } 
            onSubmitEditing = { this.initiateSearch }
            value = { this.state.searchTerm }
          />

          <TouchableOpacity style={ Stylesheet.clearSearch } onPress={ this.clearText }>
            <Image style={ Stylesheet.clearText } source={require('../assets/clearText.png')} />
          </TouchableOpacity>
        </View>

        <View style={ Stylesheet.btnContainer }>
          <TouchableOpacity style={ Stylesheet.btnFormat } onPress={ this.initiateSearch }>
            <Text style={ Stylesheet.btnText }>Search</Text>
          </TouchableOpacity>
        </View>

        <Text style={ Stylesheet.spacer }>{ }</Text>
        <Text style={ Stylesheet.spacer }>Quick Searches:</Text>
      </KeyboardAvoidingView>
    );
  }

  render() {
    if (this.state.isLoading) {
      return (
        <View style={ Stylesheet.activityIndicator }>
          <ActivityIndicator size='large' color='#272362' />
        </View>
      );
    }

    // activity indicator is now cleared, display the content
    return (
      <View style={ Stylesheet.searchResultsContainer }>
        <FlatList 
          data={ this.state.data } 
          renderItem={( {item} ) => this.renderItem(item) } keyExtractor={ (item, index) => index.toString() }  
          ListHeaderComponent={ this.getHeader }
        />
      </View>
    );
  }
}
