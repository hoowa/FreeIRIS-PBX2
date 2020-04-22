<?
/*
 * This is Test demo script for AQL
*/
error_reporting(E_ALL);
require("../inc/aql.php");

$a = new aql();
$a->open_config_file('t3.conf');

$a->assign_editkey('general','allow[1]','g722');

if (!$a->save_config_file('t3.conf')) {
	echo $a->get_error();
} else {
	echo "changed sections: \n";;
	print_r($a->last_save_changed_sections);
	echo "changed filename: ".$a->last_save_changed_filename."\n";
}
?>
