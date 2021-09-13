import React, { Component } from 'react';
import { ActivityIndicator, FlatList, TouchableOpacity, Text, View } from 'react-native';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { ListItem } from "react-native-elements";
import Constants from "expo-constants";
import Stylesheet from './Stylesheet';

export default class More extends Component {
  // establishes the title for the window
  static navigationOptions = { title: 'More' };

  constructor() {
    super();
    this.state = { isLoading: true };
  }

  // handles the mount information, setting session variables, etc
  componentDidMount = async() =>{
  const version = Constants.manifest.version;
    this.setState({
      pathUrl: await AsyncStorage.getItem('url'),
      library: await AsyncStorage.getItem('library'),
      locationId: await AsyncStorage.getItem('locationId'),
    });

    const url = this.state.pathUrl + '/app/aspenMoreDetails.php?id='+ this.state.locationId + '&library=' + this.state.library + '&version=' + version;
    console.log(url)

    fetch(url)
      .then(res => res.json())
      .then(res => {
        this.setState({
          data: res.options,
          isLoading: false
        });
      })
      .catch(error => {
        console.log("get data error from:" + url + " error:" + error);
      });
  }

  // renders the items on the screen
  renderNativeItem = (item) => {
    return (
      <ListItem bottomDivider onPress={ () => this.onPressItem(item.path) }>
        <ListItem.Content>
          <ListItem.Title>{ item.title }</ListItem.Title>
          <ListItem.Subtitle>{ item.subtitle }</ListItem.Subtitle>
        </ListItem.Content>
        <ListItem.Chevron />
      </ListItem>
      );
  }
  
  // logs out the user, but keeps the barcode for ease of logging back in
  _logout = async() => {
    await AsyncStorage.multiRemove(['isLoggedIn', 'password', 'patronName'])
    this.props.navigation.navigate('Auth');
  }

  getHeader = () => {
    return (
      <View style={ Stylesheet.outerContainer }>
        <View style={ Stylesheet.logoutButton }>
          <TouchableOpacity style={ Stylesheet.btnFormat } onPress={ this._logout }>
            <Text style={ Stylesheet.btnText }>Logout</Text>
          </TouchableOpacity>
        </View>
      </View>
    );
  }

  // handles the on press action 
  onPressItem = (item) => {
    this.props.navigation.navigate(item, { item },);
  }
 
  _listEmptyComponent = () => {
    return (
      <ListItem bottomDivider>
        <ListItem.Content>
          <ListItem.Title>Something went wrong. Please try again later.</ListItem.Title>
        </ListItem.Content>
      </ListItem>
    );
  };

  render() {
    if (this.state.isLoading) {
      return (
        <View style={ Stylesheet.activityIndicator }>
          <ActivityIndicator size='large' color='#272362' />
        </View>
      );
    }

    return (
      <View style={ Stylesheet.searchResultsContainer }>
        <FlatList 
         data={ this.state.data } 
         ListEmptyComponent = { this._listEmptyComponent() }
         ListHeaderComponent={ this.getHeader }
         renderItem={( {item} ) => this.renderNativeItem(item) } 
         keyExtractor={ (item, index) => index.toString() }  
        />
      </View>
    );
  }
}