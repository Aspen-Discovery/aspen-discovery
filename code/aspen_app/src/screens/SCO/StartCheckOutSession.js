import React from 'react';
import { LanguageContext, LibraryBranchContext, LibrarySystemContext } from '../../context/initialContext';

export const StartCheckOutSession = () => {
     const { library } = React.useContext(LibrarySystemContext);
     const { location } = React.useContext(LibraryBranchContext);
     const { language } = React.useContext(LanguageContext);

     return null;
};