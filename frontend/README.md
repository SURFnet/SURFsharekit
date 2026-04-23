# SURF Sharekit Frontend

## Project Overview
The SURF Sharekit Frontend is a React-based single-page application that provides a web interface for managing and customizing templates and publications within the SURF Sharekit ecosystem.

The application communicates with a backend JSON:API service for operations like listing templates, editing template metadata, and handling authentication/authorization.

**Core technologies**

- Frontend framework: `react` (18.3.1), `react-dom` (18.3.1)
- Application tooling: `react-scripts` (5.0.1)
- Language: JavaScript (ES6+)
- Package manager: `npm`
- Styling: `sass` (1.85.0), `styled-components` (6.1.15)
- Routing: `react-router` (7.5.3), `react-router-dom` (7.5.3)
- Forms and validation: `react-hook-form` (7.54.2)
- HTTP client: `axios` (1.9.0) with `axios-retry` (4.5.0)
- State and async utilities: `rxjs` (7.8.2), `debounce-promise` (3.1.2)
- Internationalisation (i18n): `i18next` (24.2.2), `react-i18next` (15.4.1)
- Charts and visualisation: `recharts` (2.15.1), `react-minimal-pie-chart` (9.1.0)
- Tables and drag-and-drop: `react-table` (7.8.0), `react-sortable-hoc` (2.0.0), `array-move` (4.0.0)
- Icons: `@fortawesome/fontawesome-svg-core` (6.7.2), `@fortawesome/free-solid-svg-icons` (6.7.2), `@fortawesome/free-brands-svg-icons` (6.7.2), `@fortawesome/react-fontawesome` (0.2.2)
- Analytics: `@piwikpro/react-piwik-pro` (2.2.1)
- Notifications/UI helpers: `react-toastify` (11.0.5), `sweetalert2` (11.21.0), `sweetalert2-react-content` (5.1.0), `react-spinners` (0.15.0)
- Dates: `moment` (2.30.1), `react-multi-date-picker` (4.5.2)
- File uploads: `react-dropzone` (14.3.8)
- HTTP sanitisation: `dompurify` (3.2.5)
- Environment/config: `dotenv` (16.4.7), `dotenv-expand` (12.0.1)
- Auth/session helpers: `js-cookie` (3.0.5), `@types/js-cookie` (3.0.6)
- Infrastructure: `aws-sdk` (2.1692.0)
- Build/docker: `Dockerfile`, `docker-compose.yml`, `Makefile`

## Architectural highlights
The frontend is implemented as a React SPA with a conventional component-based structure and a clear separation between view components, routing, and API access.

Key architectural aspects:

- **Routing and navigation**
    - Uses `react-router` and `react-router-dom` to provide client-side routing, including authenticated routes such as templates and dashboard pages.
    - A fallback `.htaccess` rule ensures all non-file requests are redirected to `index.html`, enabling proper SPA routing when deployed behind Apache.

- **Data fetching and API integration**
    - `axios` is used for HTTP requests, with `axios-retry` to automatically retry transient failures.
    - Backend follows JSON:API patterns (for example, endpoints like `templates`, `templates/:id`, usage of `include`, `filter`, `sort` query parameters, and `application/vnd.api+json` content type for PATCH requests).
    - Error handling centralised via helper utilities that show user-facing notifications (for example, toaster notifications) and navigate to login on `401` responses.

- **State and forms**
    - Local component state via React hooks (`useState`, `useEffect`).
    - Form handling via `react-hook-form`, including validation state and programmatic updates, particularly for dynamic template meta fields.
    - Some flows construct "patch" payloads for templates, mapping from form field values to JSON:API-compliant structures.

- **Internationalisation**
    - User-facing text and labels are keyed for translation through `i18next` and `react-i18next`.
    - Dynamic titles and labels for template sections are resolved based on the current language code (for example, English/Nederlands variants for titles and info text).

- **Styling and layout**
    - Uses `sass` for global styles and `styled-components` for scoped, component-level styling where desirable.
    - Flexbox-based layouts (for example, `flex-column` and `flex-row` classes) define responsive form and page layouts.

- **Analytics and tracking**
    - `@piwikpro/react-piwik-pro` is integrated for analytics and event tracking, allowing page-level and event-level instrumentation.

- **Resilience and UX**
    - Loading indicators (for example, fullscreen blockers and per-page spinners) are present during data fetching and saving.
    - Reusable UI primitives for buttons, fields, and notification toasts encourage consistent UX across the app.

## Directory Tour
High-level overview of the main directories:

- `src/`
    - Contains the main application source code, including:
        - React components for pages (for example, templates list and edit views) and shared UI elements.
        - Routing configuration and page layout components.
        - API service wrappers for JSON:API backend integration.
        - Helpers and utilities for formatting, i18n integration, and global page methods.
- `public/`
    - Static assets that are served as-is (HTML entry point, icons, images, etc.).
