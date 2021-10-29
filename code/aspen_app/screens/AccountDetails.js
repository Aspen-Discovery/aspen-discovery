import React, { Component } from 'react';
import { ActivityIndicator, FlatList, Text, View, TouchableOpacity } from 'react-native';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { Avatar, ListItem } from "react-native-elements"
import Stylesheet from './Stylesheet';

export default class AccountDetail extends Component {

  // establishes the title for the window
  static navigationOptions = { title: 'Account' };

  constructor() {
    super();
    this.state = {
      isLoading: true
    };
  }

  // handles the mount information, setting session variables, etc
  componentDidMount = async() =>{
    // store the values into the state
    this.setState({
      password: await AsyncStorage.getItem('password'),
      pathLibrary: await AsyncStorage.getItem('library'),
      pathUrl: await AsyncStorage.getItem('url'),
      patronName: await AsyncStorage.getItem('patronName'),
      username: await AsyncStorage.getItem('username')
    });

    // grab the checkouts
    this.getCheckOuts();

    // forces a new connection to ensure that we're getting the newest stuff
    this.willFocusSubscription = this.props.navigation.addListener('willFocus', () => { this.getCheckOuts(); } );
  }

  // needed to ensure that the data refreshes
  componentWillUnmount() {
    this.willFocusSubscription.remove();
  }

  // grabs the items checked out to the account
  getCheckOuts = () => {
    const random = new Date().getTime();
    const url = this.state.pathUrl + '/app/aspenAccountDetails.php?library=' + this.state.pathLibrary + '&barcode=' + this.state.username + '&pin=' + this.state.password + '&rand=' + random;
    
    fetch(url)
      .then(res => res.json())
      .then(res => {
        this.setState({
          checkOuts: res.numCheckedOut,
          holdsILS: res.holdsILS,
          holdsEProduct: res.holdsEProduct,
          isLoading: false,
        });
      })
      .catch(error => {
        console.log("get data error from:" + url + " error:" + error);
      });
  };

  // renders the items on the screen
  renderNativeItem = (item) => {
    const iconName = '../assets/aspenLogo.png';

    return (
      <ListItem bottomDivider onPress={ () => this.onPressItem(item.key) }>
        <Avatar rounded source={ require(iconName) } />
        <ListItem.Content>
          <ListItem.Title>{ item.title }</ListItem.Title>
          <ListItem.Subtitle>{ item.note }</ListItem.Subtitle>
        </ListItem.Content>
        <ListItem.Chevron />
      </ListItem>
    );
  }

  // handles the on press action 
  onPressItem = (item) => {
    this.props.navigation.navigate(item, { item },);
  }

  getHeader = () => {
    return (
      <View>
        <View style={ Stylesheet.accountInformation }>
        <Text style={ Stylesheet.accountTextHeader }>{ this.state.patronName }'s Account Summary:</Text>
        <Text style={ Stylesheet.accountText }>Barcode: { this.state.username }</Text>
        <Text style={ Stylesheet.accountText }>Items checked out: { this.state.checkOuts }</Text>
        <Text style={ Stylesheet.accountText }>Items on hold: { this.state.holdsILS }</Text>
        <Text style={ Stylesheet.accountText }>eItems on hold: { this.state.holdsEProduct }</Text>
        </View>
      </View>
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

    return (
      <View style={ Stylesheet.searchResultsContainer }>
        <FlatList
          data={[  
            { key: 'ListCKO', title: 'Items Checked out (' + this.state.checkOuts + ')', note: 'Click to check due dates and renewal options.', thumbnail: 'books.png' },
            { key: 'ListHold', title: 'Items On Hold (' + this.state.holdsILS + ')', note: 'Click to see what you have on hold.', thumbnail: 'holds.png' },
          ]}  
          renderItem={( {item} ) => this.renderNativeItem(item) }  
          ListHeaderComponent={ this.getHeader() }
        />
      </View>
    );
  }
}