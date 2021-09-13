import React, { Component } from 'react';
import { ActivityIndicator, Linking, Platform, ScrollView, Text, TouchableOpacity, View } from 'react-native';
import AsyncStorage from '@react-native-async-storage/async-storage';
import Stylesheet from './Stylesheet';

export default class News extends Component {
  // establishes the title for the window
  static navigationOptions = { title: "Contact Us" };

  constructor() {
    super();

    this.state = {
      isLoading: true
    };
  }

  // handles the mount information, setting session variables, etc
  componentDidMount = async() =>{
    //const url = 'https://www.ajaxlibrary.ca/app/aspenMoreDetails.php';
    this.state = {
      pathLibrary: await AsyncStorage.getItem('library'),
      pathUrl: await AsyncStorage.getItem('url'),
      locationId: await AsyncStorage.getItem('locationId'),
    };
    
    const url = this.state.pathUrl + '/app/aspenMoreDetails.php?id='+ this.state.locationId + '&library=' + this.state.pathLibrary;

    fetch(url)
      .then(res => res.json())
      .then(res => {
        this.setState({
          dataContactUs: res.contactUs,
          dataUniversal: res.universal,
          isLoading: false
        });
      })
      .catch(error => {
        console.log("get data error from:" + url + " error:" + error);
      });
  }

  // handles the calling of the library when the button is clicked
  dialCall = (number) => {
    let phoneNumber = '';
    if (Platform.OS === 'android') { phoneNumber = `tel:${number}`; }
    else {phoneNumber = `telprompt:${number}`; }
    Linking.openURL(phoneNumber);
  };

  // handles the click for visiting the website
  handleClick = (linkToFollow) => {
    Linking.canOpenURL( linkToFollow ).then(supported => {
      if (supported) {
        Linking.openURL( linkToFollow );
      }
    });
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
      <ScrollView contentContainerStyle={{ flexGrow: 1 }}>
        <View style={ Stylesheet.outerContainer }>

        <Text style={ Stylesheet.newsItem }>{ this.state.dataContactUs.blurb }</Text>

        <Text style={ Stylesheet.newsItem }>Today's Branch Hours:{'\n'} { this.state.dataUniversal.todayHours }</Text>

        <View style={ Stylesheet.btnContainer }>
          <TouchableOpacity style={ Stylesheet.btnFormat } onPress={ ()=>{ this.dialCall(this.state.dataUniversal.phone) } }>
            <Text style={ Stylesheet.btnText }>Call the Library</Text>
          </TouchableOpacity>
        </View>

        <View style={ Stylesheet.btnContainer }>
          <TouchableOpacity style={ Stylesheet.btnFormat } onPress={ ()=>{ this.handleClick(this.state.dataContactUs.email) } }>
            <Text style={ Stylesheet.btnText }>Email a Librarian</Text>
          </TouchableOpacity>
        </View>        

        <View style={ Stylesheet.btnContainer }>
          <TouchableOpacity style={ Stylesheet.btnFormat } onPress={ ()=>{ this.handleClick(this.state.dataUniversal.website) } }>
            <Text style={ Stylesheet.btnText }>Visit our Website</Text>
          </TouchableOpacity>
        </View>
      </View>
      </ScrollView>
    );
  }
}