- `.gitlab-ci/`
    - CI/CD configuration and supporting scripts for GitLab-based pipelines.
- `.qodana/` and `qodana.yml`
    - Configuration for JetBrains Qodana static analysis and quality checks.
- `node_modules/`
    - Installed JavaScript dependencies (created by `npm install`).
- Root configuration files:
    - `.babelrc` for Babel transpilation configuration.
    - `.env` and `.env.example` for environment variables.
    - `.gitlab-ci.yml` for pipeline definition.
    - `docker-compose.yml` and `Dockerfile` for containerisation.
    - `Makefile` exposing common development commands.
    - `sonar-project.properties` for SonarQube/SonarCloud analysis configuration.
    - `qodana.sarif.json` for static analysis results export.
    - `CHANGELOG.md` and `README.md` for documentation.

## Prerequisites
To build and run the SURF Sharekit Frontend locally, you need:

- **Node.js**
    - A current LTS version of Node.js is recommended (for example, 18.x or newer) that is compatible with `react-scripts` 5.x.
- **npm**
    - `npm` is the supported package manager (installed with Node.js).
- **Backend/API access**
    - Network access to the Sharekit backend API instance (development, staging, or production).
    - A valid base URL for the API (see the Configuration section).
    - Valid user credentials or test accounts for accessing protected routes such as templates.

## Configuration
Configuration is primarily managed through environment variables defined in `.env` files and referenced at build/runtime.

A template file `.env.example` is provided at the repository root and should be used as the basis for creating your local `.env` configuration.

Typical variables (exact names may vary, see `.env.example`):

- **API configuration**
    - `REACT_APP_API_BASE_URL`
        - Base URL for the backend JSON:API endpoints, e.g. `REACT_APP_API_BASE_URL=https://localhost:8080/api`.
    - `REACT_APP_API_TIMEOUT`
        - Optional HTTP timeout for API requests in milliseconds.
- **Authentication-related configuration**
    - Variables for login redirect URLs, token storage, or SSO integration (for example, `REACT_APP_AUTH_BASE_URL`, `REACT_APP_LOGIN_REDIRECT_PATH`), depending on the backend’s auth strategy.
- **Analytics**
    - `REACT_APP_PIWIK_SITE_ID`, `REACT_APP_PIWIK_CONTAINER_ID`, or similar keys for configuring `@piwikpro/react-piwik-pro`.
- **Environment and build**
    - `NODE_ENV` set to `development`, `test`, or `production`.
    - Optional `PORT` variable to control the dev server port.

At minimum, you must configure the API base URL variable (for example, `REACT_APP_API_BASE_URL`) to point at a reachable backend instance for the application to function correctly.

Whenever you modify `.env`, you should restart the dev server so that `react-scripts` picks up the new configuration.

## Running Locally
Follow these steps to run the application locally:

1. **Create your `.env` file**
    - Copy the example configuration:
        - `cp .env.example .env`
    - Open `.env` in your editor and set required values, such as:
        - `REACT_APP_API_BASE_URL=https://localhost:8080/api`
    - Ensure the API base URL points to a running backend instance (development/staging).

2. **Install dependencies**
   - `npm install`

4. **Run the development server**
    - With `npm`:
        - `npm run start`
    - The default URL is typically `http://localhost:3000` (unless `PORT` is overridden).

## API Documentation & Tooling
The frontend communicates with a JSON:API-compliant backend that exposes resources such as templates and publications.

Typical patterns visible in the integration include:

- Query parameters:
    - `include` for related resources, e.g. `include=partOf`
    - `filter[...]` for filtering by attributes, e.g. `filter[allowCustomization]=1`, `filter[title][LIKE]=%query%`
    - `sort` for sorting by one or more fields, using `-` for descending (for example, `sort=-updatedAt,title`)
- Resource endpoints:
    - `GET /templates` for listing templates.
    - `GET /templates/:id` for fetching a single template (with optional `include`).
    - `PATCH /templates/:id` for updating template title and field metadata, using media type `application/vnd.api+json`.

To effectively work with the backend:

- **API documentation**
    - Refer to the backend’s API documentation (for example, Swagger/OpenAPI or custom docs) as provided by the backend team.
    - Typical URL patterns might include something like `https://<backend-host>/swagger` or `https://<backend-host>/docs`, but you should confirm the exact endpoints with your team or infrastructure docs.

- **Request collection tooling**
    - A Postman or Bruno collection is recommended for exploring and testing backend endpoints used by this frontend.
    - Check your team’s internal documentation or repository (for example, a `docs/` directory or a dedicated API repo) for:
        - A Postman collection file (for example, `Sharekit.postman_collection.json`).
        - A Bruno workspace (for example, `sharekit.bru`).
    - Import the collection and configure environment variables such as `baseUrl`, `clientId`, or `token` according to the backend environment you are targeting.

