import React, {Component, setState, useState, useEffect} from 'react';
import { ActivityIndicator, Text, View, Platform, Alert } from 'react-native';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { createAppContainer, createSwitchNavigator } from 'react-navigation';
import { createStackNavigator } from 'react-navigation-stack';
import { createBottomTabNavigator } from 'react-navigation-tabs';
import Icon from 'react-native-vector-icons/Entypo';
import Stylesheet from './screens/Stylesheet';
import * as Location from 'expo-location';

// import helper files
import AccountDetails from './screens/AccountDetails';
import Discovery from './screens/Discovery';
import ItemDetails from './screens/ItemDetails';
import LibraryCard from './screens/LibraryCard';
import ListCKO from './screens/ListCKO';
import ListHold from './screens/ListHold';
import ListResults from './screens/ListResults';
import Login from './screens/Login';
import More from './screens/More';
import PlaceHold from './screens/PlaceHold';
import RenewAll from './screens/RenewAll';
import Search from './screens/Search';
import WhatsOn from './screens/WhatsOn';
import ContactUs from './screens/ContactUs';
import News from './screens/News';

// defines the Card tab and how it is handled
const CardTab = createStackNavigator(
  {
    Card: LibraryCard
  },
  {
    defaultNavigationOptions: {
      headerStyle: {
        backgroundColor: '#2F373A',
      },
      headerTintColor: '#FFFFFF',
      title: 'Your Library Card',
    },
  }
);

// defines the Search tab and how it is handled
const SearchTab = createStackNavigator(
  {
    Search: Search,
    PlaceHold: PlaceHold,
    ListResults: ListResults
  },
  {
    defaultNavigationOptions: {
      headerStyle: {
        backgroundColor: '#2F373A',
      },
      headerTintColor: '#FFFFFF',
      title: 'Search',
    },
  }
);

// defines the News tab and how it is handled
const MoreTab = createStackNavigator(
  {
    More: More,
    WhatsOn: WhatsOn,
    ContactUs: ContactUs,
    News: News
  },
  {
    defaultNavigationOptions: {
      headerStyle: {
        backgroundColor: '#2F373A',
      },
      headerTintColor: '#FFFFFF',
      title: 'More',
    },
  }
);

// defines the Account tab and how it is handled
const AccountTab = createStackNavigator(
  {
    Account: AccountDetails,
    ListCKO: ListCKO,
    ListHold: ListHold,
    ItemDetails: ItemDetails,
    RenewAll: RenewAll

  },
  {
    defaultNavigationOptions: {
      headerStyle: {
        backgroundColor: '#2F373A',
      },
      headerTintColor: '#FFFFFF',
      title: 'Your Account',
    },
  }
);

// defines the Account tab and how it is handled
const DiscoveryTab = createStackNavigator(
  {
    Discover: Discovery,
    PlaceHold: PlaceHold
  },
  {
    defaultNavigationOptions: {
      headerStyle: {
        backgroundColor: '#2F373A',
      },
      headerTintColor: '#FFFFFF',
      title: 'Discover',
    },
  }
);


// establishes the flow for the MainApp
const MainApp = createBottomTabNavigator(
  {
    Discover: DiscoveryTab,
    Search: SearchTab,
    Card: CardTab,
    Account: AccountTab,
    More: MoreTab
  },
  {
    defaultNavigationOptions: ({ navigation }) => ({
      tabBarIcon: ({ focused, horizontal, tintColor }) => {
        const { routeName } = navigation.state;
        let iconName;
        if (routeName === 'Discover') {
          iconName = 'map';
        } else if (routeName === 'Search') {
          iconName = 'magnifying-glass';
        } else if (routeName === 'Account') {
          iconName = 'list';
        } else if (routeName === 'More') {
          iconName = 'dots-three-horizontal';
        } else if (routeName === 'Card') {
          iconName = 'credit-card';
        }

        return <Icon name={iconName} size={25} color={tintColor} />;
      },
    }),
    tabBarOptions: {
      activeTintColor: '#f3a11e',
      inactiveTintColor: '#2F373A',
    },
  }
);

const MainNavigator = createStackNavigator({
  Home: { screen: MainApp },
},
{
  headerMode: 'none',
  navigationOptions: {
    headerVisible: false,
  }
});

