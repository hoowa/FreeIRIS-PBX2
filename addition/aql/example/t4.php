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

// very simple test query
$query = <<< EOF
update t4.conf set call-limit=1 where section>=9990 and section <=9993 limit 1,2
EOF;
$result = $a->query($query);

if ($result == false) {
    echo $a->get_error();
} else {
    echo 'affected_rows :'.$a->get_affected_rows()."\n";
}


?>
