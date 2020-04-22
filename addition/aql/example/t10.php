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


$result = $a->query("insert into t10.conf set type='friend',section='8001',call-limit=1");
$result = $a->query("delete from t10.conf where section = '8001'");

if ($result == false) {
    echo $a->get_error();
} else {
    echo 'affected_rows :'.$a->get_affected_rows()."\n";
}


?>
