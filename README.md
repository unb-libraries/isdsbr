# Islandora-Dspace Bridge
## Automated Islandora-Dspace Migrations
At UNB Libraries, we are migrating all objects from our legacy (bare-metal hosted) Drupal 7 Islandora application into a
(k8s-deployed) DSpace 7 application. __isdsbr__ is a PHP application written to automate this process. Unless you are also
doing exactly this, __isdsbr__ is unlikely to be of use to you. 

## Prerequisites
#### General Requirements
Although __isdsbr__ can be deployed on OSX, the only officially supported operating system is Linux.

#### Software Prerequisites
You must have the following tools available for use from the command line:

* [PHP8.0+](https://php.org/): Install via ```apt-get install php-cli```
* Various PHP Extensions: Install via ```apt-get install php-curl php-ctype php-dom php-gd php-mbstring php-posix php-yaml php-zip```
* [composer](https://getcomposer.org/): Installation steps [are located here](https://getcomposer.org/download/).

#### Networking
__isdsbr__ requires your local workstation to make HTTP, HTTPS, SSH and kubectl requests to the Solr, Islandora and
DSpace hosts. These requests must not be blocked. If you use a proxy server to connect to the web or SSH, you must also
configure your OS to use that proxy by default.

## Initial Setup
```
composer install
```

## Workflow
The entire process is performed via a series of commands:

### isdsbr:export
Exports Islandora objects to a local path.
```
./isdsbr isdsbr:export /tmp/exportFedora
```

#### Details
* Uses [standard Solr queries](https://github.com/unb-libraries/isdsbr/blob/1.x/isdsbr.yml.sample#L5), and queries the
Islandora Solr instance to discover objects targeted for export.
* Connects (via ssh) to the [fedora host](https://github.com/unb-libraries/isdsbr/blob/1.x/isdsbr.yml.sample#L43) and
export the target objects (and associated bitstreams) with ```fedora-export.sh``` to a temporary folder.
* Copies those objects via ssh to the local path (/tmp/exportFedora).
  * [Collections](https://github.com/unb-libraries/isdsbr/blob/1.x/isdsbr.yml.sample#L2) are written to separate local
folders within the target path, and the target DSpace collection IDs are written to a metadata file within each folder.

### isdsbr:crosswalk
Crosswalks the Islandora (MODS based) data from a local path into the Dublin Core based Simple Archive Format.
```
./isdsbr isdsbr:crosswalk /tmp/exportFedora /tmp/importDspace
```

#### Details
* Uses the [configured field map](https://github.com/unb-libraries/isdsbr/blob/1.x/isdsbr.yml.sample#L6) and
[migrates](https://github.com/unb-libraries/isdsbr/blob/1.x/field_maps/thesis.yml) the MODS metadata and PDF bitstreams
for all objects (located in /tmp/exportFedora) to a Dublin Core based Simple Archive Format (/tmp/importDspace).
  * The first level of folders within the source path is considered to be a collection delimiter, and preserved in the
target output.

### isdsbr:import
Imports the Dublin Core based Simple Archive Format into DSpace.
```
./isdsbr isdsbr:import /tmp/importDspace unbscholar-dspace-lib-unb-ca-cd47bfccc-74p7g prod
```

#### Details
* The first level of folders within the source path (/tmp/importDspace) is considered to be a collection delimiter.
* For each collection:
  * Reads the target DSpace collection from a metadata file.
  * Compresses, copies a zip file of the objects (via kubectl) to the target DSpace k8s pod 
(name: unbscholar-dspace-lib-unb-ca-645f6fc74b-6rsd7 namespace:prod).
  * (via kubectl) Extracts the archive and imports the set of objects into the target DSpace collection using
```dspace import```.
  * Executes (via kubectl) a ```dspace filter-media``` command on the target DSpace collection.
  * Copies the generated import map file to this repository's
[import_maps](https://github.com/unb-libraries/isdsbr/tree/1.x/import_maps) path, naming it appropriately.


### isdsbr:import:revert
Reverts a previous DSpace import.
```
./isdsbr isdsbr:import:revert ./import_maps/dspace_import_map-1634640294-biology_theses_dissertations.txt unbscholar-dspace-lib-unb-ca-cd47bfccc-74p7g prod
```

#### Details
* Copies the desired import_map to revert (./import_maps/dspace_import_map-1634640294-biology_theses_dissertations.txt)
via kubectl to the target DSpace k8s pod (name: unbscholar-dspace-lib-unb-ca-cd47bfccc-74p7g namespace:prod).
* Executes (via kubectl) a ```dspace import -d```.

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
