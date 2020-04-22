#!/usr/bin/php
<?
/*
 * this is demo command line interface of aql library
 */
ini_set ('memory_limit', '128M');
require(dirname(__FILE__)."/inc/aql.php");

$base_dir = $_SERVER['argv'][1];

if (!is_dir($base_dir)) {
    echo "AQLCLI : sorry can't find basedir\n";
    exit;
}

$aql = new aql();
if (!$aql->set('basedir',$base_dir)) {
    echo "AQLCLI : ".$aql->get_error()."\n";
    exit;
}

echo "Welcome to the AQL command line interface.  Commands end pass Enter key\n";
echo "Your AQL Library version : ".$aql->get_version()." ,   AQL_Confparser Version : ".aql_confparser::get_version()."\n\n";
echo "type 'help' for help.\n\n";

while(1) {
    echo "AQLcli>";
    $input = fgets(STDIN);
    $input = trim($input);
    if ($input == 'help') {
        echo "List of all Interface commands : \n\n";
        echo "help      Display this help.\n";
        echo "quit      Quit AQLcli.\n";
        echo "freedb   free cached config file\n";
        echo "select    query select command.\n";
        echo "alter    query alter command.\n";
        echo "update    query update command.\n";
        echo "insert    query insert command.\n";
        echo "use       set basedir to load config.\n";
        echo "delete    query delete command.\n\n";
        echo "for more help see http://www.freeiris.org/astconfquerylanguage\n\n";
    } elseif ($input == 'quit') {
        exit;
    } elseif ($input == 'freedb') {
        $aql->free_database();
        echo "database freed.\n";
    } elseif (preg_match('/^(select|alter|update|insert|delete|use) /i',$input)) {
        $b1 = microtime(true);
        $result = $aql->query($input);
        $b2 = microtime(true);
        if (!$result) {
            echo $aql->get_error()."\n";
        } else {
            if (is_array($result)==true) {
                echo "+-----------------+-------------------------------\n";
                echo "+     SECTION     +\n";
                foreach ($result as $section => $dataref) {
                    echo "+-----------------+-------------------------------\n";
                    echo "+  $section";
                    if (strlen($section) <= 14) {
                        echo str_repeat(' ',(18-3-strlen($section)));
                    } else {
                        " ";
                    }
                    echo "+";
                    $id=0;
                    foreach ($dataref as $k => $v) {
                        if ($id==0) {
                            echo " $k=$v\n";
                        } else {
                            echo "+                 + $k=$v\n";
                        }
                        $id++;
                    }
                    if ($id==0) {
                        echo "\n";
                    }
                }
                echo "+-----------------+-------------------------------\n";
            }
            echo "Affected rows : ".$aql->get_affected_rows()." (".round(($b2-$b1),3)." sec)\n";
            echo "Filename : ".$aql->last_query_filename."\n\n";
        }
    }
  //  ------------+
//3 rows in set (0.00 sec)
}

?>
