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

// set manual commit
$a->autocommit = false;
$a->query("update t5.conf set call-limit = 1 where section = 8888");
$a->query("update t5.conf set call-limit = 1 where section = 9999");
$result = $a->commit('t5.conf');

if ($result == false) {
    echo $a->get_error();
} else {
    echo 'affected_rows :'.$a->get_affected_rows()."\n";
}


?>
