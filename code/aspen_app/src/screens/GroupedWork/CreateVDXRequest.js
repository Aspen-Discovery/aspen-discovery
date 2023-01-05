import _ from 'lodash';
import {useRoute, useNavigation} from '@react-navigation/native';
import {Box, Button, Checkbox, CheckIcon, FormControl, Input, Select, Text, TextArea} from 'native-base';
import React from 'react';
import {useQuery} from '@tanstack/react-query';
import {loadingSpinner} from '../../components/loadingSpinner';
import {submitVdxRequest} from '../../util/recordActions';
import {SafeAreaView} from 'react-native';
import {LibraryBranchContext, LibrarySystemContext, UserContext} from '../../context/initialContext';
import {getBasicItemInfo} from '../../util/api/item';
import {loadError} from '../../components/loadError';
import {getVdxForm} from '../../util/loadLibrary';

export const CreateVDXRequest = () => {
    const route = useRoute();
    const id = route.params.id;
    const {library} = React.useContext(LibrarySystemContext);
    const {location} = React.useContext(LibraryBranchContext);
    const [isLoading, setLoading] = React.useState(false);

    if (location.vdxFormId === "-1" || _.isNull(location.vdxLocation)) {
          return loadError('Location not setup for VDX', '');
     }

    const { data: item } = useQuery({
        queryKey: ['vdxItem', id, library.baseUrl],
        queryFn: () => getBasicItemInfo(id, library.baseUrl),
    });
    const vdxItem = item;

    const {status, data, error, isFetching} = useQuery({
        queryKey: ['vdxForm', location.vdxFormId, library.baseUrl],
        queryFn: () => getVdxForm(library.baseUrl, location.vdxFormId),
        enabled: !!vdxItem,
    });

    return <>{isLoading || status === 'loading' || isFetching ? loadingSpinner() : status === 'error' ? loadError(error, '') : <Request config={data} item={vdxItem}/>}</>;

};

