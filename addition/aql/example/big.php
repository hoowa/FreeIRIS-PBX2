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

$query = <<< EOF
select * from big.conf
EOF;
$result = $a->query($query);

if ($result == false) {
    echo $a->get_error();
} else {
    echo "benchmark to query data from big.conf\n";
    echo "AQL LANGUAGE : $query\n";
    echo 'record sections: '.$a->get_affected_rows();
    //print_r($result);
}

?>
