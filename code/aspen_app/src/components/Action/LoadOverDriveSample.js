import React from 'react';
import { ButtonSpinner, Button, ButtonText } from '@gluestack-ui/themed';

// custom components and helper files
import { LibrarySystemContext, ThemeContext, UserContext } from '../../context/initialContext';
import { completeAction } from '../../util/recordActions';

export const LoadOverDriveSample = (props) => {
     const { user } = React.useContext(UserContext);
     const { library } = React.useContext(LibrarySystemContext);
     const [loading, setLoading] = React.useState(false);
     const { theme } = React.useContext(ThemeContext);

     console.log(props);

     return (
          <Button
               size="xs"
               minWidth="100%"
               maxWidth="100%"
               variant="link"
               mb="$1"
               borderWidth={1}
               borderColor={theme['colors']['primary']['500']}
               onPress={() => {
                    setLoading(true);
                    completeAction(props.id, props.type, user.id, props.formatId, props.sampleNumber, '', library.baseUrl, '', '', '', '').then((r) => {
                         setLoading(false);
                    });
               }}>
               {loading ? <ButtonSpinner color={theme['colors']['primary']['500']} /> : <ButtonText color={theme['colors']['primary']['500']}>{props.title}</ButtonText>}
          </Button>
     );
};