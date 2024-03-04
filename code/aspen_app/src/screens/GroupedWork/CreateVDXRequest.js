import _ from 'lodash';
import { useRoute, useNavigation } from '@react-navigation/native';
import { Box, Button, Checkbox, CheckIcon, FormControl, Input, Select, Text, TextArea, ScrollView } from 'native-base';
import React from 'react';
import { Platform } from 'react-native';
import { useQuery } from '@tanstack/react-query';
import { loadingSpinner } from '../../components/loadingSpinner';
import { submitVdxRequest } from '../../util/recordActions';
import { HoldsContext, LibraryBranchContext, LibrarySystemContext, UserContext } from '../../context/initialContext';
import { loadError } from '../../components/loadError';
import { getVdxForm } from '../../util/loadLibrary';
import { reloadProfile } from '../../util/api/user';
import { reloadHolds } from '../../util/loadPatron';

export const CreateVDXRequest = () => {
     const route = useRoute();
     const id = route.params.id;
     const title = route.params.workTitle ?? null;
     const author = route.params.author ?? null;
     const publisher = route.params.publisher ?? null;
     const isbn = route.params.isbn ?? null;
     const oclcNumber = route.params.oclcNumber ?? null;
     const { library } = React.useContext(LibrarySystemContext);
     const { location } = React.useContext(LibraryBranchContext);
     const { updateUser } = React.useContext(UserContext);

     if (location.vdxFormId === '-1' || _.isNull(location.vdxLocation)) {
          return loadError('Location not setup for VDX', '');
     }

     const { status, data, error, isFetching } = useQuery({
          queryKey: ['vdxForm', location.vdxFormId, library.baseUrl],
          queryFn: () => getVdxForm(library.baseUrl, location.vdxFormId),
     });

     return <>{status === 'loading' || isFetching ? loadingSpinner() : status === 'error' ? loadError('Error', '') : <Request config={data} workId={id} workTitle={title} workAuthor={author} workPublisher={publisher} workIsbn={isbn} workOclcNumber={oclcNumber} />}</>;
};

