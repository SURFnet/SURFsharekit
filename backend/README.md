#Project configuration

###
This project is based on Silverstripe, for more information see https://docs.silverstripe.org/en/4/getting_started/

###Docker setup

1. Set local php version to 7.4.* 
   1. Php version depends on the silverstripe php version in the composer file, currently it's: "php": ">=7.2.24"
   1. This is done by altering your PATH variable and downloading the correct php version via e.g. MAMP.
2. Set correct .env variables (copy .env.example to .env and set variables)
3. in '/public': Copy + paste .htaccess.example and name it .htaccess  
4. Run composer update
5. Run 'docker compose up --build' in a terminal window from PHPStorm
   1. If a newer silverstripe version is used (see composer.json or composer.lock ), modify the dockerfile by setting 'FROM brettt89/silverstripe-web:7.1-apache' to the correct version. For available versions, see: https://hub.docker.com/r/brettt89/silverstripe-web/
6. Go to localhost:8080/dev/build?flush=1
7. Login with admin // password from .env SS_DEFAULT_ADMIN_USERNAME and SS_DEFAULT_ADMIN_PASSWORD

#ENV variables

#### GENERAL
Set base URL, f.e. http://localhost:8081

```SS_BASE_URL="<base_url>"```

#### DB credentials
Set database credentials

`````
SS_DATABASE_CLASS="MySQLDatabase"
SS_DATABASE_SERVER="localhost"
SS_DATABASE_USERNAME="<db_user>"
SS_DATABASE_PASSWORD="<db_password>"
SS_DATABASE_NAME="<db_name>"
`````

#### Email credentials (Amazon SES)
Set mail credentials, (only amazon SES supported) and set admin_email to a general e-mail from address
```
SES_SMTP_USERNAME="<ses_smtp_user>"
SES_SMTP_PASSWORD="<ses_smtp_password>"
ADMIN_EMAIL="<admin_email>"
```

#### Log settings (include filename in path)
Set local log path
```
LOCAL_LOG_PATH="<local_log_path>"
LOG_LEVEL = "debug"
```
#### Environment settings
Set general environment settings and default admin username and password
```
SS_ENVIRONMENT_TYPE="<env>"
APPLICATION_ENVIRONMENT="<env>>"

SS_DEFAULT_ADMIN_USERNAME="<ss_user>"
SS_DEFAULT_ADMIN_PASSWORD="<ss_password>"

APACHE_RUN_USER="apache"
```
#### S3 credentials
Set object store credentials
```
AWS_REGION = "<aws_region>"
AWS_BUCKET_NAME = "<aws_bucket>"

AWS_ACCESS_KEY_ID = "<aws_key>"
AWS_SECRET_ACCESS_KEY = "<aws_secret>"
```

#### Migration database (only for migration testing)
```
MIGRATION_DB_HOST = "<migration_db_host>"
MIGRATION_DB_USER = "<migration_db_user>"
MIGRATION_DB_PASSWORD = "<migration_db_password>"
MIGRATION_DB_DATABASE = "<migration_db_database>"
```

#### SURFconext
Set SURFconext credentials
```
CONEXT_URL = "<conext_url>"
CONEXT_CLIENT_ID = "<conext_client_id>"
CONEXT_CLIENT_SECRET = "<conext_client_secret>"
```

#### Client URL
Set Base URL of Sharekit Client (for generation of direct URLs)
```
FRONTEND_BASE_URL="<frontend_base_url>"
```

#### DOI
Set DOI credentials
```
DOI_SERVER="<doi_server>"
DOI_PREFIX="<doi_prefix>"
DOI_USER="<doi_user>"
DOI_PASSWORD="<doi_password>"
```

#### Private key
Set private key used for generating temporary file-download links
```
FILE_DOWNLOAD_PRIVATE_KEY="<private_key>"
FILE_DOWNLOAD_IV="<iv>"
FILE_DOWNLOAD_CIPHER="<cipher>"
```
