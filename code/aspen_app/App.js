import React, {Component} from 'react';
import { ActivityIndicator, Text, View } from 'react-native';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { createAppContainer, createSwitchNavigator } from 'react-navigation';
import { createStackNavigator } from 'react-navigation-stack';
import { createBottomTabNavigator } from 'react-navigation-tabs';
import Icon from 'react-native-vector-icons/Entypo';
import Stylesheet from './screens/Stylesheet';

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
});

class AuthLoadingScreen extends Component {
  constructor (props) {
    super(props);
    this._loadData();
  }

  render() {
    return(
      <View style={ Stylesheet.activityIndicator }>
        <>
          <Text>Setting up the auth screen</Text>
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
    AuthLoading: AuthLoadingScreen,
    App: MainNavigator,
    Auth: LoginNavigator,
  },
  {
    initialRouteName: 'AuthLoading',
  }
));
