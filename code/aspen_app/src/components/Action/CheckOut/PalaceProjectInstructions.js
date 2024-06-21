import React from 'react';
import { Text, CheckIcon, Heading, HStack, VStack, Badge, BadgeText, FlatList, Button, ButtonGroup, ButtonText, ButtonIcon, Box, Icon, Center, AlertDialog, AlertDialogContent, AlertDialogHeader, AlertDialogBody, AlertDialogFooter, AlertDialogBackdrop, Select, SelectTrigger, SelectInput, SelectIcon, SelectPortal, SelectBackdrop, SelectContent, SelectDragIndicatorWrapper, SelectDragIndicator, SelectItem, SelectScrollView, ScrollView } from '@gluestack-ui/themed';
import { MaterialIcons } from '@expo/vector-icons';
import { LanguageContext, LibrarySystemContext, ThemeContext } from '../../../context/initialContext';
import { loadingSpinner } from '../../loadingSpinner';

export const PalaceProjectInstructions = () => {
     const { library } = React.useContext(LibrarySystemContext);
     const { language } = React.useContext(LanguageContext);
     const [isLoading, setLoading] = React.useState(false);
     const { theme } = React.useContext(ThemeContext);

     if (isLoading) {
          return loadingSpinner();
     }

     return (
          <ScrollView>
               <Box p="$5">{library.palaceProjectInstructions ? <Text>{library.palaceProjectInstructions}</Text> : null}</Box>
          </ScrollView>
     );
};