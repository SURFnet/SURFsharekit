import i18n from "i18next";
import { initReactI18next } from "react-i18next";
import translationEN from './resources/locales/en/translation.json'
import translationNL from './resources/locales/nl/translation.json'

const resources = {
    en: {
        translation: translationEN
    },
    nl: {
        translation: translationNL
    }
};

i18n
    .use(initReactI18next) // passes i18n down to react-i18next
    .init({
        resources,
        lng: "nl",
        debug: true,
        fallbackLng: "en",
        interpolation: {
            escapeValue: false // react already safes from xss
        }
    });

export default i18n;