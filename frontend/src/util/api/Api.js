import axios from "axios";
import AppStorage, {StorageKey} from "../AppStorage";
import {Jsona} from "jsona";
import Toaster from "../toaster/Toaster";
import VerificationPopup from "../../verification/VerificationPopup";
import i18n from 'i18next';
import {toast} from "react-toastify";
import { getCurrentConfig } from "../../config/environment";

function redirectToNotFound() {
    window.location = '/notfound'
}

function redirectToLogin() {
    window.location = "/login"
}

function handleApiError(error, onServerFailure) {
    if (axios.isCancel(error)) {
        console.log('Request cancelled:', error.message);
        return;
    }

    if (error.code !== "ECONNABORTED") {
        if (error.response) {
            const { status } = error.response;
            if (status === 404) {
                redirectToNotFound();
            } else if (status === 401) {
                redirectToLogin();
                toast.dismiss();
            } else {
                onServerFailure(error);
            }
        } else {
            // Handle network errors or other non-response errors
            onServerFailure(error);
        }
    }
}

class Api {
    static dataFormatter = new Jsona();

    static normalizeUrl(url) {
        // Remove trailing slashes and slashes before query parameters
        return url.replace(/\/+(?=\?|$)/g, '');
    }

    static jsonApiGet(url, validate, onSuccess, onLocalFailure, onServerFailure, config = {}) {
        if (typeof config.cancelToken !== typeof undefined) {
            config.cancelToken.cancel("Operation canceled due to new request.");
        }
        let cancelToken = axios.CancelToken.source();
        config.cancelToken = cancelToken.token;
        let requestConfig = this.getRequestConfig({
            ...config
        });
        const normalizedUrl = this.normalizeUrl(url);

        axios.get(normalizedUrl, requestConfig)
            .then(function (response) {
                try {
                    response.meta = response.data.meta
                    response.links = response.data.links
                    response.filters = response.data.filters
                    response.data = Api.dataFormatter.deserialize(response.data);

                    validate(response);
                    onSuccess(response);
                } catch (error) {
                    onLocalFailure(error);
                }
            })
            .catch(function (error) {
                handleApiError(error, onServerFailure);
            });
        return cancelToken;
    }

    static get(url, validate, onSuccess, onLocalFailure, onServerFailure, config = {}) {
        const requestConfig = this.getRequestConfig(config);
        const normalizedUrl = this.normalizeUrl(url);

        axios.get(normalizedUrl, requestConfig)
            .then(function (response) {
                try {
                    validate(response);
                    onSuccess(response);
                } catch (error) {
                    onLocalFailure(error);
                }
            })
            .catch(function (error) {
                handleApiError(error, onServerFailure);
            });
    }

    static delete(url, validate, onSuccess, onLocalFailure, onServerFailure, config = {}, data = null) {
        const finalConfig = this.getRequestConfig(config);
        const normalizedUrl = this.normalizeUrl(url);

        axios.delete(normalizedUrl, finalConfig)
            .then(function (response) {
                try {
                    validate(response);
                    onSuccess(response);
                } catch (error) {
                    onLocalFailure(error);
                }
            })
            .catch(function (error) {
                handleApiError(error, onServerFailure);
            });
    }

    static post(url, validate, onSuccess, onLocalFailure, onServerFailure, config = {}, data = null) {
        const finalConfig = this.getRequestConfig(config);
        const normalizedUrl = this.normalizeUrl(url);

        axios.post(normalizedUrl, data ? data : finalConfig.data, finalConfig)
            .then(function (response) {
                try {
                    validate(response);
                    onSuccess(response);
                } catch (error) {
                    onLocalFailure(error);
                }
            })
            .catch(function (error) {
                handleApiError(error, onServerFailure);
            });
    }

    static patch(url, validate, onSuccess, onLocalFailure, onServerFailure, config = {}, data = null) {
        const finalConfig = this.getRequestConfig(config);
        const normalizedUrl = this.normalizeUrl(url);

        axios.patch(normalizedUrl, data ? data : finalConfig.data, finalConfig)
            .then(function (response) {
                try {
                    validate(response);
                    onSuccess(response);
                } catch (error) {
                    onLocalFailure(error);
                }
            })
            .catch(function (error) {
                handleApiError(error, onServerFailure);
            });
    }

    static getRequestConfig(config = {}) {
        const envConfig = getCurrentConfig();
        
        let defaultConfig = {
            baseURL: envConfig.api.baseURL,
            withCredentials: envConfig.api.withCredentials,
        };

        // Add environment-specific headers
        if (process.env.NODE_ENV === 'development') {
            defaultConfig.headers = {
                ...defaultConfig.headers,
                'X-Environment': 'development'
            };
        }

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
            let url = new URL(fileURL)
            url.searchParams.append("accessToken", response.data.accessToken)
            window.open(url.toString(), '__blank')
        }

        function onServerFailure(error) {
            Toaster.showServerError(error);
        }

        function onLocalFailure(error) {
            Toaster.showServerError(error);
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
                var url = new URL(fileURL)
                url.searchParams.append("accessToken", response.data.accessToken)
                window.open(url.toString(), '__blank')
            }, false, 5 * 1000 * 60)
        }

        function onServerFailure(error) {
            Toaster.showServerError(error);
        }

        function onLocalFailure(error) {
            Toaster.showServerError(error);
        }

        Api.get('generateAccessToken', onValidate, onSuccess, onLocalFailure, onServerFailure);
    }
}

export default Api;