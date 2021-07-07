import React, { Component } from 'react';
import { ActivityIndicator, Image, Text, TouchableOpacity, View } from 'react-native';
import AsyncStorage from '@react-native-async-storage/async-storage';
import Stylesheet from './Stylesheet';

export default class ItemDetails extends Component {
  // establishes the title for the window
  static navigationOptions = { title: 'Renewal Options' };
  
  constructor() {
    super();
    this.state = { isLoading: true };
  }

  // handles the mount information, setting session variables, etc
  componentDidMount = async() =>{
    this.setState({
      isLoading: false,
      renewalDate: '',
      password: await AsyncStorage.getItem('password'),
      pathLibrary: await AsyncStorage.getItem('library'),
      pathUrl: await AsyncStorage.getItem('url'),
      showSuccess: false,
      showFailure: false,
      username: await AsyncStorage.getItem('username')
    });
  }

  // function that calls the external PHP script to renew the items
  onPressItem = (barcode) => {
    this.setState({
      isLoading: true
    });
    const random = new Date().getTime();
    const url = this.state.pathUrl + '/app/aspenRenew.php?library=' + this.state.pathLibrary + '&barcode=' + this.state.username + '&pin=' + this.state.password + '&itemId=' + barcode + '&rand=' + random;

    fetch(url)
      .then(res => res.json())
      .then(res => {
        let renewed = res.renewed;
        
        // handle a failed renewal and then exit function
        if (renewed) {
          this.setState({
            isLoading: false,
            showSuccess: true
          });
        } else {
          this.setState({
            isLoading: false,
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

    const title     = this.props.navigation.state.params.item.key;
    const dateDue   = this.props.navigation.state.params.item.dateDue;
    const barcode   = this.props.navigation.state.params.item.barcode;
    const author    = this.props.navigation.state.params.item.author;
    const itemImage = this.props.navigation.state.params.item.thumbnail;
    var renewText   = "Renew " + title;

    if (barcode == null) {
      renewText = "Sorry, but this eProduct cannot be renewed through the app.";
    }

    return (
      <View style={ Stylesheet.outerContainer }>
        <Image style={ Stylesheet.coverArtImage } source={{ uri: itemImage }} />
        <Text style={ Stylesheet.title }>Title: { title } {'\n'}By: { author }</Text>
        <Text style={ Stylesheet.dueDate }>Date Due: { dateDue }</Text>
        <View style={ Stylesheet.btnContainer }>
          <TouchableOpacity style={ Stylesheet.btnFormat } onPress={ () => this.onPressItem(barcode) }>
            <Text style={ Stylesheet.btnText }>{ renewText }</Text>
          </TouchableOpacity>
        </View>
        
        { this.state.showSuccess || this.state.showFailure ?
          <View style={ Stylesheet.ilsMessageContainer }>
            <TouchableOpacity onPress={ () => this.props.navigation.goBack() }>
              {this.state.showSuccess ? <Text style={ Stylesheet.ilsSuccessMessage }>This Item was successfully renewed.{'\n\n'}Tap to close.</Text> : null}
              {this.state.showFailure ? <Text style={ Stylesheet.ilsFailMessage }>This Item cannot be renewed and is still due due on or before { dateDue }.{'\n\n'}Tap to close.</Text> : null}
            </TouchableOpacity>
          </View> : null }
      </View>
    );
  }
}