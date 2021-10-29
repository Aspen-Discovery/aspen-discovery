import React, { Component } from 'react';
import { ActivityIndicator, Linking, ScrollView, Text, TouchableOpacity, View } from 'react-native';
import Stylesheet from './Stylesheet';

export default class News extends Component {
  // establishes the title for the window
  static navigationOptions = { title: "What's On" };

  constructor() {
    super();

    this.state = {
      isLoading: true
    };
  }

  // handles the mount information, setting session variables, etc
  componentDidMount = async() =>{
    const url = 'https://www.ajaxlibrary.ca/app/moreDetails.php';

    fetch(url)
      .then(res => res.json())
      .then(res => {
        this.setState({
          dataWhatsOn: res.whatsOn,
          dataUniversal: res.universal,
          isLoading: false
        });
      })
      .catch(error => {
        console.log("get data error from:" + url + " error:" + error);
      });
  }

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

        <Text style={ Stylesheet.newsItem }>{ this.state.dataWhatsOn.blurb }</Text>

        <View style={ Stylesheet.btnContainer }>
          <TouchableOpacity style={ Stylesheet.btnFormat } onPress={ ()=>{ this.handleClick(this.state.dataWhatsOn.link) } }>
            <Text style={ Stylesheet.btnText }>{ this.state.dataWhatsOn.button }</Text>
          </TouchableOpacity>
        </View>

        <View style={ Stylesheet.btnContainer }>
          <TouchableOpacity style={ Stylesheet.btnFormat } onPress={ ()=>{ this.handleClick(this.state.dataUniversal.website) } }>
            <Text style={ Stylesheet.btnText }>Visit our Website</Text>
          </TouchableOpacity>
        </View>
        
        <View style={ Stylesheet.btnContainer }>
          <TouchableOpacity style={ Stylesheet.btnFormat } onPress={ ()=>{ this.handleClick(this.state.dataUniversal.catalogue) } }>
            <Text style={ Stylesheet.btnText }>Visit our Catalogue</Text>
          </TouchableOpacity>
        </View>

      </View>
      </ScrollView>
    );
  }
}
