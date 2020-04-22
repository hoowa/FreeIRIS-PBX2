<?
/*
 * This is Test demo script for AQL
*/
error_reporting(E_ALL);
require("../inc/aql.php");

$a = new aql();
$a->open_config_file('t9.conf');

$a->assign_addsection(8001,"type=friend\nlimit-call=1\n");
$a->assign_delsection('general');

if (!$a->save_config_file('t9.conf')) {
	echo $a->get_error();
} else {
	echo "changed sections: \n";;
	print_r($a->last_save_changed_sections);
	echo "changed filename: ".$a->last_save_changed_filename."\n";
}
?>
