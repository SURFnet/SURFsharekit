import axios from "axios";
import AppStorage, {StorageKey} from "../AppStorage";
import {Jsona} from "jsona";
import Toaster from "../toaster/Toaster";
import VerificationPopup from "../../verification/VerificationPopup";
import i18n from 'i18next';

class Api {
    static dataFormatter = new Jsona();
    static jsonApiGet(url, validate, onSuccess, onLocalFailure, onServerFailure, config = {}) {

        if(typeof config.cancelToken !== typeof undefined){
            config.cancelToken.cancel("Operation canceled due to new request.");
        }
        let cancelToken = axios.CancelToken.source();
        config.cancelToken = cancelToken.token;
        let requestConfig = this.getRequestConfig(config);

        axios.get(url, requestConfig)
            .then(function (response) {
                try {
                    response.meta = response.data.meta
                    response.links = response.data.links
                    response.data = Api.dataFormatter.deserialize(response.data);

                    validate(response);
                    onSuccess(response);
                } catch (error) {
                    onLocalFailure(error);
                }
            })
            .catch(function (error) {
                if(!axios.isCancel(error)) {
                    onServerFailure(error);
                }else{
                    console.log('cancelled');
                }
            })
            .then(function () {
                // always executed
            });
        return cancelToken;
    }

    static get(url, validate, onSuccess, onLocalFailure, onServerFailure, config = {}) {
        axios.get(url, this.getRequestConfig(config))
            .then(function (response) {
                try {
                    validate(response);
                    onSuccess(response);
                } catch (error) {
                    onLocalFailure(error);
                }
            })
            .catch(function (error) {
                onServerFailure(error);
            })
            .then(function () {
                // always executed
            });
    }

    static delete(url, validate, onSuccess, onLocalFailure, onServerFailure, config = {}, data = null) {
        const finalConfig = this.getRequestConfig(config);
        axios.delete(url, finalConfig)
            .then(function (response) {
                try {
                    validate(response);
                    onSuccess(response);
                } catch (error) {
                    onLocalFailure(error);
                }
            })
            .catch(function (error) {
                onServerFailure(error);
            })
            .then(function () {
                // always executed
            });
    }

    static post(url, validate, onSuccess, onLocalFailure, onServerFailure, config = {}, data = null) {
        const finalConfig = this.getRequestConfig(config);
        axios.post(url, data ? data : finalConfig.data, finalConfig)
            .then(function (response) {
                try {
                    validate(response);
                    onSuccess(response);
                } catch (error) {
                    onLocalFailure(error);
                }
            })
            .catch(function (error) {
                onServerFailure(error);
            })
            .then(function () {
                // always executed
            });
    }

    static patch(url, validate, onSuccess, onLocalFailure, onServerFailure, config = {}, data = null) {
        const finalConfig = this.getRequestConfig(config);

        axios.patch(url, data ? data : finalConfig.data, finalConfig)
            .then(function (response) {
                try {
                    validate(response);
                    onSuccess(response);
                } catch (error) {
                    onLocalFailure(error);
                }
            })
            .catch(function (error) {
                onServerFailure(error);
            })
            .then(function () {
                // always executed
            });
    }

    static getRequestConfig(config = {}) {
        let defaultConfig = {
            baseURL: process.env.REACT_APP_API_URL,
        };

        let defaultHeaders = {};

        const loggedInUser = AppStorage.get(StorageKey.USER);
        if (loggedInUser && loggedInUser.accessToken) {
            defaultHeaders["Authorization"] = 'Bearer ' + loggedInUser.accessToken;
        }

        defaultConfig.headers = defaultHeaders;
        return {
            ...defaultConfig,
            ...config,
            headers: {
                ...defaultConfig.headers,
                ...config.headers
            },
            validateStatus: function (statusCode) {
                return statusCode >= 200 && statusCode < 300

            }
        };
    }

    static downloadFileWithAccessToken(fileURL) {
        function onValidate() {
        }

        function onSuccess(response) {
            window.open(fileURL + '?accessToken=' + response.data.accessToken, '__blank')
        }

        function onServerFailure() {
            Toaster.showDefaultRequestError();
        }

        function onLocalFailure() {
            Toaster.showDefaultRequestError();
        }

        Api.get('generateAccessToken', onValidate, onSuccess, onLocalFailure, onServerFailure);
    }

    static downloadFileWithAccessTokenAndPopup(fileURL, fileTitle = null) {
        let downloadTitle = fileTitle ?? i18n.t('verification.download.title')
        let downloadSubtitle = downloadTitle === fileTitle ? i18n.t('verification.download.title') : ""

        function onValidate() {
        }

        function onSuccess(response) {
            VerificationPopup.show(downloadTitle, downloadSubtitle, () => {
                window.open(fileURL + '?accessToken=' + response.data.accessToken, '__blank')
            }, false, 5 * 1000 * 60)
        }

        function onServerFailure() {
            Toaster.showDefaultRequestError();
        }

        function onLocalFailure() {
            Toaster.showDefaultRequestError();
        }

        Api.get('generateAccessToken', onValidate, onSuccess, onLocalFailure, onServerFailure);
    }
}

export default Api;