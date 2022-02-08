import React from 'react';
import {BrowserRouter as Router, Route, Switch} from "react-router-dom";
import "./sass/main.scss";
import Dashboard from "./dashboard/Dashboard";
import Login from "./login/Login";
import NotFound from "./errorpages/NotFound";
import Publications from "./publications/Publications";
import EditPublication from "./editpublication/EditPublication";
import {Slide, ToastContainer} from "react-toastify";
import 'react-toastify/dist/ReactToastify.css';
import './util/toaster/toaster.scss'
import Organisation from "./organisation/Organisation";
import Profile from "./profile/Profile";
import Group from "./group/Group";
import Forbidden from "./errorpages/Forbidden";
import Templates from "./templates/Templates";
import EditTemplate from "./edittemplate/EditTemplate";
import Search from "./search/Search";
import Reports from "./reports/Reports";
import TrashCan from "./trashcan/TrashCan";
import Onboarding from "./onboarding/Onboarding";
import Unauthorized from "./errorpages/Unauthorized";
import PublicPage from "./publicpage/PublicPage";
import Profiles from "./profiles/Profiles";
import NewProfile from "./profiles/newprofile/NewProfile";
import {version} from "./appversion.json"
import Removed from "./errorpages/Removed";

function App() {

    function includeToastify() {
        return <ToastContainer
            transition={Slide}
            position="top-center"
            autoClose={5000}
            hideProgressBar={true}
            closeOnClick={true}
            closeButton={false}
            newestOnTop={false}
            rtl={false}
            draggable={false}
            limit={3}
        />
    }

    return (
        [
            <Router>
                <link
                    href="https://fonts.googleapis.com/css2?family=Nunito:ital,wght@0,700;0,800;0,900;1,700;1,800;1,900&family=Open+Sans:ital,wght@0,300;0,400;0,700;1,300;1,400;1,700&display=swap"
                    rel="stylesheet"/>

                <div className={"App " + (hasEnvironmentBanner() ? 'with-environment-banner' : '')}>
                    <Switch>
                        {appRoutes.map(r => <Route path={r.path} exact={r.exact} component={r.component}/>)}
                    </Switch>
                    {includeToastify()}
                </div>
            </Router>]
    );
}

export const appRoutes = [
    {
        path: '/public/:uuid',
        exact: true,
        component: PublicPage
    },
    {
        path: '/dashboard',
        exact: true,
        component: Dashboard
    },
    {
        path: '/publications',
        exact: true,
        component: Publications
    },
    {
        path: '/publications/:id',
        exact: true,
        component: EditPublication
    },
    {
        path: '/organisation',
        exact: true,
        component: Organisation
    },
    {
        path: '/templates',
        exact: true,
        component: Templates
    },
    {
        path: '/templates/:id',
        exact: true,
        component: EditTemplate
    },
    {
        path: '/reports',
        exact: true,
        component: Reports
    },
    {
        path: '/groups/:id',
        exact: true,
        component: Group
    },
    {
        path: '/profile/:id',
        exact: true,
        component: Profile
    },
    {
        path: '/profile',
        exact: true,
        component: Profile
    },
    {
        path: '/profiles',
        exact: true,
        component: Profiles
    },
    {
        path: '/profiles/newprofile',
        exact: true,
        component: NewProfile
    },
    {
        path: '/search/:searchQuery',
        exact: true,
        component: Search
    },
    {
        path: '/trashcan/',
        exact: false,
        component: TrashCan
    },
    {
        path: '/login',
        exact: false,
        component: Login
    },
    {
        path: '/onboarding',
        exact: false,
        component: Onboarding
    },
    {
        path: '/unauthorized',
        exact: false,
        component: Unauthorized
    },
    {
        path: '/forbidden',
        exact: false,
        component: Forbidden
    },
    {
        path: '/notfound',
        exact: false,
        component: NotFound
    },
    {
        path: '/removed',
        exact: false,
        component: Removed
    },
    {
        path: '/:type/:id',
        exact: false,
        component: Dashboard
    },
    {
        path: '/',
        exact: true,
        component: Dashboard
    },
    {
        path: '/',
        exact: false,
        component: NotFound
    },
]

let usingEnvironmentBanner = false;

export function EnvironmentBanner() {
    if (process.env.REACT_APP_ENVIRONMENT_TYPE === 'dev') {
        usingEnvironmentBanner = true;
        return <div className={'environment-banner development'}>
            DEVELOPMENT ENVIRONMENT
        </div>
    } else if (process.env.REACT_APP_ENVIRONMENT_TYPE === 'test') {
        usingEnvironmentBanner = true;
        return <div className={'environment-banner test'}>
            TEST ENVIRONMENT
        </div>
    } else if (process.env.REACT_APP_ENVIRONMENT_TYPE === 'staging') {
        usingEnvironmentBanner = true;
        return <div className={'environment-banner staging'}>
            STAGING ENVIRONMENT
        </div>
    } else if (process.env.REACT_APP_ENVIRONMENT_TYPE === 'acceptance') {
        usingEnvironmentBanner = true;
        return <div className={'environment-banner acceptance'}>
            ACCEPTANCE ENVIRONMENT
        </div>
    } else {
        return <div/>
    }
}

export function hasEnvironmentBanner() {
    return usingEnvironmentBanner === true;
}

export default App;
