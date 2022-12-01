# OPIUM.PRESS
## Deploying your own server
### Prerequisites 
- PHP 8.1 or higher
- Composer
- A database (MariaDB recommended)
- Symfony CLI
- **For Production Environments**
  - Apache or NGINX

### Steps
> all commands should be run in the project directory
1. Create .env.local file with the following information
```dotenv
DATABASE_URL=mysql://username:password@serverurlwithport
APP_ENV=[DEV or PROD] # defaults to dev
APP_DEBUG=[1 or 0] # 1 gives you debugging information, defaults to 0
APP_SECRET=[32 character long random string of hex digits]
```
2. Running composer install
```shell
$ composer install
```
3. Format Database
```shell
$ php bin/console doctrine:schema:update --force
```
4. Run the server
```shell
$ Symfony server:start
```