const Request = (payload) => {
     const navigation = useNavigation();
     const { config, workId, workTitle, workOclcNumber, workPublisher, workAuthor, workIsbn } = payload;
     const { library } = React.useContext(LibrarySystemContext);
     const { updateUser } = React.useContext(UserContext);
     const { updateHolds } = React.useContext(HoldsContext);

     let publisherValue = '';
     if (!_.isUndefined(workPublisher)) {
          publisherValue = workPublisher;
          if (_.isArray(workPublisher)) {
               publisherValue = workPublisher[0];
          }
     }

     const [title, setTitle] = React.useState(workTitle);
     const [author, setAuthor] = React.useState(workAuthor);
     const [publisher, setPublisher] = React.useState(publisherValue);
     const [isbn, setIsbn] = React.useState(workIsbn);
     const [note, setNote] = React.useState('');
     const [acceptFee, setAcceptFee] = React.useState(false);
     const [pickupLocation, setPickupLocation] = React.useState();

     const [isSubmitting, setIsSubmitting] = React.useState(false);

     const handleSubmission = async () => {
          const request = {
               title: title ?? null,
               author: author ?? null,
               publisher: publisher ?? null,
               isbn: isbn ?? null,
               acceptFee: acceptFee,
               note: note ?? null,
               catalogKey: workId ?? null,
               oclcNumber: workOclcNumber ?? null,
               pickupLocation: pickupLocation ?? null,
          };
          await submitVdxRequest(library.baseUrl, request).then(async (result) => {
               setIsSubmitting(false);
               if (result.success) {
                    navigation.goBack();
                    await reloadHolds(library.baseUrl).then((result) => {
                         updateHolds(result);
                    });
                    await reloadProfile(library.baseUrl).then((result) => {
                         updateUser(result);
                    });
               }
          });
     };

     const getIntroText = () => {
          const field = config.fields.introText;
          if (field.display === 'show') {
               return (
                    <Text fontSize="sm" pb={3}>
                         {field.label}
                    </Text>
               );
          }
          return null;
     };

     const getTitleField = () => {
          const field = config.fields.title;
          if (field.display === 'show') {
               return (
                    <FormControl my={2} isRequired={field.required}>
                         <FormControl.Label>{field.label}</FormControl.Label>
                         <Input
                              name={field.property}
                              defaultValue={title}
                              accessibilityLabel={field.description ?? field.label}
                              onChangeText={(value) => {
                                   setTitle(value);
                              }}
                         />
                    </FormControl>
               );
          }
          return null;
     };

     const getAuthorField = () => {
          const field = config.fields.author;
          if (field.display === 'show') {
               return (
                    <FormControl my={2} isRequired={field.required}>
                         <FormControl.Label>{field.label}</FormControl.Label>
                         <Input
                              name={field.property}
                              defaultValue={author}
                              accessibilityLabel={field.description ?? field.label}
                              onChangeText={(value) => {
                                   setAuthor(value);
                              }}
                         />
                    </FormControl>
               );
          }
          return null;
     };

     const getPublisherField = () => {
          const field = config.fields.publisher;
          if (field.display === 'show') {
               return (
                    <FormControl my={2} isRequired={field.required}>
                         <FormControl.Label>{field.label}</FormControl.Label>
                         <Input
                              name={field.property}
                              defaultValue={publisher}
                              accessibilityLabel={field.description ?? field.label}
                              onChangeText={(value) => {
                                   setPublisher(value);
                              }}
                         />
                    </FormControl>
               );
          }
          return null;
     };

     const getIsbnField = () => {
          const field = config.fields.isbn;
          if (field.display === 'show') {
               return (
                    <FormControl my={2} isRequired={field.required}>
                         <FormControl.Label>{field.label}</FormControl.Label>
                         <Input
                              name={field.property}
                              defaultValue={isbn}
                              accessibilityLabel={field.description ?? field.label}
                              onChangeText={(value) => {
                                   setIsbn(value);
                              }}
                         />
                    </FormControl>
               );
          }
          return null;
     };

     const getFeeInformation = () => {
          const field = config.fields.feeInformationText;
          if (field.display === 'show' && !_.isEmpty(field.label)) {
               return <Text bold>{field.label}</Text>;
          }
          return null;
     };

     const getAcceptFeeCheckbox = () => {
          const field = config.fields.acceptFee;
          if (field.display === 'show') {
               return (
                    <FormControl my={2} maxW="90%" isRequired={field.required}>
                         <Checkbox
                              name={field.property}
                              accessibilityLabel={field.description ?? field.label}
                              onChange={(value) => {
                                   setAcceptFee(value);
                              }}
                              value>
                              {field.label}
                         </Checkbox>
                    </FormControl>
               );
          }
          return null;
     };

     const getNoteField = () => {
          const field = config.fields.note;
          if (field.display === 'show') {
               return (
                    <FormControl my={2} isRequired={field.required}>
                         <FormControl.Label>{field.label}</FormControl.Label>
                         <TextArea
                              name={field.property}
                              value={note}
                              accessibilityLabel={field.description ?? field.label}
                              onChangeText={(text) => {
                                   setNote(text);
                              }}
                         />
                    </FormControl>
               );
          }
          return null;
     };

     const getPickupLocations = () => {
          const field = config.fields.pickupLocation;
          if (field.display === 'show' && _.isArray(field.options)) {
               const locations = field.options;
               return (
                    <FormControl my={2} isRequired={field.required}>
                         <FormControl.Label>{field.label}</FormControl.Label>
                         <Select
                              isReadOnly={Platform.OS === 'android'}
                              name="pickupLocation"
                              defaultValue={pickupLocation}
                              accessibilityLabel={field.description ?? field.label}
                              _selectedItem={{
                                   bg: 'tertiary.300',
                                   endIcon: <CheckIcon size="5" />,
                              }}
                              selectedValue={pickupLocation}
                              onValueChange={(itemValue) => {
                                   setPickupLocation(itemValue);
                              }}>
                              {locations.map((location, index) => {
                                   return <Select.Item label={location.displayName} value={location.locationId} />;
                              })}
                         </Select>
                    </FormControl>
               );
          }
          return null;
     };

     const getCatalogKeyField = () => {
          const field = config.fields.catalogKey;
          if (field.display === 'show') {
               return (
                    <FormControl my={2} isDisabled isRequired={field.required}>
                         <FormControl.Label>{field.label}</FormControl.Label>
                         <Input name={field.property} defaultValue={catalogKey} accessibilityLabel={field.description ?? field.label} />
                    </FormControl>
               );
          }
          return null;
     };

     const getActions = () => {
          return (
               <Button.Group pt={3}>
                    <Button
                         colorScheme="secondary"
                         isLoading={isSubmitting}
                         isLoadingText={config.buttonLabelProcessing}
                         onPress={() => {
                              setIsSubmitting(true);
                              handleSubmission();
                         }}>
                         {config.buttonLabel}
                    </Button>
                    <Button colorScheme="secondary" variant="outline" onPress={() => navigation.goBack()}>
                         Cancel
                    </Button>
               </Button.Group>
          );
     };

     return (
          <ScrollView>
               <Box safeArea={5}>
                    {getIntroText()}
                    {getTitleField()}
                    {getAuthorField()}
                    {getPublisherField()}
                    {getIsbnField()}
                    {getNoteField()}
                    {getFeeInformation()}
                    {getAcceptFeeCheckbox()}
                    {getPickupLocations()}
                    {getCatalogKeyField()}
                    {getActions()}
               </Box>
          </ScrollView>
     );
};