# ?

## Features
- Logger in a separate class with multiple debug level
- Config in an singleton class, getting url params and config
- Possible to use in only one project
- Docker image
- Cli interface to add domains, run server and show the version
- Command to check installation


## Security
- New parameter `permit_params_with_route` to control querystring with routes
- Codecept for tests
- Refactoring the ThumcnoServer (TimThumb class file) and tests
- Add TravisCI and CodeClimate
- Using `php-cs-fixer`

#1.03-RC

## Features
- Set possible sizes in `.ini` file
- Set styles, with defined sizes to change your url

## Updates
- Better to log and more comments to update

## Fixed bugs
- Security fix: If the user put '../' at the begin of src param, he could access another pictures in another directories

# 1.02-BETA

## Features
- Url friendly in `.ini` files.
- Documentation updates

# 1.01

## Updates
- Change the $_GET to a parameter in a class Thumcno

# 1.0

## Features
- Generate images with parameters in URL
- Set the quality in image
- Create for multiple domains
- Configuration in `.ini` files
