<?php

include "Exporter.php";

$exporter = new Exporter();

$csvFile = file('anketa.csv');
$data = [];

foreach ($csvFile as $line) {
    $data[] = str_getcsv($line, ";");
}
$i = 1;
foreach ($data as $item) {
    print("iteration: $i\n");
    $exporter->exportOldAnketaItem($item);
    $i++;
    //$this->createTag($item);
    //var_dump($item);
}