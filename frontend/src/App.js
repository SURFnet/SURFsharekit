import React, {useEffect, useState} from 'react';
import {BrowserRouter as Router, Redirect, Route, Switch, useLocation} from "react-router-dom";
import "./sass/main.scss";
import Dashboard from "./dashboard/Dashboard";
import Login from "./login/Login";
import NotFound from "./errorpages/NotFound";
import Publications from "./publications/Publications";
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
import Projects from "./projects/Projects";
import {useGlobalState} from "./util/GlobalState";
import {mobileTabletMaxWidth} from "./Mixins";
import Publication from "./publication/Publication";
import ForbiddenFile from "./errorpages/ForbiddenFile";
import PrivacyStatement from "./privacystatement/PrivacyStatement";
import TextPage from "./components/textpage/TextPage";

function App() {

    const [isEnvironmentBannerVisible, setIsEnvironmentBannerVisible] = useGlobalState("isEnvironmentBannerVisible",false);

    useEffect(() => {
        if (process.env.REACT_APP_ENVIRONMENT_TYPE !== 'live') {
            setIsEnvironmentBannerVisible(true);
        }
    }, [])

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

    localStorage.setItem(`cached-version`, version)

    return (
        <Router>
            <div className={"App"}>
                <Switch>
                    <Redirect from="/:url*(/+)" to={window.location.pathname.slice(0, -1)} />
                    {appRoutes.map(r => <Route path={r.path} exact={r.exact} component={r.component}/>)}
                </Switch>
                {includeToastify()}
            </div>
        </Router>
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
        component: Publication
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
        path: '/projects',
        exact: true,
        component: Projects
    },
    {
        path: '/projects/:id',
        exact: true,
        component: Publication
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
        path: '/forbiddenfile',
        exact: false,
        component: ForbiddenFile
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
        path: '/privacy',
        exact: true,
        component: TextPage
    },
    {
        path: '/cookies',
        exact: true,
        component: TextPage
    },
    {
        path: '/',
        exact: false,
        component: NotFound
    }
]

export default App;
