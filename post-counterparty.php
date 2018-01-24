<?php
include "MSExporter.php";

$exporter = new MSExporter();

$newFile = file_get_contents( 'php://input' );
$newData = json_decode( $newFile );
unset( $newFile );
if ( $exporter->checkModel( $newData ) )
{
   $exporter->exportCounterpartyFromAnketaJSON($newData);
   $exporter->completeAllRequests();
} else {
   $log = "Access denied on " . date("d-m-Y H:i:s");
   file_put_contents('log.txt', $log.PHP_EOL , FILE_APPEND | LOCK_EX);
}


/*$data = json_decode(file_get_contents("data.json"));
foreach ($data as $item){
   $exporter->exportCounterpartyFromAnketaJSON($item);
   $exporter->completeAllRequests();
}*/