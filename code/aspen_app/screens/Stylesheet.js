import { StyleSheet } from 'react-native';

export default StyleSheet.create({
  accountInformation: {
    alignItems: 'center',
    backgroundColor: '#2f373a',
    borderRadius: 20,
    color: '#e1e1e1',
    fontSize: 16,
    justifyContent: 'center',
    margin: 10,
    padding: 10,
    textAlign: 'center',
    textAlignVertical: 'center',
  },
  accountText: {
    color: '#fff',
    fontSize: 14,
    paddingLeft: 20,
    width: '80%',
  },
  accountTextHeader: {
    color: '#fff',
    fontSize: 18,
    overflow: 'hidden', //needed for border radius to work on IOS
    textAlign: 'left',
    width: '80%',
  },
  activityIndicator: {
    alignItems: 'center',
    backgroundColor: '#e1e1e1',
    color: '#2f373a',
    flex: 1,
    justifyContent: 'center',
    width: '100%'
  },
  aspenLogoContainer: {
    alignItems: 'center',
    margin: 30,
  },
  author: {
    fontSize: 14,
    //paddingBottom: 25
  },
  barcodeText: {
    fontSize: 18,
    textAlign: 'center',
  },
  bookSummary: {
    fontWeight: 'bold',
  },
  btnContainer: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    textAlign: 'center'
  },
  btnFormat: {
    backgroundColor: '#00b5d4',
    borderRadius: 4,
    margin: 5,
    overflow: 'hidden', //needed for border radius to work on IOS
    padding: 15,
    //width: '80%',
  },
  btnFormatSmall: {
    backgroundColor: '#2f373a',
    borderRadius: 4,
    margin: 5,
    opacity: 0.75,
    overflow: 'hidden', //needed for border radius to work on IOS
    padding: 5,
    //width: '80%',
  },
  btnText: {
    color: '#2f373a',
    fontSize: 16,
    fontWeight: 'bold',
    textAlign: 'center',
  },
btnTextGray: {
  color: '#fff',
  fontSize: 16,
  textAlign: 'center',
},
  cardContainer: {
    alignItems: 'center',
    backgroundColor: '#ffffff',
    borderColor: '#00b5d4',
    borderRadius: 20,
    borderWidth: 1,
    height: 200,
    justifyContent: 'center',
    width: 250
  },
  clearSearch: {
    marginLeft: -35
  },
  clearText: {
    height: 25,
    paddingTop: 15,
    paddingLeft: 20,
    width: 25
  },
  coverArtImage: {
    alignItems: 'center',
    borderRadius: 20,
    height: 200,
    marginTop: 5,
    justifyContent: 'center',
    width: 135
  },
  discoverContainer: {
    alignItems: 'center',
    flex: 1,
    flexDirection: 'column',
    justifyContent: 'center',
    margin: 1,
    padding: 5,
  },
  discoveryPickerView: {
    backgroundColor: '#fff',
    borderRadius: 0,
    fontSize: 16,
    height: 50,
    marginBottom: 5,
    padding: 10,
    paddingRight: 20,
    width: '100%',
  },
  discoveryThumbnail: {
    alignItems: 'center',
    borderColor: '#f0f0f0',
    borderWidth: 1,
    height: 200,
    justifyContent: 'center',
    width: 121,
  },
  dueDate: {
    fontSize: 14,
    paddingBottom: 25
  },
  floatRight: {
    marginTop: 10,
    textAlign: 'right',
    textAlignVertical: 'center'
  },
  holdsResultsContainer: {
    alignSelf: 'center',
    backgroundColor: '#e1e1e1',
    flex: 1,
    width:'80%',
  },
  ilsFailMessage: {
    alignItems: 'center',
    backgroundColor: '#2f373a',
    borderRadius: 20,
    color: '#e1e1e1',
    fontSize: 16,
    height: 150,
    justifyContent: 'center',
    padding: 10,
    textAlign: 'center',
    textAlignVertical: 'center',
    width: 300
  },
  ilsMessageContainer: {
    alignItems: 'center',
    borderWidth: 0,
    bottom: 0,
    flex: 1,
    flexDirection: 'column',
    justifyContent: 'center',
    left: 0,
    position: 'absolute',
    right: 0,
    top: 0,
    zIndex: 10
  },
  ilsSuccessMessage: {
    alignItems: 'center',
    backgroundColor: '#2f373a',
    borderRadius: 20,
    color: '#e1e1e1',
    fontSize: 16,
    height: 150,
    justifyContent: 'center',
    padding: 10,
    textAlign: 'center',
    textAlignVertical: 'center',
    width: 300
  },
  input: {
    backgroundColor: '#2f373a',
    borderRadius: 4,
    color: '#fff',
    marginBottom: 10,
    marginTop: 10,
    padding: 10,
    width: '80%',
  },
  inputIOS: {
    borderColor: 'gray',
    borderRadius: 4,
    borderWidth: 1,
    color: 'black',
    fontSize: 16,
    paddingHorizontal: 25,
    paddingRight: 30, // to ensure the text is never behind the icon
    paddingVertical: 12,
  },
  inputAndroid: {
    borderColor: 'purple',
    borderRadius: 8,
    borderWidth: 0.5,
    color: 'black',
    fontSize: 16,
    paddingHorizontal: 10,
    paddingRight: 30, // to ensure the text is never behind the icon
    paddingVertical: 8,
  },
  libraryLogo: {
    borderRadius: 25,
    height: 175,
    width: 175
  },
  logoutButton: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginTop: 10,
    marginBottom: 10,
    textAlign: 'center'
  },
  modalSelector: {
    borderWidth: 1, 
    borderColor:'black',
    padding: 10, 
    height: 50,
    borderRadius: 4,
    fontWeight: 'bold',
    margin: 5,
     textAlign: 'center'
  },
  newsImage: {
    aspectRatio: 0.75,
    height: 150,
    resizeMode: 'contain',
    width: 150
  },
  newsItem: {
    backgroundColor: '#2f373a',
    borderRadius: 20,
    color: '#fff',
    fontSize: 16,
    marginBottom: 10,
    overflow: 'hidden', //needed for border radius to work on IOS
    padding: 15,
    textAlign: 'center',
    width: '80%',
  },
  newsItemText: {
    color: '#fff',
    fontSize: 16,
    marginBottom: 10,
    overflow: 'hidden', //needed for border radius to work on IOS
    padding: 15,
    textAlign: 'center',
  },
  outerContainer: {
    alignItems: 'center',
    backgroundColor: '#e1e1e1',
    flex: 1,
    justifyContent: 'center',
    
    width: '100%'
  },
  pickerViewLibrary: {
    backgroundColor: '#2f373a',
    borderRadius: 20,
    color: '#fff',
    fontSize: 12,
    height: 50,
    marginBottom: 5,
    padding: 10,
    paddingRight: 20,
    width: '80%',
  },  
  pickerViewSmall: {
    backgroundColor: '#fff',
    borderRadius: 20,
    fontSize: 12,
    height: 50,
    marginBottom: 5,
    padding: 10,
    paddingRight: 20,
    width: '60%',
  },
  readMore: {
    alignSelf: 'stretch',
    color: 'blue',
    marginRight: 30,
    textAlign: 'center' 
  },
  searchInput: {
    backgroundColor: '#2f373a',
    borderRadius: 20,
    color: '#fff',
    marginBottom: 10,
    marginTop: 10,
    padding: 10,
    width: '100%'
  },
  searchResultsContainer: {
    backgroundColor: '#e1e1e1',
    flex: 1
  },
  searchWrapper: {
    alignItems: 'center', 
    flexDirection: "row",
    width: '80%'
  },
  spacer: {
    fontSize: 18,
    paddingTop: 5
  },
  statusBar: {
    backgroundColor: '#1e90ff',
  },
  summaryDescription: {
    marginLeft: 30,
    marginRight: 30
  },
  title: {
    fontSize: 16,
    fontWeight: 'bold',
    padding: 10
  },
  welcomeContainer: {
    alignItems: 'center',
    marginTop: 10,
    marginBottom: 30,
  },
});
