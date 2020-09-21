<?php

/**
 * @file
 * Execute commands via Robo.
 */

use Robo\Robo;

$app_owner = 'UnbLibraries';
$app_name = "IslandoraDspaceBridge";
$self_update_repository = 'unb-libraries/isdsbr';
$config_fileslug = 'isdsbr';

$app_namespace = "$app_owner\\$app_name";
$app_root = __DIR__ . '/..';
$app_version = trim(file_get_contents("$app_root/VERSION"));
$configuration_filename = "$app_root/$config_fileslug.yml";

// Autoload libraries.
if (file_exists("$app_root/vendor/autoload.php")) {
  $autoloader_path = "$app_root/vendor/autoload.php";
} else {
  die("Could not find autoloader. Run 'composer install'.");
}

// Command discovery from source.
$class_loader = require $autoloader_path;
$discovery = new \Consolidation\AnnotatedCommand\CommandFileDiscovery();
$discovery->setSearchPattern('*Command.php');
$command_classes = $discovery->discover( 
  "$app_root/src/Robo/Commands",
  "$app_namespace\Robo\Commands"
);

// Create an instance of the runner.
$runner = new \Robo\Runner($command_classes);
$runner
  ->setSelfUpdateRepository($self_update_repository)
  ->setConfigurationFilename($configuration_filename)
  ->setclassLoader($class_loader);

// Execute the command and return the result.
$output = new \Symfony\Component\Console\Output\ConsoleOutput();
$statusCode = $runner->execute($argv, $app_name, $app_version, $output);
exit($statusCode);
