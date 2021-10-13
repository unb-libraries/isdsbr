# Islandora-Dspace Bridge
## Automated Islandora-Dspace Migrations
__isdsbr__ is a PHP application that automates and standardizes a migration from Islandora (MODS metadata) to DSpace (Dublin Core metadata). __isdsbr__ is based on the Robo framework.

## Getting Started
### General requirements
Although __isdsbr__ can be deployed on OSX, the only officially supported operating system is Linux.

### Software Prerequisites
You must have the following tools available for use from the command line:

* [PHP7.3+](https://php.org/): Install via ```apt-get install php-cli```
* Various PHP Extensions: Install via ```apt-get install php-curl php-ctype php-dom php-gd php-mbstring php-posix php-yaml php-zip```
* [composer](https://getcomposer.org/): Installation steps [are located here](https://getcomposer.org/download/).

## Networking
__isdsbr__ requires your local workstation to make HTTP, HTTPS and SSH requests. These requests must not be blocked. If you use a proxy server to connect to the web or SSH, you must also configure your OS to use that proxy by default.

### Initial Setup
```
composer install
```

## Usage
### 1. isdsbr:export
#### Remote -> Local
Discover objects from Islandora/Fedora based on a solr query, and then export them to a local path via __fedora-export.sh__ in the ATOMZip format:

```
./isdsbr isdsbr:export /tmp/exportFedora
```

The solr query and all necessary configuration is read from isdsbr.yml.

### 2. isdsbr:crosswalk
#### Local -> Local
Migrate the Islandora MODS-based content into Dublin Core based Simple Archive Format metadata:

```
./isdsbr isdsbr:crosswalk /tmp/exportFedora /tmp/exportDspace
```

### 3. isdsbr:import
#### Local -> Remote
Import the local migrated DSpace metadata into a remote DSpace instance running in a k8s pod:

```
./isdsbr isdsbr:import /tmp/exportDspace unbscholar-lib-unb-ca-cd47bfccc-74p7g prod 1234567/21
```

(Optionally) Revert an import by leveraging the timestamped export map file:

```
./isdsbr isdsbr:import:revert 1600784182 unbscholar-lib-unb-ca-cd47bfccc-74p7g prod
```

## Releases
__isdsbr__ releases are not tagged according to semantic versioning. Using the HEAD commit is recommended.

We add features to the product often, and deprecate quickly. Expect rapid development that introduces backwards-incompatible changes.

## Author / Contributors
This application was created at [![UNB Libraries](https://github.com/unb-libraries/assets/raw/master/unblibbadge.png "UNB Libraries")](https://lib.unb.ca) by the following humans:

<a href="https://github.com/JacobSanford"><img src="https://avatars.githubusercontent.com/u/244894?v=3" title="Jacob Sanford" width="128" height="128"></a>

We gladly accept improvements and contributions, and if you would like to help improve __isdsbr__, please forward a Pull Request.

## License
- As part of our 'open' ethos, UNB Libraries licenses its applications and workflows to be freely available to all whenever possible.
- Consequently, the contents of this repository [unb-libraries/isdsbr] are licensed under the [MIT License](http://opensource.org/licenses/mit-license.html). This license explicitly excludes:
    - Any website content, which remains the exclusive property of its author(s).
    - The UNB logo and any of the associated suite of visual identity assets, which remains the exclusive property of the University of New Brunswick.
