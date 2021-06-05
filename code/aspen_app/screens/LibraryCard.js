import React, { Component } from 'react';
import { ActivityIndicator, Image, Text, View } from 'react-native';
import AsyncStorage from '@react-native-async-storage/async-storage';
import Barcode from "react-native-barcode-expo";
import Stylesheet from './Stylesheet';

export default class LibraryCard extends Component {
  // done to allow the page title to change down in the code
  static navigationOptions = ({ navigation }) => ({
    title: typeof(navigation.state.params)==='undefined' || typeof(navigation.state.params.title) === 'undefined' ? 'Your Library Card': navigation.state.params.title,
  });

  constructor(props) {
    super(props);
    this.state = { 
      isLoading: true
    }
  }

  // store the values into the state
  componentDidMount = async() =>{
    const username   = await AsyncStorage.getItem('username');
    const patronName = await AsyncStorage.getItem('patronName');

    this.setState({
      isLoading: false,
      patronName: patronName,
      username: username
    });

    // change the page name to personalize it
    this.props.navigation.setParams({ title: this.state.patronName + "'s Library Card" });
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
        <View style={ Stylesheet.aspenLogoContainer }>
            <Image source={ require('../assets/aspenLogo.png') } style={ Stylesheet.newsImage } />
            <Text style={ Stylesheet.barcodeText }>Barcode:{'\n'}{ this.state.username }</Text>
        </View>

        <View style={ Stylesheet.cardContainer }>
         <Barcode value={ this.state.username } format="CODE128" />
        </View>  
      </View>
    );
  }
}