## Testing
The testing setup is aligned with `react-scripts` 5.x and the usual Create React App patterns.

**Test types**

- Unit tests for React components and utilities.
- Potential integration tests around page flows and form handling.
- Snapshot tests may be used for UI components where appropriate.

**Framework and tooling**

- Test runner: Jest (bundled via `react-scripts`).
- React testing utilities: `@testing-library/react` and related helpers (commonly used with CRA-based setups; confirm in `package.json`).
- CLI wrapper: `react-scripts test`.

**Running tests**

- Install dependencies (if not already done):
    - `npm install`
- Run the test suite in watch mode:
    - `npm test`
- For single-run CI mode:
    - `CI=true npm test`
- Depending on project configuration, additional NPM scripts may be available for coverage or specific test suites (for example, `npm run test:unit` or `npm run test:integration`). Check `package.json` for the authoritative list.

**Testing in CI**

- CI/CD (via `.gitlab-ci.yml`) will typically:
    - Install dependencies.
    - Run the test suite in non-interactive mode.
    - Optionally run linters, static analysis (`qodana`), and code quality checks (SonarQube via `sonar-project.properties`).

## Project specific information (OPTIONAL)
- **Templates management**
    - The application includes dedicated pages for listing templates and editing template metadata.
    - Template edit flows:
        - Load template data and related `steps` and `templateSections`.
        - Group fields into sections, each containing multiple metadata fields.
        - Allow editing of field titles, information text, enabled/required flags, and other attributes.
        - Construct JSON:API-compatible PATCH payloads to update template metadata in bulk.
    - Permissions:
        - Template objects carry a `permissions` structure (for example, `permissions.canEdit`) that controls whether fields can be edited or are read-only.

- **Authentication and navigation**
    - If a `user` object is not present in the application’s storage, protected pages (for example, the templates listing) redirect to the login page with a `redirect` query parameter (for example, `login?redirect=templates`).
    - When the backend returns HTTP 401, the frontend triggers a redirect to the login page to re-authenticate.

- **Error and loading handling**
    - API errors are surfaced through a toaster-like component for user-friendly error messages.
    - Loading indicators are displayed at both page-level (for example, while fetching template details) and global-level (for example, fullscreen spinner while submitting and patching a template).

## Packages (OPTIONAL)
Below is a non-exhaustive grouping of notable npm packages used in this project and their current versions (as available in the configuration):

- **Core React and tooling**
    - `react` (18.3.1)
    - `react-dom` (18.3.1)
    - `react-scripts` (5.0.1)
    - `history` (5.3.0)
    - `path-browserify` (1.0.1)
    - `babel-plugin-transform-remove-console` (6.9.4)
    - `babel-plugin-styled-components` (2.1.4)

- **Routing and navigation**
    - `react-router` (7.5.3)
    - `react-router-dom` (7.5.3)
    - `react-router-prompt` (0.8.0)

- **State, async, and utilities**
    - `rxjs` (7.8.2)
    - `debounce-promise` (3.1.2)
    - `jsona` (1.12.1)
    - `array-move` (4.0.0)

- **HTTP and API**
    - `axios` (1.9.0)
    - `axios-retry` (4.5.0)
    - `aws-sdk` (2.1692.0)

- **Forms and validation**
    - `react-hook-form` (7.54.2)

- **Styling and layout**
    - `sass` (1.85.0)
    - `styled-components` (6.1.15)

- **Internationalisation**
    - `i18next` (24.2.2)
    - `react-i18next` (15.4.1)

- **UI components and UX**
    - `react-table` (7.8.0)
    - `react-sortable-hoc` (2.0.0)
    - `react-minimal-pie-chart` (9.1.0)
    - `recharts` (2.15.1)
    - `react-select` (5.10.0)
    - `react-dropzone` (14.3.8)
    - `react-multi-date-picker` (4.5.2)
    - `react-toastify` (11.0.5)
    - `sweetalert2` (11.21.0)
    - `sweetalert2-react-content` (5.1.0)
    - `react-spinners` (0.15.0)

- **Icons**
    - `@fortawesome/fontawesome-svg-core` (6.7.2)
    - `@fortawesome/free-solid-svg-icons` (6.7.2)
    - `@fortawesome/free-brands-svg-icons` (6.7.2)
    - `@fortawesome/react-fontawesome` (0.2.2)

- **Analytics and tracking**
    - `@piwikpro/react-piwik-pro` (2.2.1)

- **Security and cookies**
    - `dompurify` (3.2.5)
    - `js-cookie` (3.0.5)
    - `@types/js-cookie` (3.0.6)

- **Environment and configuration**
    - `dotenv` (16.4.7)
    - `dotenv-expand` (12.0.1)

This list should be cross-checked with `package.json` for any additional libraries and exact version constraints. When adding new dependencies, follow existing conventions (for example, using `npm install <package> --save` and updating any relevant documentation or architectural notes).