import React from 'react';
import ReactDOM from 'react-dom';
import './index.css';
import App from './App';
import * as serviceWorker from './serviceWorker';
import './i18n';
import PiwikPro from '@piwikpro/react-piwik-pro';


// load .env
require('dotenv').config();

// Analytics, only initialize on live environment
if(process.env.REACT_APP_ENVIRONMENT_TYPE === 'live') {
    PiwikPro.initialize(process.env.REACT_APP_PIWIK_PRO_CONTAINER_ID, process.env.REACT_APP_PIWIK_PRO_CONTAINER_URL);
}

ReactDOM.render(
    <React.StrictMode>
        <App/>
    </React.StrictMode>,
    document.getElementById('root')
);

// If you want your app to work offline and load faster, you can change
// unregister() to register() below. Note this comes with some pitfalls.
// Learn more about service workers: https://bit.ly/CRA-PWA
serviceWorker.unregister();
