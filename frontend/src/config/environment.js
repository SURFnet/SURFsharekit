const isDevelopment = process.env.NODE_ENV === 'development';

const config = {
    // API Configuration
    api: {
        baseURL: process.env.REACT_APP_API_URL,
        withCredentials: true,
        // Development specific settings
        development: {
            // If you're using a local HTTPS certificate
            secure: process.env.REACT_APP_USE_HTTPS === 'true',
            // Local development domain
            cookieDomain: 'localhost',
            // CORS settings for development
            cors: {
                origin: 'http://localhost:3000',
                credentials: true
            }
        },
        // Production settings
        production: {
            secure: true,
            cookieDomain: process.env.REACT_APP_COOKIE_DOMAIN,
            cors: {
                origin: process.env.REACT_APP_FRONTEND_URL,
                credentials: true
            }
        }
    },
    
    // Cookie Configuration
    cookie: {
        // Common settings
        name: 'sharekit_access_token',
        path: '/',
        sameSite: 'strict',
        httpOnly: true,
        // Environment specific settings
        development: {
            secure: process.env.REACT_APP_USE_HTTPS === 'true',
            domain: 'localhost'
        },
        production: {
            secure: true,
            domain: process.env.REACT_APP_COOKIE_DOMAIN
        }
    }
};

// Get current environment settings
export const getCurrentConfig = () => {
    const env = isDevelopment ? 'development' : 'production';
    return {
        api: {
            ...config.api,
            ...config.api[env]
        },
        cookie: {
            ...config.cookie,
            ...config.cookie[env]
        }
    };
};

export default config; 