// NavigationProvider.jsx
import React, {createContext, useContext, useEffect} from 'react';
import {UNSAFE_NavigationContext, useLocation, useNavigate} from 'react-router-dom';

const NavigationContext = createContext();

export const NavigationProvider = ({ children }) => {
    const navigate = useNavigate();
    return (
        <NavigationContext.Provider value={navigate}>
            {children}
        </NavigationContext.Provider>
    );
};

export const useNavigation = () => {
    return useContext(NavigationContext);
};