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
select * from t2.conf where section = 9999
EOF;
// select * from t2.conf where section != 'general' and section != NULL
// select section,host,secret FROM t2.conf where section <= 9995 and section != 'general' and section != null limit 6,1
// select section,host,secret FROM t2.conf where section like '%99%' and section != 'general' and section != null limit 1
$result = $a->query($query);

if ($result == false) {
    echo $a->get_error();
} else {
    print_r($result);
}

echo "====================================================================================\n";
# very simple test query
$query = "select * from t2.conf where section != 'general' and section != NULL limit 1";
$result = $a->query($query);

if ($result == false) {
    echo $a->get_error();
} else {
    print_r($result);
}

echo "====================================================================================\n";
# very simple test query
$query = "select * from t2dahdi.conf";
$result = $a->query($query);

if ($result == false) {
    echo $a->get_error();
} else {
    print_r($result);
}

?>
