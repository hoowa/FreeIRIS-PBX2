<?
/*
 * This is Test demo script for AQL
*/
error_reporting(E_ALL);
require("../inc/aql.php");

$a = new aql();

$setok = $a->set('basedir','./');
if (!$setok) {
    echo __LINE__.' '.$a->get_error();
}


$result = $a->query("insert into t14.conf set callerid=\"'99' <99>\",section='555'");

if ($result == false) {
    echo $a->get_error();
} else {
    echo 'affected_rows :'.$a->get_affected_rows()."\n";
}


?>
