import React, { Component, useState, useEffect } from 'react';
import { ActivityIndicator, Button, FlatList, Alert, Image, KeyboardAvoidingView, Text, TextInput, TouchableOpacity, View, ScrollView } from 'react-native';
import AsyncStorage from '@react-native-async-storage/async-storage';
import ModalSelector from 'react-native-modal-selector-searchable';
import Stylesheet from './Stylesheet';
import * as Location from 'expo-location';

export default class Login extends Component {
  // set default values for the login information in the constructor
  constructor(props) {
    super(props);
    this.state = {
    username: '',
    password: '',
    isLoading: true,
    };
  }

  RefreshLoc = async() => {
    return ( this.props.navigation.navigate('ResetLocation') );
  }

  // handles the mount information, setting session variables, etc
  componentDidMount = async() =>{
    // store the values into the state
    this.setState({
    url: await AsyncStorage.getItem('url'),
    username: await AsyncStorage.getItem('username'),
    latitude: await AsyncStorage.getItem('latitude'),
    longitude: await AsyncStorage.getItem('longitude'),
    isLoading: false,
    });
  }

  // clear the value of the box when clicked
  clearText = async() =>{
    this.setState({ username: '', password: '', libraryName: '' });
  }

  clearAsyncStorage = async() => {
      AsyncStorage.clear();
  }

  // shows the options for locations
  showLocationPulldown = () => {
      let latitude = this.state.latitude;
      let longitude = this.state.longitude;
      let greenhouseUrl = 'https://aspen-test.bywatersolutions.com/API/GreenhouseAPI?method=getLibraries&latitude=' + latitude + '&longitude=' + longitude
      //console.log(greenhouseUrl);

      // fetch greenhouse data
      fetch(greenhouseUrl, {
      header: {
         'Accept': 'application/json',
         'Content-Type':'application/json'
      },
      timeout: 5000})
      .then(res => res.json())
      .then((res) => {
         this.setState({
             allLibraries: res.libraries
         });
       },(err) => {
       console.warn('Its borked! Aspen was unable to connect to the Greenhouse. Attempted connecting to <' + greenhouseUrl +'>');
       console.warn('Error: ',err)
      })

    return (
      <View>
        <ModalSelector
          data = {this.state.allLibraries}
          keyExtractor= {item => item.baseUrl.concat("|" , item.solrScope, "|", item.libraryId, '|', item.locationId)}
          labelExtractor= {item => item.name}
          initValue = "Select your Library ▼"
          supportedOrientations = {['portrait', 'landscape']}
          animationType = 'fade'
          accessible = {true}
          cancelText = "Cancel"
          scrollViewAccessibilityLabel = {'Scrollable options'}
          cancelButtonAccessibilityLabel = {'Cancel'}
          onChange={option => { this.setState({ libraryName: option.name, libraryUrl:option.baseUrl.concat("|" , option.solrScope, "|", option.libraryId, '|', option.locationId) }) }}>
          <TextInput
            style={ Stylesheet.modalSelector }
            editable = {false}
            placeholder = "Select Your Library ▼"
            placeholderTextColor = "#000"
            value = {this.state.libraryName} />
        </ModalSelector>
      </View>
    );
  }

  render () {

   if (this.state.isLoading) {
        return (
          <View style={ Stylesheet.activityIndicator }>
            <ActivityIndicator size='large' color='#272362' />
          </View>
        );
      }

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
          //value = { this.state.username }
        />
        <TextInput style={ Stylesheet.input }
          placeholder = 'Password/PIN'
          placeholderTextColor = "#F0F0F0"
          secureTextEntry
          onChangeText = { (password) => this.setState({ password }) }
          onSubmitEditing = { this._login }
          ref = { (input) => this.passwordInput = input }
          //value = { this.state.password }
        />

        <View style={ Stylesheet.btnContainer }>
          <TouchableOpacity style={ Stylesheet.btnFormat } onPress={ this._login }>
            <Text style={ Stylesheet.btnText }>Login</Text>
          </TouchableOpacity>
        </View>
        <View style={ Stylesheet.btnContainer }>
          <TouchableOpacity style={ Stylesheet.btnFormatSmall } onPress={ this.RefreshLoc }>
            <Text style={ Stylesheet.btnTextSmall }>Try Reloading Location</Text>
          </TouchableOpacity>
        </View>
        <View>
        </View>
      </KeyboardAvoidingView>
    );


  }

  // create a function that saves your data asyncronously
  _storeData = async (data, username, password, url, library, locationId) => {
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
        await AsyncStorage.setItem('libraryName', this.state.libraryName);
        await AsyncStorage.setItem('locationId', locationId);

        // toss this back to the navigator to see if we're logged in
        this.props.navigation.navigate(loggedIn ? 'App' : 'Auth');
        this.setState({ isLoading: false })
    } catch (error) {
        //alert(error);
        Alert.alert("Error", "Sorry. There was an error. Please try again later.", [ { text: "Close" } ]);
    }
  }

  // Login function - determines if the login credentials are correct
  _login = async() => {

    this.setState({ isLoading: true })
    // save the login credentials to the storage
    const { username, password } = this.state;

    var locationInfo = this.state.libraryUrl.split('|');

    const random = new Date().getTime(); // included to ensure that we're pulling the most recent information
    const url = locationInfo[0] + '/app/aspenLogin.php?id='+ locationInfo[2] + '&library=' + locationInfo[1] + '&barcode=' + this.state.username + '&pin=' + this.state.password + '&rand=' + random;
    fetch(url)
    .then(res => res.json())
    .then(res => {
      let data = res;
 
      // verify if the login credentials match the system
      if (data.ValidLogin === 'Yes') {
        this._storeData(data, username, password, locationInfo[0], locationInfo[1], locationInfo[2]);
      } else {
        // no good login - fail
        Alert.alert("Login Error", "The barcode or PIN are incorrect. Please try again.", [ { text: "Close" } ]);
      }
    })
    .catch(error => {
      console.log("get data error from: " + url + " error:" + error);
    });
  };

  async _cacheResourcesAsync() {
      const images = [require('../assets/aspenLogo.png')];

      const cacheImages = images.map(image => {
        return Asset.fromModule(image).downloadAsync();
      });
      return Promise.all(cacheImages);
    }
}