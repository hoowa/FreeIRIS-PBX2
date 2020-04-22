<?
/*
 * This is Test demo script for AQL
*/
error_reporting(E_ALL);
require("../inc/aql.php");

$a = new aql();
$a->open_config_file('t1.conf');

echo $a->errstr;
$da = $a->get_database();
print_r($da);
?>
