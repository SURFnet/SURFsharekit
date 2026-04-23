import React, {useEffect} from 'react';
import {createBrowserRouter, RouterProvider, Outlet} from "react-router-dom";
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
import Removed from "./errorpages/Removed";
import Projects from "./projects/Projects";
import {useGlobalState} from "./util/GlobalState";
import Publication from "./publication/Publication";
import ForbiddenFile from "./errorpages/ForbiddenFile";
import TextPage from "./components/textpage/TextPage";
import Archive from "./archive/Archive";
import RootLayout from "./RootLayout";

const router = createBrowserRouter([
    {
        path: "/",
        element: <RootLayout><Outlet /></RootLayout>,
        children: [
            { path: "", element: <Dashboard /> },
            { path: "dashboard", element: <Dashboard /> },
            { path: "login", element: <Login /> },
            { path: "publications", element: <Publications /> },
            { path: "publications/:id", element: <Publication />},
            { path: "organisation", element: <Organisation /> },
            { path: "templates", element: <Templates /> },
            { path: "templates/:id", element: <EditTemplate /> },
            { path: "projects", element: <Projects /> },
            { path: "projects/:id", element: <Publication /> },
            { path: "reports", element: <Reports /> },
            { path: "groups/:id", element: <Group /> },
            { path: "profile/:id", element: <Profile /> },
            { path: "profile", element: <Profile /> },
            { path: "profiles", element: <Profiles /> },
            { path: "profiles/newprofile", element: <NewProfile /> },
            { path: "search/:searchQuery", element: <Search /> },
            { path: "forbidden", element: <Forbidden /> },
            { path: "trashcan", element: <TrashCan /> },
            { path: "onboarding", element: <Onboarding /> },
            { path: "unauthorized", element: <Unauthorized /> },
            { path: "removed", element: <Removed /> },
            { path: "forbiddenfile", element: <ForbiddenFile /> },
            { path: "archive", element: <Archive /> },
            { path: "*", element: <NotFound /> },
            { path: "notfound", element: <NotFound /> },
            { path: "cookies", element: <TextPage /> },
            { path: ":type/:id", element: <Dashboard /> },
            { path: 'public/:uuid', element: <PublicPage />}
        ]
    }
]);

function App() {
    const [, setIsEnvironmentBannerVisible] = useGlobalState("isEnvironmentBannerVisible",false);

    useEffect(() => {
        if (process.env.REACT_APP_ENVIRONMENT_TYPE !== 'live') {
            setIsEnvironmentBannerVisible(true);
        }
    }, [setIsEnvironmentBannerVisible])

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
        <>
            <RouterProvider router={router}>
                <div className={"App"} />
            </RouterProvider>
            {includeToastify()}
        </>
    );
}

export default App;