const Request = (payload) => {
    const navigation = useNavigation();
    const {config, item} = payload;
    const {library} = React.useContext(LibrarySystemContext);

    let publisherValue = item.publisher;
    if(_.isArray(item.publisher)) {
        publisherValue = item.publisher[0];
    }

    const [title, setTitle] = React.useState(item.title);
    const [author, setAuthor] = React.useState(item.author);
    const [publisher, setPublisher] = React.useState(publisherValue);
    const [isbn, setIsbn] = React.useState(item.isbn);
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
            catalogKey: item.id ?? null,
            pickupLocation: pickupLocation ?? null,
        }
        await submitVdxRequest(library.baseUrl, request).then(result => {
            setIsSubmitting(false);
            navigation.goBack();
        });
    }

    const getIntroText = () => {
        const field = config.fields.introText;
        if(field.display === 'show') {
            return (
                <Text fontSize="sm" pb={3}>
                    {field.label}
                </Text>
            );
        }
        return null;
    }

    const getTitleField = () => {
        const field = config.fields.title;
        if(field.display === 'show') {
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
    }

    const getAuthorField = () => {
        const field = config.fields.author;
        if(field.display === 'show') {
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
    }

    const getPublisherField = () => {
        const field = config.fields.publisher;
        if(field.display === 'show') {
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
    }

    const getIsbnField = () => {
        const field = config.fields.isbn;
        if(field.display === 'show') {
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
    }

    const getFeeInformation = () => {
        const field = config.fields.feeInformationText;
        if(field.display === 'show') {
            return (
                <FormControl.HelperText>
                    {field.label}
                </FormControl.HelperText>
            );
        }
        return null;
    }

    const getAcceptFeeCheckbox = () => {
        const field = config.fields.acceptFee;
        if(field.display === 'show') {
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
    }

    const getNoteField = () => {
        const field = config.fields.note;
        if(field.display === 'show') {
            return (
                <FormControl my={2} isRequired={field.required}>
                    <FormControl.Label>{field.label}</FormControl.Label>
                    <TextArea
                        name={field.property}
                        value={note}
                        accessibilityLabel={field.description ?? field.label}
                        onChangeText={(text) => {
                            setNote(text)
                        }}
                    />
                </FormControl>
            );
        }
        return null;
    }

    const getPickupLocations = () => {
        const field = config.fields.pickupLocation;
        if(field.display === 'show' && _.isArray(field.options)) {
            const locations = field.options;
            return (
                <FormControl my={2} isRequired={field.required}>
                    <FormControl.Label>{field.label}</FormControl.Label>
                    <Select
                        name="pickupLocation"
                        defaultValue={pickupLocation}
                        accessibilityLabel={field.description ?? field.label}
                        _selectedItem={{
                            bg: 'tertiary.300',
                            endIcon: <CheckIcon size="5"/>,
                        }}
                        selectedValue={this.getValue('pickupLocation')}
                        onValueChange={(itemValue) => {
                            setPickupLocation(itemValue)
                        }}>
                        {locations.map((location, index) => {
                            return <Select.Item label={location.displayName} value={location.locationId}/>;
                        })}
                    </Select>
                </FormControl>
            );
        }
        return null;
    }

    const getCatalogKeyField = () => {
        const field = config.fields.catalogKey;
        if(field.display === 'show') {
            return (
                <FormControl my={2} isDisabled isRequired={field.required}>
                    <FormControl.Label>{field.label}</FormControl.Label>
                    <Input name={field.property} defaultValue={item.id} accessibilityLabel={field.description ?? field.label}/>
                </FormControl>
            );
        }
        return null;
    }

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
        )
    }

    return (
        <SafeAreaView>
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
        </SafeAreaView>
    )
}

/*
class CreateVDXRequestB extends Component {
     static contextType = userContext;

     constructor(props, context) {
          super(props, context);
          this.state = {
               isLoading: true,
               options: [],
               fields: [],
               request: {
                    title: this.props.route.params?.title ?? null,
                    author: this.props.route.params?.author ?? null,
                    publisher: this.props.route.params?.publisher ?? null,
                    isbn: null,
                    acceptFee: false,
                    maximumFeeAmount: 5.0,
                    note: null,
                    catalogKey: this.props.route.params?.catalogKey ?? null,
                    pickupLocation: this.props.route.params?.pickupLocation ?? null,
               },
          };
     }

     componentDidMount = async () => {
          const {navigation, route} = this.props;
          const vdxOptions = route.params?.vdxOptions ?? null;

          if (vdxOptions) {
               this.setState({
                    options: vdxOptions,
                    fields: _.values(vdxOptions['fields']),
                    isLoading: false,
               });
          } else {
               console.log('Error');
          }
     };

     updateValue = (field, value) => {
          let newValue = value;
          if (field === 'showMaximumFee') {
               newValue = this.formatCurrency(value);
          }

          const currentValues = [this.state.request];
          _.set(currentValues[0], field, newValue);
     };

     getValue = (field) => {
          return this.state.request[field];
     };

     returnField = (field, key) => {
          const currentFields = [this.state.fields];
          const matchedField = _.find(currentFields[0], _.matchesProperty('property', field));
          return matchedField[key];
     };

     formatCurrency = (value) => {
          return Number.parseFloat(value).toFixed(2);
     };

     getPlaceholder = (field) => {
          return this.props.route.params?.[field] ?? '';
     };

     onSubmit = async () => {
          await submitVdxRequest(this.context.library.baseUrl, this.state.request);
     };

     _renderField = (field) => {
          if (field.type === 'input' && field.display === 'show') {
               return (
                   <FormControl my={2} isRequired={field.required}>
                        <FormControl.Label>{field.label}</FormControl.Label>
                        <Input
                            name={field.property}
                            defaultValue={this.getPlaceholder(field.property)}
                            accessibilityLabel={field.description ?? field.label}
                            onChangeText={(value) => {
                                 this.updateValue(field.property, value);
                            }}
                        />
                   </FormControl>
               );
          }

          if (field.type === 'textarea' && field.display === 'show') {
               return (
                   <FormControl my={2} isRequired={field.required}>
                        <FormControl.Label>{field.label}</FormControl.Label>
                        <TextArea
                            name={field.property}
                            value={this.getValue(field.property)}
                            accessibilityLabel={field.description ?? field.label}
                            onChangeText={(text) => {
                                 this.updateValue(field.property, text);
                            }}
                        />

                        {field.property === 'title' ? <FormControl.HelperText>{this.returnField('feeInformationText', 'label')}</FormControl.HelperText> : null}
                   </FormControl>
               );
          }

          if (field.type === 'select' && field.display === 'show') {
               if (_.isArray(field.options)) {
                    const locations = field.options;
                    return (
                        <FormControl my={2} isRequired={field.required}>
                             <FormControl.Label>{field.label}</FormControl.Label>
                             <Select
                                 name="pickupLocation"
                                 defaultValue={this.getPlaceholder('pickupLocation')}
                                 accessibilityLabel={field.description ?? field.label}
                                 _selectedItem={{
                                      bg: 'tertiary.300',
                                      endIcon: <CheckIcon size="5"/>,
                                 }}
                                 selectedValue={this.getValue('pickupLocation')}
                                 onValueChange={(itemValue) => {
                                      this.updateValue('pickupLocation', itemValue);
                                 }}>
                                  {locations.map((location, index) => {
                                       return <Select.Item label={location.displayName} value={location.locationId}/>;
                                  })}
                             </Select>
                        </FormControl>
                    );
               }
          }

          if (field.type === 'number' && field.display === 'show') {
               return (
                   <FormControl my={2} isRequired={field.required}>
                        <FormControl.Label>{field.label}</FormControl.Label>
                        <Input
                            name={field.property}
                            defaultValue={this.getPlaceholder(field.property)}
                            accessibilityLabel={field.description ?? field.label}
                            keyboardType="decimal-pad"
                            onChangeText={(value) => {
                                 this.updateValue(field.property, value);
                            }}
                        />
                   </FormControl>
               );
          }

          if (field.type === 'checkbox' && field.display === 'show') {
               return (
                   <FormControl my={2} maxW="90%" isRequired={field.required}>
                        <Checkbox
                            name={field.property}
                            defaultValue={this.getPlaceholder(field.property)}
                            accessibilityLabel={field.description ?? field.label}
                            onChange={(value) => {
                                 this.updateValue(field.property, value);
                            }}
                            value>
                             {field.label}
                        </Checkbox>
                   </FormControl>
               );
          }

          if (field.type === 'number' && field.display === 'show') {
               return (
                   <FormControl my={2} isRequired={field.required}>
                        <FormControl.Label>{field.label}</FormControl.Label>
                        <Input defaultValue={5.0} keyboardType="decimal-pad"/>
                   </FormControl>
               );
          }

          if (field.property === 'catalogKey' && field.display === 'show') {
               return (
                   <FormControl my={2} isDisabled isRequired={field.required}>
                        <FormControl.Label>{field.label}</FormControl.Label>
                        <Input name={field.property} defaultValue={this.getPlaceholder(field.property)} accessibilityLabel={field.description ?? field.label}/>
                   </FormControl>
               );
          }
     };

     _renderHeader = () => {
          return (
              <Text fontSize="sm" pb={3}>
                   {this.state.options.fields.introText.label}
              </Text>
          );
     };

     _renderFooter = () => {
          return (
              <Button.Group pt={3}>
                   <Button
                       colorScheme="secondary"
                       onPress={() => {
                            this.props.navigation.goBack();
                            this.onSubmit();
                       }}>
                        {this.state.options.buttonLabel}
                   </Button>
                   <Button colorScheme="secondary" variant="outline" onPress={() => this.props.navigation.goBack()}>
                        Cancel
                   </Button>
              </Button.Group>
          );
     };

     render() {
          if (this.state.isLoading) {
               return loadingSpinner();
          }

          return (
              <SafeAreaView>
                   <Box safeArea={5}>
                        <FlatList data={this.state.fields} renderItem={({item}) => this._renderField(item)} keyExtractor={(item, index) => index.toString()} ListHeaderComponent={this._renderHeader} ListFooterComponent={this._renderFooter}/>
                   </Box>
              </SafeAreaView>
          );
     }
}*/