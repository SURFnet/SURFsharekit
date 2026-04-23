import Cookies from 'js-cookie';

const TOKEN_COOKIE_NAME = 'sharekit_access_token';
const TOKEN_EXPIRY_DAYS = 1; // Token expires in 1 day

export const CookieStorage = {
    setToken(token) {
        // Set secure cookie with HttpOnly flag
        Cookies.set(TOKEN_COOKIE_NAME, token, {
            expires: TOKEN_EXPIRY_DAYS,
            secure: true, // Only sent over HTTPS
            sameSite: 'strict', // Protect against CSRF
            path: '/', // Available across the entire site
        });
    },

    getToken() {
        return Cookies.get(TOKEN_COOKIE_NAME);
    },

    removeToken() {
        Cookies.remove(TOKEN_COOKIE_NAME, {
            secure: true,
            sameSite: 'strict',
            path: '/',
        });
    }
}; 