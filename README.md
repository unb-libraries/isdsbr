# systems-toolkit
## Automate all the common tasks.
A Robo based application that automates and standardizes several common tasks.

## Getting Started
### Requirements
The following packages are required to be globally installed:

* [PHP7](https://php.org/) - Install instructions [are here for OSX](https://gist.github.com/JacobSanford/52ad35b83bcde5c113072d5591eb89bd).
* [Composer](https://getcomposer.org/)
* [docker](https://www.docker.com)/[docker-compose](https://docs.docker.com/compose/)

### 1. Initial Setup
```
composer install --prefer-dist
```

### 2. Commands
```
 cyberman
  cyberman:sendmessage                      Send a message via the CyberMan Slack bot.
 drupal
  drupal:8:doupdates                        Perform needed Drupal 8 updates automatically.
  drupal:8:getupdates                       Get the list of needed Drupal 8 updates .
  drupal:8:rebasedevprod                    Rebase dev onto prod for multiple Drupal 8 Repositories. Robo Command.
  drupal:8:rebuild-redeploy                 Rebuild all Drupal 8 docker images and redeploy in their current state.
 dzi
  dzi:generate-tiles                        Generate DZI tiles for a file.
  dzi:generate-tiles:tree                   Generate DZI tiles for an entire tree.
 github
  github:repo:cherry-pick-multiple          Cherry pick a commit from a repo onto multiple others. Robo Command.
  github:repo:rebasedevprod                 Rebase dev onto prod for multiple GitHub Repositories. Robo Commmand.
  github:user:activity                      Get a list of recent commits to GitHub by a user.
 jira
  jira:project:info                         Get project info from the JIRA ID.
 k8s
  k8s:logs                                  Get a kubernetes service logs from the URI and namespace.
  k8s:shell                                 Get a kubernetes service shell from a URI and namespace.
 newspapers.lib.unb.ca
  newspapers.lib.unb.ca:create-issue        Import a single digital serial issue from a file path.
  newspapers.lib.unb.ca:create-issues-tree  Create digital serial issues from a tree containing files.
  newspapers.lib.unb.ca:create-page         Create a digital serial page from a source file.
  newspapers.lib.unb.ca:generate-page-ocr   Generate and update the OCR content for a digital serial page.
  newspapers.lib.unb.ca:get-page            Download the image of a digital serial page.
 ocr
  ocr:tesseract:file                        Generate OCR for a file.
  ocr:tesseract:tree                        Generate OCR for an entire tree.
  ocr:tesseract:tree:metrics                Generate metrics for OCR confidence and word count for a tree.
 travis
  travis:build:get-latest                   Get the latest travis build job details for a repository.
  travis:build:get-latest-id                Get the latest travis build job ID for a repository.
  travis:build:restart                      Restart a travis build job.
  travis:build:restart-latest               Restart the latest travis build job in a branch of a repository.
 updater
  updater:composer-apps                     Updates composer-based apps on various servers.
```

### 3. Other Commands
Run ```vendor/bin/syskit``` to get a list of available commands.