// provides a login screen path to ensure that the account is logged into and can't be backed out of
const LoginNavigator = createStackNavigator({
  Home: { screen: Login },
},
 {
   headerMode: 'none',
   navigationOptions: {
     headerVisible: false,
   }
   });

export class getPermissionsScreen extends Component {
    constructor(props) {
        super(props);
        this._loadData();
    }


    componentWillUnmount() {
        this.mounted = false;
    }

    componentDidMount = async() => {
           let fetchingData = '0';
           await AsyncStorage.setItem('isFetchingData', fetchingData);
    }





    render() {

        return(
          <View style={ Stylesheet.activityIndicator }>
            <>
              <ActivityIndicator size='large' color='#2F373A' />
              <Text>Checking permissions...</Text>
            </>
          </View>
        )

    }

      _loadData = async() => {
          const doneFetch = await AsyncStorage.getItem('isFetchingData');
          this.props.navigation.navigate(doneFetch !==  '1' ? 'Permissions' : 'Auth')
      }



}

class AuthLoadingScreen extends Component {
  constructor (props) {
   super(props);
   this._loadData();
  }

  render() {

    return(
      <View style={ Stylesheet.activityIndicator }>
        <>
          <Text>Loading...</Text>
            <ActivityIndicator size='large' color='#2F373A' />
        </>
      </View>
    )
  }

  _loadData = async() => {
    const isLoggedIn = await AsyncStorage.getItem('isLoggedIn');
    this.props.navigation.navigate(isLoggedIn !== '1' ? 'Auth' : 'App')
  }
}

export default createAppContainer(createSwitchNavigator(
  {
    PermissionCheck: getPermissionsScreen,
    AuthLoading: AuthLoadingScreen,
    App: MainNavigator,
    Auth: LoginNavigator,
  },
  {
    initialRouteName: 'AuthLoading',
  }
));

export function GetGeolocation()  {
const [location, setLocation] = useState(null);
const [latitude, setLatitude] = useState(null);
const [longitude, setLongitude]= useState(null);
const [errorMsg, setErrorMsg] = useState(null);

useEffect(() => {
    (async () => {
    try {
      let { status } = await Location.requestForegroundPermissionsAsync();
      if (status !== 'granted') {
        setErrorMsg('Permission to access location was denied');
        Alert.alert(
            "User location not detected",
            "You haven't granted permission to detect your location.",
            [{ text: 'OK', onPress: () => console.log('OK Pressed') }])
      }
    try {
        let isLocationServicesEnabled = await Location.hasServicesEnabledAsync();
        let location = await Location.getCurrentPositionAsync({
            maximumAge: 60000, // only for Android
            accuracy: Location.Accuracy.Low});

        await AsyncStorage.setItem('latitude', JSON.stringify(location.coords.latitude));
        await AsyncStorage.setItem('longitude', JSON.stringify(location.coords.longitude));
        let fetchedData = '1';
        await AsyncStorage.setItem('isFetchingData', fetchedData);
      } catch (error) {
        console.log('getCurrentPositionAsync error',error);
        let location = await Location.getLastKnownPositionAsync();
            if (location == null) {
              Alert.alert(
                "Geolocation failed",
                "Your position could not be detected",
                [{ text: "OK", onPress: () => console.log("OK Pressed") }]
              );
            } else {
              await AsyncStorage.setItem('latitude', JSON.stringify(location.coords.latitude));
              await AsyncStorage.setItem('longitude', JSON.stringify(location.coords.longitude));
              let fetchedData = '1';
              await AsyncStorage.setItem('isFetchingData', fetchedData);
            }
        }
      } catch (error) {
       console.log('askAsync error',error);
      }
  }, []);
});

  let text = 'Making fresh cookies...';
  var userLatitude =  latitude
  var userLongitude =  longitude
  let greenhouseUrl = 'https://aspen-test.bywatersolutions.com/API/GreenhouseAPI?method=getLibraries&latitude=' + userLatitude + '&longitude=' + userLongitude

  if (errorMsg) {
    text = errorMsg;
  } else if (location) {
    text = greenhouseUrl
    global.greenhouseUrl = greenhouseUrl

  }

  return (<View><Text>{text}</Text></View>);

}
