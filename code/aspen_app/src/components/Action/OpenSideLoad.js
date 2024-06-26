import { Button, ButtonText, ButtonSpinner } from '@gluestack-ui/themed';
import React from 'react';
import { ThemeContext } from '../../context/initialContext';

// custom components and helper files
import { openSideLoad } from '../../util/recordActions';

export const OpenSideLoad = (props) => {
     const [loading, setLoading] = React.useState(false);
     const { theme } = React.useContext(ThemeContext);

     return (
          <Button
               minWidth="100%"
               maxWidth="100%"
               size="md"
               _text={{
                    padding: 0,
                    textAlign: 'center',
               }}
               style={{
                    flex: 1,
                    flexWrap: 'wrap',
               }}
               bgColor={theme['colors']['primary']['500']}
               onPress={async () => {
                    setLoading(true);
                    await openSideLoad(props.url).then((r) => setLoading(false));
               }}>
               {loading ? <ButtonSpinner color={theme['colors']['primary']['500-text']} /> : <ButtonText color={theme['colors']['primary']['500-text']}>{props.title}</ButtonText>}
          </Button>
     );
};