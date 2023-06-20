<?php
/**
 * This requires a kubectl port-forward command to be running in the background
 * See README.md for more information
 */

include 'vendor/autoload.php';

use League\Csv\Reader;

const HANDLE_PREFIX = 'https://unbscholar.lib.unb.ca/handle/';

$csv = Reader::createFromPath('./theses.csv', 'r');
$csv->setHeaderOffset(0);
$records = $csv->getRecords();

foreach ($records as $record) {
  if (!empty($record['mods_recordInfo_recordIdentifier_s'])) {
    preg_match('!\d+!', $record['mods_recordInfo_recordIdentifier_s'], $matches);
    if (!empty($matches[0])){
      $id =  $matches[0];
      $url = "http://localhost:8983/solr/search/select?fl=handle&indent=true&q.op=OR&q=dc.identifier.other%3A$id&wt=json";
      $json = file_get_contents($url);
      $data = json_decode($json, true);
      if (!empty($data['response']['docs'][0]['handle'])) {
        $handle = $data['response']['docs'][0]['handle'];
        echo implode(
          ',',
          [
            $record['PID'],
            HANDLE_PREFIX . $handle,
          ]
        ) . PHP_EOL;
      }
    }
  }
}
