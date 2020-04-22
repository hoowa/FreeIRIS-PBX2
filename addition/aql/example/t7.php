<?
/*
 * This is Test demo script for AQL
*/
error_reporting(E_ALL);
require("../inc/aql.php");

$a = new aql();
$a->open_config_file('t7.conf');

$a->assign_append('general','nat','yes','after','allow[1]');
//'section name|[unsection]','newkeyname','newvalue','null|before|after','null|match_keyname'

if (!$a->save_config_file('t7.conf')) {
	echo $a->get_error();
} else {
	echo "changed sections: \n";;
	print_r($a->last_save_changed_sections);
	echo "changed filename: ".$a->last_save_changed_filename."\n";
}
?>
