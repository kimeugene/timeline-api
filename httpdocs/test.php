<?php

require '../include/shared/FoursquareApiProxy.class.php';

if (isset($_GET['ll']) && preg_match('/^-?\d+\.\d+,-?\d+\.\d+$/', $_GET['ll'])) {
    list($lat, $long) = explode(',', $_GET['ll']);
}

if (isset($_GET['radius']) && is_numeric($_GET['radius'])) {
   $rad = $_GET['radius'];
}

if (isset($_GET['results']) && is_numeric($_GET['results'])) {
   $res = $_GET['results'];
}

$lat  = (isset($lat))  ? (float)$lat  :  40.7619;
$long = (isset($long)) ? (float)$long : -73.9763;
$rad  = (isset($rad))  ? (int)$rad    :  800;
$res  = (isset($res))  ? (int)$res    :  50;

$fs = new FoursquareApiProxy($lat, $long);
$fs->setRadius($rad);
$fs->setResults($res);

echo 'API version sent to FourSquare: <b>' . $fs->getVersion() . '</b><br />' . PHP_EOL;
echo 'Latitude used: <b>' . $fs->getLatitude() . '</b><br />' . PHP_EOL;
echo 'Longitude used: <b>' . $fs->getLongitude() . '</b><br />' . PHP_EOL;
echo sprintf('Radius: <b>%d</b> meters', $fs->getRadius()) . '<br />' . PHP_EOL;
echo sprintf('Asked Foursquare API to return <b>%d</b> venues', $fs->getResults()) . '<br /><br />' . PHP_EOL;

echo '<pre>';
var_dump($fs->getVenueList());
echo '</pre>';

