import React, { Component } from 'react';
import { Alert, Image, KeyboardAvoidingView, Text, TextInput, TouchableOpacity, View } from 'react-native';
import AsyncStorage from '@react-native-async-storage/async-storage';
import ModalSelector from 'react-native-modal-selector-searchable';
import Stylesheet from './Stylesheet';

export default class Login extends Component {
  // set default values for the login information in the constructor
  constructor(props) {
    super(props);
    this.state = { username: '', password: '' }
  }

  // handles the mount information, setting session variables, etc
  componentDidMount = async() =>{
    // store the values into the state
    this.setState({
      url: await AsyncStorage.getItem('url'),
      username: await AsyncStorage.getItem('username')
    });
  }

  // clear the value of the box when clicked
  clearText = async() =>{
    this.setState({ username: '', password: '', pickUpLabel: '' });
  }

  // shows the options for locations
  showLocationPulldown = () => {
 
    const data = [
      { key: 0, section: true, label: 'Select your Library' },
      { key: 'https://aspen-test.bywatersolutions.com|test', label: 'ByWater Test' },
      { key: 'https://discover.ajaxlibrary.ca|main', label: 'AJAX Public Library, Ontario' },
      { key: 'https://libcat.arlingtonva.us|arlington', label: 'Arlington Public Library, Virginia' },
      { key: 'https://catalog.duchesnecountylibrary.org|duchesne', label: 'Duchesne County Library, Utah' },
      { key: 'https://catalog.jcls.org|jacksoncounty', label: 'Jackson County, Oregon' },
      { key: 'https://catalog.library.nashville.org|nashville', label: 'Nashville Public Library, Tennessee' },
      { key: 'https://discover.salinapubliclibrary.org|salinaks', label: 'Salina Public Library, Kansas' },
      { key: 'https://catalog.uintahlibrary.org|uintah', label: 'Uintah County Library, Utah' },
      { key: 'https://catalog.wasatchlibrary.org|wasatch', label: 'Wasatch County Library, Utah' },
    ];

    return (
      <View>
        <Text>This is the login screen</Text>
        <ModalSelector
          data = {data}
          initValue = "Select your Library"
          supportedOrientations = {['landscape']}
          animationType = 'fade'
          accessible = {true}
          scrollViewAccessibilityLabel = {'Scrollable options'}
          cancelButtonAccessibilityLabel = {'Cancel Button'}
          onChange = {(option) => { this.setState({pickUpLabel: option.label, pickUpLocation:option.key})}}>
          <TextInput
            style={ Stylesheet.modalSelector }
            editable = {false}
            placeholder = "Select your Library"
            value = {this.state.pickUpLabel} />
        </ModalSelector>
      </View>
    );
  }

  render () {
    return (
      <KeyboardAvoidingView behavior='padding' style={ Stylesheet.outerContainer }>

        <View style={Stylesheet.welcomeContainer}>
          <Image style={ Stylesheet.libraryLogo } source={ require('../assets/aspenLogo.png') } />
        </View>
        { this.showLocationPulldown() }
        <TextInput style={ Stylesheet.input } 
          id = 'barcode'
          placeholder = 'Library Barcode' 
          placeholderTextColor = "#F0F0F0"
          autoCapitalize = 'none' 
          onChangeText = { (username) => this.setState({ username }) } 
          onSubmitEditing = { () => this.passwordInput.focus() }
          returnKeyType = 'next'
          value = { this.state.username } 
        />
        <TextInput style={ Stylesheet.input } 
          placeholder = 'Password' 
          placeholderTextColor = "#F0F0F0"
          secureTextEntry 
          onChangeText = { (password) => this.setState({ password }) } 
          onSubmitEditing = { this._login }
          ref = { (input) => this.passwordInput = input }
          value = { this.state.password } 
        />
        
        <View style={ Stylesheet.btnContainer }>
          <TouchableOpacity style={ Stylesheet.btnFormat } onPress={ this._login }>
            <Text style={ Stylesheet.btnText }>Login</Text>
          </TouchableOpacity>
        </View>
        <View style={ Stylesheet.btnContainer }>
          <TouchableOpacity style={ Stylesheet.btnFormatSmall } onPress={ this.clearText }>
            <Text style={ Stylesheet.btnText }>Reset form</Text>
          </TouchableOpacity>
        </View>
      </KeyboardAvoidingView>
    );
  }

  // create a function that saves your data asyncronously
  _storeData = async (data, username, password, url, library) => {
    try {
        // grab global information once and share it throughout the app
        let patronName = data.Name.substr(0, data.Name.indexOf(' '));;
        let loggedIn   = '1';

        await AsyncStorage.setItem('isLoggedIn', loggedIn);
        await AsyncStorage.setItem('username', username);
        await AsyncStorage.setItem('password', password);
        await AsyncStorage.setItem('patronName', patronName);
        await AsyncStorage.setItem('url', url);
        await AsyncStorage.setItem('library', library);

        // toss this back to the navigator to see if we're logged in
        this.props.navigation.navigate(loggedIn ? 'App' : 'Auth');
    } catch (error) {
        //alert(error);
        Alert.alert("Error", "Sorry. There was an error. Please try again later.", [ { text: "Close" } ]);
    }
  }

  // Login function - determines if the login credentials are correct
  _login = async() => {

    // save the login credentials to the storage
    const { username, password } = this.state;

    var locationInfo = this.state.pickUpLocation.split('|');

    const random = new Date().getTime(); // included to ensure that we're pulling the most recent information
    const url = locationInfo[0] + '/app/aspenLogin.php?library=' + locationInfo[1] + '&barcode=' + this.state.username + '&pin=' + this.state.password + '&rand=' + random;

    fetch(url)
    .then(res => res.json())
    .then(res => {
      let data = res;
 
      // verify if the login credentials match the system
      if (data.ValidLogin === 'Yes') {
        this._storeData(data, username, password, locationInfo[0], locationInfo[1]);

      } else {
        // no good login - fail
        Alert.alert("Login Error", "The barcode or PIN are incorrect. Please try again.", [ { text: "Close" } ]);
      }
    })
    .catch(error => {
      console.log("get data error from:" + url + " error:" + error);
    });
  };
}