import _ from 'lodash';
import { Box, useColorModeValue } from 'native-base';
import React from 'react';
import { Platform } from 'react-native';
import MapView, { Marker, PROVIDER_GOOGLE } from 'react-native-maps';

const mapStyle = [
     {
          featureType: 'poi.business',
          stylers: [
               {
                    visibility: 'off',
               },
          ],
     },
     {
          featureType: 'poi.medical',
          stylers: [
               {
                    visibility: 'off',
               },
          ],
     },
     {
          featureType: 'poi.park',
          elementType: 'labels.text',
          stylers: [
               {
                    visibility: 'off',
               },
          ],
     },
];
const mapStyleDark = [
     {
          elementType: 'geometry',
          stylers: [
               {
                    color: '#242f3e',
               },
          ],
     },
     {
          elementType: 'labels.text.fill',
          stylers: [
               {
                    color: '#746855',
               },
          ],
     },
     {
          elementType: 'labels.text.stroke',
          stylers: [
               {
                    color: '#242f3e',
               },
          ],
     },
     {
          featureType: 'administrative.locality',
          elementType: 'labels.text.fill',
          stylers: [
               {
                    color: '#d59563',
               },
          ],
     },
     {
          featureType: 'poi',
          elementType: 'labels.text.fill',
          stylers: [
               {
                    color: '#d59563',
               },
          ],
     },
     {
          featureType: 'poi.business',
          stylers: [
               {
                    visibility: 'off',
               },
          ],
     },
     {
          featureType: 'poi.medical',
          stylers: [
               {
                    visibility: 'off',
               },
          ],
     },
     {
          featureType: 'poi.park',
          elementType: 'geometry',
          stylers: [
               {
                    color: '#263c3f',
               },
          ],
     },
     {
          featureType: 'poi.park',
          elementType: 'labels.text',
          stylers: [
               {
                    visibility: 'off',
               },
          ],
     },
     {
          featureType: 'poi.park',
          elementType: 'labels.text.fill',
          stylers: [
               {
                    color: '#6b9a76',
               },
          ],
     },
     {
          featureType: 'road',
          elementType: 'geometry',
          stylers: [
               {
                    color: '#38414e',
               },
          ],
     },
     {
          featureType: 'road',
          elementType: 'geometry.stroke',
          stylers: [
               {
                    color: '#212a37',
               },
          ],
     },
     {
          featureType: 'road',
          elementType: 'labels.text.fill',
          stylers: [
               {
                    color: '#9ca5b3',
               },
          ],
     },
     {
          featureType: 'road.highway',
          elementType: 'geometry',
          stylers: [
               {
                    color: '#746855',
               },
          ],
     },
     {
          featureType: 'road.highway',
          elementType: 'geometry.stroke',
          stylers: [
               {
                    color: '#1f2835',
               },
          ],
     },
     {
          featureType: 'road.highway',
          elementType: 'labels.text.fill',
          stylers: [
               {
                    color: '#f3d19c',
               },
          ],
     },
     {
          featureType: 'transit',
          elementType: 'geometry',
          stylers: [
               {
                    color: '#2f3948',
               },
          ],
     },
     {
          featureType: 'transit.station',
          elementType: 'labels.text.fill',
          stylers: [
               {
                    color: '#d59563',
               },
          ],
     },
     {
          featureType: 'water',
          elementType: 'geometry',
          stylers: [
               {
                    color: '#17263c',
               },
          ],
     },
     {
          featureType: 'water',
          elementType: 'labels.text.fill',
          stylers: [
               {
                    color: '#515c6d',
               },
          ],
     },
     {
          featureType: 'water',
          elementType: 'labels.text.stroke',
          stylers: [
               {
                    color: '#17263c',
               },
          ],
     },
];

const DisplayMap = (data) => {
     const location = data.data;

     const mapColorMode = useColorModeValue('light', 'dark');

     const markerRef = React.useRef(null);
     const mapRef = React.useRef(null);

     const onRegionChangeComplete = () => {
          if (markerRef && markerRef.current && markerRef.current.showCallout) {
               markerRef.current.showCallout();
          }
     };

     const onMapReadyHandler = React.useCallback(() => {
          if (Platform.OS !== 'android') {
               mapRef?.current?.fitToElements(false);
          } else {
               mapRef?.current?.fitToSuppliedMarkers(['library'], {
                    edgePadding: {
                         top: 50,
                         right: 50,
                         bottom: 50,
                         left: 50,
                    },
                    animated: true,
               });
          }
     }, [mapRef]);

     if (_.isNumber(location.latitude) && location.latitude !== 0 && location.longitude !== 0) {
          if (Platform.OS === 'ios') {
               return (
                    <Box pt={2} pb={2}>
                         <MapView
                              onRegionChangeComplete={onRegionChangeComplete}
                              ref={mapRef}
                              onMapReady={onMapReadyHandler}
                              provider={PROVIDER_GOOGLE}
                              camera={{
                                   center: {
                                        latitude: location.latitude,
                                        longitude: location.longitude,
                                   },
                                   pitch: 1,
                                   zoom: 16,
                              }}
                              paddingAdjustmentBehavior="never"
                              loadingEnabled={true}
                              scrollEnabled={false}
                              showsPointsOfInterest={false}
                              customMapStyle={mapColorMode === 'light' ? mapStyle : mapStyleDark}
                              style={{ height: 180, width: '100%' }}>
                              <Marker
                                   coordinate={{
                                        latitude: location.latitude,
                                        longitude: location.longitude,
                                   }}
                                   title={location.displayName}
                                   description={location.address}
                                   ref={markerRef}
                                   identifier="library"
                                   anchor={{ x: 0.5, y: 0.25 }}
                                   centerOffset={{ x: 0.5, y: 0.25 }}
                              />
                         </MapView>
                    </Box>
               );
          } else {
               return (
                    <Box pt={2} pb={2}>
                         <MapView
                              onRegionChangeComplete={onRegionChangeComplete}
                              ref={mapRef}
                              onMapReady={onMapReadyHandler}
                              provider={PROVIDER_GOOGLE}
                              initialRegion={{
                                   latitude: location.latitude,
                                   longitude: location.longitude,
                                   latitudeDelta: 0.005,
                                   longitudeDelta: 0,
                              }}
                              region={{
                                   latitude: location.latitude,
                                   longitude: location.longitude,
                                   latitudeDelta: 0.005,
                                   longitudeDelta: 0,
                              }}
                              paddingAdjustmentBehavior="never"
                              loadingEnabled={true}
                              scrollEnabled={false}
                              zoomEnabled={false}
                              pitchEnabled={false}
                              showsPointsOfInterest={false}
                              customMapStyle={mapColorMode === 'light' ? mapStyle : mapStyleDark}
                              style={{ height: 180, width: '100%' }}>
                              <Marker
                                   coordinate={{
                                        latitude: location.latitude,
                                        longitude: location.longitude,
                                   }}
                                   title={location.displayName}
                                   description={location.address}
                                   ref={markerRef}
                                   identifier="library"
                                   anchor={{ x: 0.5, y: 0.25 }}
                                   centerOffset={{ x: 0.5, y: 0.25 }}
                              />
                         </MapView>
                    </Box>
               );
          }
     }

     return null;
};

export default DisplayMap;