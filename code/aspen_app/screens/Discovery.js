import React, { Component } from 'react';
import { ActivityIndicator, FlatList, Image, Text, TouchableWithoutFeedback, View } from 'react-native';
import RNPickerSelect from 'react-native-picker-select';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { Chevron } from 'react-native-shapes';
import Stylesheet from './Stylesheet';
import Constants from "expo-constants";

export default class Discovery extends Component {
  constructor() {
    super();
    this.state = {
      browseCat: {},
      data: {},
      isLoading: true,
      hasError: false,
      limiter: '',
    };
  }

  // store the values into the state
  componentDidMount = async() =>{
    this.setState({
      pathLibrary: await AsyncStorage.getItem('library'),
      pathUrl: await AsyncStorage.getItem('url'),
      libraryName: await AsyncStorage.getItem('libraryName'),
    });

    // store version
    const version = Constants.manifest.version;
    await AsyncStorage.setItem('version', version);

    this.grabBrowseCategory();
    this.grabListData(this.state.limiter);
  }


  // Grab the browse categories
  grabBrowseCategory = () => {
    const url = this.state.pathUrl + '/app/aspenBrowseCategory.php?library=' + this.state.pathLibrary;
    console.log(url);

    fetch(url)
      .then(res => res.json())
      .then(res => {
        this.setState({
          browseCat: res.Items,
          limiter: res.default
        });
      })
      .catch(error => {
        console.log("get data error from:" + url + " error:" + error);
        this.setState({
          error: "get data error from:" + url + " error:" + error,
          isLoading: false,
          hasError: true
        });
      });
  }


  // call the list data in a function in order to reload when changes happen
  grabListData = (restriction) => {
    this.setState({ 
      isLoading: true,
      limiter: restriction
    });
    
    const url = this.state.pathUrl + '/app/aspenDiscover.php?library=' + this.state.pathLibrary + '&limiter=' + restriction;
    console.log(url);

    fetch(url)
      .then(res => res.json())
      .then(res => {
        this.setState({
          data: res.Items,
          isLoading: false,
        });
      })
      .catch(error => {
        console.log("get data error from:" + url + " error:" + error);
        this.setState({
          error: "get data error from:" + url + " error:" + error,
          isLoading: false,
          hasError: true
        });
      });
  }

  // route user to page that allows them to place a hold
  onPressItem = (item) => { 
    this.props.navigation.navigate( 'PlaceHold', { item }, ); 
  }

  // renders the items on the screen
  renderNativeItem = (item) => {
    return (
      <TouchableWithoutFeedback onPress={ () => this.props.navigation.navigate('PlaceHold', {item } )}>
        <View style={ Stylesheet.discoverContainer }>
          <Image style={ Stylesheet.discoveryThumbnail } source={{ uri: item.image }} />
        </View>
      </TouchableWithoutFeedback>
    );
  }

  getHeader = () => {
    const searchPlaceHolder = { label: 'Click to discover more!', value: '', color: '#9EA0A4', };

    return (
      <View style={ Stylesheet.discoveryPickerView }>
        <RNPickerSelect
          onValueChange = { (itemValue) =>  this.grabListData(itemValue) }
          items = { this.state.browseCat.map(obj => (
            {
               label: obj.title,
               value: obj.reference,
            }))}
          placeholder = { searchPlaceHolder }
          style = { Stylesheet.pickerViewSmall, Platform.OS === 'ios' ? Stylesheet.inputIOS : Stylesheet.inputAndroid }
          useNativeAndroidPickerStyle = { false }
          value = { this.state.limiter }
          Icon = { () => {
            return <Chevron size={1.5} color="gray" style={ Stylesheet.floatRight } />;
          }}
        /> 
      </View>
    );
  }

  render() {
    if ((this.state.isLoading) && (this.state.limiter == '')) {
      return (

        <View style={ Stylesheet.activityIndicator }>
          <>
            <Text>Loading titles from {this.state.libraryName}...</Text>
            <ActivityIndicator size='large' color='#272362' />
          </>
        </View>
      );
    } else if (this.state.isLoading) {
          return (

            <View style={ Stylesheet.activityIndicator }>
              <>
                <Text>Loading titles for { this.state.limiter } category from {this.state.libraryName}...</Text>
                <ActivityIndicator size='large' color='#272362' />
              </>
            </View>
          );
    } else if (this.state.hasError) {
      return (
        <View style={ Stylesheet.activityIndicator }>
          <Text>{this.state.error}.</Text>
        </View>
      );
    }

    return (
      <View>
        <FlatList 
          data={ this.state.data } 
          keyExtractor={ (item, index) => index.toString() } 
          numColumns={ 3 } 
          renderItem={( {item} ) => this.renderNativeItem(item) } 
          ListHeaderComponent={ this.getHeader }
          extraData={ this.state }
        />
      </View>
    );
  }
}