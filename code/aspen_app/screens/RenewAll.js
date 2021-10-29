import React, { Component } from 'react';
import { ActivityIndicator, Text, TouchableOpacity, View } from 'react-native';
import AsyncStorage from '@react-native-async-storage/async-storage';
import Stylesheet from './Stylesheet';

export default class RenewAll extends Component {
  // establishes the title for the window
  static navigationOptions = { title: 'Renewal All' };
  
  constructor() {
    super();
    this.state = { isLoading: true };
  }

  // handles the mount information, setting session variables, etc
  componentDidMount = async() =>{
    this.setState({
      password: await AsyncStorage.getItem('password'),
      pathLibrary: await AsyncStorage.getItem('library'),
      pathUrl: await AsyncStorage.getItem('url'),
      showSuccess: false,
      showFailure: false,
      username: await AsyncStorage.getItem('username')
    });

    const random = new Date().getTime();
    const url = this.state.pathUrl + '/app/aspenRenew.php?library=' + this.state.pathLibrary + '&barcode=' + this.state.username + '&pin=' + this.state.password + '&itemId=all&rand=' + random;

    fetch(url)
      .then(res => res.json())
      .then(res => {
        let renewed = res.renewed;
        let message = res.message;
        
        // handle a failed renewal and then exit function
        if (renewed) {
          this.setState({
            isLoading: false,
            message: message,
            showSuccess: true
          });
        } else {
          this.setState({
            isLoading: false,
            message: message,
            showFailure: true
          });
        }

        return '';
      })
      .catch(error => {
        console.log("get data error from:" + url + " error:" + error);
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
      <View style={ Stylesheet.outerContainer }>
        { this.state.showSuccess || this.state.showFailure ?
          <View style={ Stylesheet.ilsMessageContainer }>
            <TouchableOpacity onPress={ () => this.props.navigation.goBack() }>
              {this.state.showSuccess ? <Text style={ Stylesheet.ilsSuccessMessage }>{ this.state.message }{'\n\n'}Your eProducts were cannot be renewed in the App at this time.{'\n\n'}Tap to close.</Text> : null}
              {this.state.showFailure ? <Text style={ Stylesheet.ilsFailMessage }>{ this.state.message }{'\n\n'}Your eProducts were cannot be renewed in the App at this time.{'\n\n'}Please check your list for items still due.{'\n\n'}Tap to close.</Text> : null}
            </TouchableOpacity>
          </View> : null }
      </View>
    );
  }
}