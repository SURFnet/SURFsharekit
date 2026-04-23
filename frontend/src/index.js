import React from 'react';
import { createRoot } from 'react-dom/client';
import './index.css';
import App from './App';
import * as serviceWorker from './serviceWorker';
import './i18n';
import PiwikPro from '@piwikpro/react-piwik-pro';

// Analytics, only initialize on live environment
if(process.env.REACT_APP_ENVIRONMENT_TYPE === 'live') {
    PiwikPro.initialize(process.env.REACT_APP_PIWIK_PRO_CONTAINER_ID, process.env.REACT_APP_PIWIK_PRO_CONTAINER_URL);
}

// replace console.* for disable log on production
if (process.env.REACT_APP_ENVIRONMENT_TYPE === 'live' || process.env.NODE_ENV === 'production') {
    console.log = () => {}
    console.error = () => {}
    console.debug = () => {}
}

const root = createRoot(document.getElementById('root'));
root.render(
    <React.StrictMode>
        <App/>
    </React.StrictMode>
);

// If you want your app to work offline and load faster, you can change
// unregister() to register() below. Note this comes with some pitfalls.
// Learn more about service workers: https://bit.ly/CRA-PWA
serviceWorker.unregister();
