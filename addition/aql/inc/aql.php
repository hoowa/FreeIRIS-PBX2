<?
/*
#
#       AQL -- Astconf Query Language
#       Copyright (C) 2010, Fonoirs Co.,LTD.
#       By Sun bing <hoowa.sun@gmail.com>
#
#       See http://www.freeiris.org/astconfquerylanguage for more.
#
#       This program is free software; you can redistribute it and/or modify
#       it under the terms of the GNU General Public License as published by
#       the Free Software Foundation; either version 2 of the License, or
#       (at your option) any later version.
#
#       This program is distributed in the hope that it will be useful,
#       but WITHOUT ANY WARRANTY; without even the implied warranty of
#       MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#       GNU General Public License for more details.
#
#       You should have received a copy of the GNU General Public License
#       along with this program; if not, write to the Free Software
#       Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
#       MA 02110-1301, USA.
#
*/
/*
#
#    aql.class.php
#    Copyright (C) 2010, Fonoirs Co.,LTD.
#    By Sun bing <hoowa.sun@gmail.com>
#
*/
function __autoload($file) {
    require_once $file.'.php';
}
    
class aql extends aql_confparser
{
    const VERSION='1.5';
    const AUTHOR='Sun Bing <hoowa.sun@freeiris.org>';
    
    /* 
     * base of filename directory, aql where found conf file from 
     * this directory. 
     */
    public $basedir = null;
    /*
     * auto commit when query command sent.
     */
    public $autocommit = true;
    /*
     * last changed affected rows
     */
    public $affected_rows = null;
    /*
     * checkspelling if file not found try add .conf to end
     */
    public $checkspelling = true;
    /*
     * last query filename
     */
    public $last_query_filename = null;

    /*
     * lexical keywords
     */
    private $querycommand = array('select','update','alter','delete','insert','use');
    private $operators = array('=', '<>', '<', '<=', '>', '>=', 'like', 'and');
    private $conjuctions = array('from','where');
    private $functions = array('before','after');
    private $types = array('bool','double','float','int','integer','long','null','numeric','real','scalar','string');
    private $keywords = array('section','[unsection]');
    
    
    // under construct
    public function __construct() {
    }
    
    // set options
    public function set($option,$value) {
        if ($option == "basedir") {
            
            if (is_dir($value)) {
                $this->basedir = $value;
                return(true);
            } else {
                $this->errstr = "Set [".__LINE__."] : You have an error ; basedir not found $value !\n";
                return(false);
            }
            
        } elseif ($option == "autucommit") {
            
            if ($value == true) {
                $this->autocommit = true;
                return(true);
            } else {
                $this->autocommit = false;
                return(true);
            }

        } elseif ($option == "reloadwhensave") {
            
            if ($value == true) {
                $this->reload_when_save = true;
                return(true);
            } else {
                $this->reload_when_save = false;
                return(true);
            }

        } elseif ($option == "queryresult") {
            
            if ($value == true) {
                $this->queryresult = true;
                return(true);
            } else {
                $this->queryresult = false;
                return(true);
            }
 
        } elseif ($option == "comment_flags") {
            if (trim($value) !== null) {
                $this->comment_flags = $value;
            }
            return(true);
        }

    }
    
    // query language interface
    public function query($expression) {
        
        $result = false;
        
        $expression = trim($expression);
        if ($expression == "") {
            $this->errstr = "Query [".__LINE__."] : You have an error in your AQL syntax; Not found Expression! \n";
            return($result);
        }
        
        //lexical analysis
        $token = $this->query_lexical_analysis($expression);
        if ($token == false) {
            return($result);
        }

        // try to find tablename
        if (array_key_exists('tablename',$token) && $this->checkspelling==true && !preg_match('/\.conf$/',$token['tablename'])) {
            if (file_exists($this->basedir.'/'.$token['tablename'].'.conf')) {
                $token['tablename'] .= '.conf';
            }
        }

        //run query command
        if ($token['querycommand'] == "select") {
            $result = $this->query_command_select($token);
            $this->last_query_filename=$token['tablename'];
        } elseif ($token['querycommand'] == "update") {
            $result = $this->query_command_update($token);
            $this->last_query_filename=$token['tablename'];
        } elseif ($token['querycommand'] == "alter") {
            $result = $this->query_command_alter($token);
            $this->last_query_filename=$token['tablename'];
        } elseif ($token['querycommand'] == "insert") {
            $result = $this->query_command_insert($token);
            $this->last_query_filename=$token['tablename'];
        } elseif ($token['querycommand'] == "delete") {
            $result = $this->query_command_delete($token);
            $this->last_query_filename=$token['tablename'];
        // followed command not change any table
        } elseif ($token['querycommand'] == "use") {
            $result = $this->query_command_use($token);
            $this->last_query_filename=$token['basedir'];
        } else {
            $this->errstr = "Query [".__LINE__."] : You have an error in your AQL syntax; Not found Expression! \n";
            return($result);
        }
        
        return($result);
    }
    
    // manual commit
    public function commit($filename) {
        
        if (!$this->save_config_file($filename)) {
            $this->errstr="Query [".__LINE__."] : You have an error in tablename(filename); ".$this->errstr;
            return(false);
        }
        
        // saving affected rows
        $this->affected_rows = count($this->last_save_changed_sections);
        return(true);
    }
    // clean all commit list
    public function commit_clean($filename) {
        $this->commit_assign=array();
        return(true);
    }
    
    // get error from aql_confparser and from myself
    public function get_error() {
        return($this->errstr);
    }
    
    // Get the number of affected rows by the last AQL commands. 
    public function get_affected_rows() {
        return($this->affected_rows);
    }
    
    // return confpaser version
    public function get_version() {
        return(self::VERSION);
    }


    /* ----------------------------------------------------------------
     * 
     * Private Function lexical analysis
     * tune query expression to token array
     * querycommand, operators, conjuctions, functions, types
     * 
     * ----------------------------------------------------------------
     */
    // get one word beginning from string and cut with space
    private function query_lexical_getword($string) {
        
        $string = trim($string);
        $word=null;
        
        for ($i=0;$i<=(strlen($string)-1);$i++) {
            if ($string[$i] == " ") {
                break;
            }
            $word .= $string[$i];
        }

        return($word);
    }
     // format string to keyword
    private function query_lexical_analysis_keyword($string) {
        
        $string = trim($string);

        if ($string[0] == '"' && $string[(strlen($string)-1)] == '"') {
            $string = substr($string,1,-1);
        // begin with ' and end with '
        } elseif ($string[0] == "'" && $string[(strlen($string)-1)] == "'") {
            $string = substr($string,1,-1);
        // begin with a-zA-Z0-9
        } elseif (preg_match('/[0-9a-zA-Z\*]/',$string[0])) {
        // begin with other char
        } else {
            $this->errstr="Query [".__LINE__."] : You have an error in your AQL syntax; $string \n";
            return(false);
        }

        $string = stripslashes($string); // escape \' \" \\

        return($string);
    }
    
    private function query_lexical_analysis($put_expression) {
        
        $token = array();

        // querycommand find
        $put_querycommand = strtolower($this->query_lexical_getword($put_expression));
        foreach ($this->querycommand as $each) {
            if ($put_querycommand == $each) {
                $token['querycommand'] = $each;
                break;
            }
            
        }
        if (array_key_exists('querycommand',$token)==false) {
            $this->errstr="Query [".__LINE__."] : You have an error in your AQL syntax; Invalid expression ! \n";
            return(false);
        }
        // checking syntax end with ;
        if (preg_match('/[;]$/',$put_expression[(strlen($put_expression)-1)])) {
            $this->errstr="Query [".__LINE__."] : You have an error in your AQL syntax; At end of '$put_expression' ! \n";
            return(false);
        }

        /*
         * difference command parser
         * SELECT * FROM <config_file>
         * [WHERE <condition> [AND <condition> ...]]
         * [LIMIT [<offset>,]<count>]
         */
        if ($token['querycommand'] == 'select') {

            //split result_range and condition
            preg_match("/^select (.*) from (.*)/i",$put_expression,$matches);
            if (!$matches) {
                $this->errstr="Query [".__LINE__."] : You have an error in your AQL syntax; Not found 'from' in expression ! \n";
                return(false);
            }

            // Analysis result_range to token keywords array
            $put_condition = $matches[2];

            // find result ranges
            $token['result_ranges']=$this->query_lexical_analysis_resultrange($matches[1]);
            if ($token['result_ranges']==false) {
                return(false);
            }
            
            // find Table name
            $token['tablename']=$this->query_lexical_getword($put_condition);
            $put_condition = substr($put_condition,strlen($token['tablename']));

            // find last LIMIT
            $put_limit = $this->query_lexical_analysis_limit($put_condition);
            if ($put_limit == false)    return(false);
            $put_condition = $put_limit[0];
            $token['limit_position'] = $put_limit[1];
            $token['limit_max'] = $put_limit[2];

            // find WHERE
            $put_wherecondition = $this->query_lexical_analysis_whereconditions($put_condition);
            if ($put_wherecondition == false)    return(false);
            $put_condition = $put_wherecondition[0];
            $token['conditions']=$put_wherecondition[1];

        /*
         *  UPDATE <config_file> SET key=value[,key2=value2]
         *  [WHERE <condition> [AND <condition> ...]]
         *  [LIMIT [<offset>,]<count>]
         */
        } elseif ($token['querycommand'] == 'update') {
            
            //split result_range and condition
            preg_match("/^update (.*) set (.*)/i",$put_expression,$matches);
            if (!$matches) {
                $this->errstr="Query [".__LINE__."] : You have an error in your AQL syntax; $put_expression ! \n";
                return(false);
            }
            
            // find Table name
            $token['tablename']=$matches[1];
            
            // find followed
            $put_condition = $matches[2];

            // find limit
            $put_limit = $this->query_lexical_analysis_limit($put_condition);
            if ($put_limit == false)    return(false);
            $put_condition = $put_limit[0];
            $token['limit_position'] = $put_limit[1];
            $token['limit_max'] = $put_limit[2];
            
            // find where conditions
            $put_wherecondition = $this->query_lexical_analysis_whereconditions($put_condition);

            if ($put_wherecondition == false)    return(false);
            $put_condition = $put_wherecondition[0];
            $token['conditions']=$put_wherecondition[1];

            // find sets
            $token['sets'] = $this->query_lexical_analysis_sets($put_condition);
            if ($token['sets'] == false)    return(false);

        /* 
         * add new key :
         * ALTER TABLE <config_file> ADD key=value[,key2=value2...] [AFTER(keyname)|BEFORE(keyname)]
         * [WHERE <condition> [AND <condition> ...]]
         * [LIMIT [<offset>,]<count>]
         * 
         * delete key :
         * ALTER TABLE <config_file> DROP key,[key2...]
         * [WHERE <condition> [AND <condition> ...]]
         * [LIMIT [<offset>,]<count>]
         */
        } elseif ($token['querycommand'] == 'alter') {

            //split result_range and condition
            preg_match("/^alter table (.*) (add|drop) (.*)/i",$put_expression,$matches);
            if (!$matches) {
                $this->errstr="Query [".__LINE__."] : You have an error in your AQL syntax; $put_expression ! \n";
                return(false);
            }
            
            // find Table name
            $token['tablename']=$matches[1];
            $token['type']=$matches[2];
            
            // find followed
            $put_condition = $matches[3];

            // find limit
            $put_limit = $this->query_lexical_analysis_limit($put_condition);
            if ($put_limit == false)    return(false);
            $put_condition = $put_limit[0];
            $token['limit_position'] = $put_limit[1];
            $token['limit_max'] = $put_limit[2];

            // find where conditions
            $put_wherecondition = $this->query_lexical_analysis_whereconditions($put_condition);
            if ($put_wherecondition == false)    return(false);
            $put_condition = $put_wherecondition[0];
            $token['conditions']=$put_wherecondition[1];
            
            
            // find before|after?
            if (preg_match('/^(.*) (before|after)\((.*)\)$/i',$put_condition,$matches)) {
                $token['macro']=$matches[2];
                $token['macro_args']=$this->query_lexical_analysis_keyword($matches[3]);
                if (!$token['macro_args']) {
                    return(false);
                }
                $put_condition = $matches[1];
            } else {
                $token['macro']=null;
                $token['macro_args']=null;
            }
            
            // find sets
            if ($token['type'] == 'drop') {
                $token['sets'] = $this->query_lexical_analysis_sets($put_condition,true);
            } else {
                $token['sets'] = $this->query_lexical_analysis_sets($put_condition,false);
            }
            if ($token['sets'] == false)    return(false);
            


        /*
         * INSERT INTO <config_file> SET key=value[,key2=value2]
         */
        } elseif ($token['querycommand'] == 'insert') {
            //split result_range and condition
            preg_match("/^insert into (.*) set (.*)/i",$put_expression,$matches);
            if (!$matches) {
                $this->errstr="Query [".__LINE__."] : You have an error in your AQL syntax; $put_expression ! \n";
                return(false);
            }
            
            // find Table name
            $token['tablename']=$matches[1];

            // find followed
            $put_condition = $matches[2];
            
            // find sets
            $token['sets'] = $this->query_lexical_analysis_sets($put_condition);
            if ($token['sets'] == false)    return(false);


        /*
         * DELETE FROM <config_file>
         * [WHERE <condition> [AND <condition> ...]]
         * [LIMIT [<offset>,]<count>]
         */
        } elseif ($token['querycommand'] == 'delete') {
            //split result_range and condition
            preg_match("/^delete from (.*)/i",$put_expression,$matches);
            if (!$matches) {
                $this->errstr="Query [".__LINE__."] : You have an error in your AQL syntax; $put_expression ! \n";
                return(false);
            }
            
            // Analysis result_range to token keywords array
            $put_condition = $matches[1];

            // find Table name
            $token['tablename']=$this->query_lexical_getword($put_condition);
            $put_condition = substr($put_condition,strlen($token['tablename']));

            // find last LIMIT
            $put_limit = $this->query_lexical_analysis_limit($put_condition);
            if ($put_limit == false)    return(false);
            $put_condition = $put_limit[0];
            $token['limit_position'] = $put_limit[1];
            $token['limit_max'] = $put_limit[2];

            // find WHERE
            $put_wherecondition = $this->query_lexical_analysis_whereconditions($put_condition);
            if ($put_wherecondition == false)    return(false);
            $put_condition = $put_wherecondition[0];
            $token['conditions']=$put_wherecondition[1];
            
            
        /*
         * USE <path>
         */
        } elseif ($token['querycommand'] == 'use') {
            //split result_range and condition
            preg_match("/^use (.*)/i",$put_expression,$matches);
            if (!$matches) {
                $this->errstr="Query [".__LINE__."] : You have an error in your AQL syntax; $put_expression ! \n";
                return(false);
            }
            
            $token['basedir'] = $this->query_lexical_analysis_keyword($matches[1]);
        }

        return($token);
    }


    // query where condition expr analysis
    private function query_lexical_analysis_resultrange($expr) {

        $resultrange=array();

        foreach (explode(',',$expr) as $each_range) {
            
            // fmt value
            $each_range = trim($each_range);
            
            // syntax checking
            $each_range = $this->query_lexical_analysis_keyword($each_range);
            if (!$each_range) {
                return(false);
            }
            
            // if count
            if (preg_match('/^count\((.+)\)$/i',$each_range)) {
                $resultrange = array();
                $resultrange[] = 'count(*)';
                break;
                
            } elseif (preg_match('/^\*$/',$each_range)) {
                $resultrange = array();
                $resultrange[] = '*';
                break;
                
            } elseif (preg_match('/[\[\]\=]/',$each_range)) {
                $this->errstr="Query [".__LINE__."] : You have an error in your AQL syntax; '$each_range' ! \n";
                return(false);
            }

            array_push($resultrange,$each_range);
        }

        return($resultrange);
    }

    // query keywords LIMIT expr analysis
    private function query_lexical_analysis_limit($put_condition) {

        $limit_position = null;
        $limit_max = null;
        
        // find limit
        if (preg_match('/ limit (.*)$/i',$put_condition,$matches)) {
            $put_condition = preg_replace('/ limit (.*)$/i','',$put_condition);

            $limit = explode(",",trim($matches[1]));
            for ($i=0;$i<=(count($limit)-1);$i++) {
                if ($i>1) {
                    $this->errstr="Query [".__LINE__."] : You have an error in your AQL syntax; LIMIT too many args ! \n";
                    return(false);
                }
                // check intger
                if (preg_match('/[^0-9]/',$limit[$i])) {
                    $this->errstr="Query [".__LINE__."] : You have an error in your AQL syntax; LIMIT invalid number\n";
                    return(false);
                }
                if ($i===0) {
                    $limit_position = $limit[$i];
                } elseif ($i===1) {
                    $limit_max = $limit[$i];
                }
            }
        }

        return(array($put_condition,$limit_position,$limit_max));
    }


    // query where condition expr analysis
    private function query_lexical_analysis_whereconditions($expr) {

        $conditions=array();
        
        if (preg_match('/ where (.*)$/i',$expr,$matches)) {
            $expr = preg_replace('/ where (.*)$/i','',$expr);

            foreach (preg_split('/ and /i',$matches[1]) as $each) {

                $each_list=array();
                $each_list['key']=null;
                $each_list['value']=null;
                $each_list['operator']=null;
                
                // trim
                $each = trim($each);
                
                // take key from each
                $brackets = null;
                $operbegin = null;
                for($i=0;$i<=(strlen($each)-1);$i++) {
                    if ($i===0) {
                        if ($each[$i] == "'" || $each[$i] == '"') {
                            $brackets=$each[$i];
                            continue;
                        } else {
                            $brackets=null;
                            $each_list['key'].=$each[$i];
                            continue;
                        }
                    } elseif ($each[$i] == $brackets && $each[$i-1] == "\\") {
                        $each_list['key'].=$each[$i];
                        continue;
                    } elseif ($each[$i] == " " || $each[$i] == $brackets) {
                        $operbegin=$i+1;
                        break;
                    } elseif (($brackets == null && preg_match("/[^0-9a-zA-Z]/",$each[$i]))) {
                        $operbegin=$i;
                        break;
                    } else {
                        $each_list['key'].=$each[$i];
                        continue;
                    }
                }
                if ($each_list['key'] == null) {
                    $this->errstr="Query [".__LINE__."] : You have an error in your AQL syntax; $expr \n";
                    return(false);
                }
                $each_list['key'] = stripslashes($each_list['key']); // escape \' \" \\
                // current we support conditions key section only.
                if ($each_list['key'] != 'section') {
                    $this->errstr="Query [".__LINE__."] : You have an error in your AQL syntax; WHERE conditions key support 'section' only. \n";
                    return(false);
                }
                
                // take value from each 
                $brackets = null;
                $operend = null;
                for($i=(strlen($each)-1);$i>=0;$i--) {
                    if ($i===(strlen($each)-1)) {
                        if ($each[$i] == "'" || $each[$i] == '"') {
                            $brackets=$each[$i];
                            continue;
                        } else {
                            $brackets=null;
                            $each_list['value']=$each[$i].$each_list['value'];
                            continue;
                        }
                    } elseif ($each[$i] == $brackets && $each[$i-1] == "\\") {
                        $each_list['value']=$each[$i].$each_list['value'];
                        continue;
                    } elseif ($each[$i] == " " || $each[$i] == $brackets) {
                        $operend=$i-1;
                        break;
                    } elseif (($brackets == null && preg_match("/[^0-9a-zA-Z]/",$each[$i]))) {
                        $operend=$i;
                        break;                  
                    } else {
                        $each_list['value']=$each[$i].$each_list['value'];
                        continue;
                    }
                }
                // if section value is '' replace with string null
                if ($each_list['value'] == '') {
                    $each_list['value']='null';
                } else {
                    $each_list['value'] = stripslashes($each_list['value']); // escape \' \" \\
                }
                if ($each_list['value'] == null) {
                    $this->errstr="Query [".__LINE__."] : You have an error in your AQL syntax; $expr \n";
                    return(false);
                }

                // take operator
                $each_list['operator'] = trim(strtolower(substr($each,($operbegin),($operend-$operbegin+1))));
                // operator mark alias
                if ($each_list['operator'] == '!=') {
                    $each_list['operator'] = '<>';
                }
                // checking operator valid
                if ($each_list['operator'] == '<>' ||
                    $each_list['operator'] == '<=' ||
                    $each_list['operator'] == '>=' ||
                    $each_list['operator'] == '<' ||
                    $each_list['operator'] == '>' ||
                    $each_list['operator'] == '=' ||
                    $each_list['operator'] == 'like') {

                } else {
                    $this->errstr="Query [".__LINE__."] : You have an error in your AQL syntax; $expr \n";
                    return(false);
                }

                //checking syntax
                if ($each_list['operator']=='<=' && is_numeric($each_list['value']) == false) {
                    $this->errstr="Query [".__LINE__."] : You have an error in your AQL syntax; $expr \n";
                    return(false);
                } elseif ($each_list['operator']=='>=' && is_numeric($each_list['value']) == false) {
                    $this->errstr="Query [".__LINE__."] : You have an error in your AQL syntax; $expr \n";
                    return(false);
                } elseif ($each_list['operator']=='<' && is_numeric($each_list['value']) == false) {
                    $this->errstr="Query [".__LINE__."] : You have an error in your AQL syntax; $expr \n";
                    return(false);
                } elseif ($each_list['operator']=='>' && is_numeric($each_list['value']) == false) {
                    $this->errstr="Query [".__LINE__."] : You have an error in your AQL syntax; $expr \n";
                    return(false);
                } elseif ($each_list['operator']=='like' && strlen($each_list['value']) < 3) {
                    $this->errstr="Query [".__LINE__."] : You have an error in your AQL syntax; 'like' request min 3char in value \n";
                    return(false);
                }

                array_push($conditions,$each_list);
            }
        
        }
       
        return(array($expr,$conditions));
    }

    // query sets analysis
    private function query_lexical_analysis_sets($expr,$disable_operator=false) {

        // split and fill expr to keyvalue
        $keyvalue=array();
        $buffer=null;
        $brackets=null;
        for ($i=0;$i<=(strlen($expr)-1);$i++) {
            
            // find char is brackets
            if ($expr[$i] == "'" || $expr[$i] == '"') {
                if ($brackets == null) {
                    $brackets=$expr[$i];
                } elseif ($expr[$i] == $brackets && $expr[$i-1] == "\\") {
                } elseif ($expr[$i] == $brackets) {
                    $brackets=null;
                }
            }
            
            // if find ,
            if ($expr[$i] == ',') {
                if ($brackets==null) {
                    if (trim($buffer) != null) {
                        $keyvalue[] = $buffer;
                    }
                    $buffer=null;
                    continue;
                } else {
                    $buffer .= $expr[$i];
                    continue;
                }
            } else {
                $buffer .= $expr[$i];
                continue;
            }
        }
        if (trim($buffer) != null) {
            $keyvalue[] = $buffer;
        }
        if (count($keyvalue) <= 0) {
            $this->errstr="Query [".__LINE__."] : You have an error in your AQL syntax; $expr \n";
            return(false);
        }

        // filling data to list
        $sets=array();
        foreach ($keyvalue as $each) {

            $each_list=array();
            $each_list['key']=null;
            $each_list['value']=null;
            $each_list['operator']=null;
                            
            // trim
            $each = trim($each);

            // take key from each
            $brackets = null;
            $operbegin = null;
            $key_find_flags = false;
            for($i=0;$i<=(strlen($each)-1);$i++) {
                if ($i===0) {
                    if ($each[$i] == "'" || $each[$i] == '"') {
                        $brackets=$each[$i];
                        continue;
                    } else {
                        $brackets=null;
                        $each_list['key'].=$each[$i];
                        continue;
                    }
                } elseif ($each[$i] == $brackets && $each[$i-1] == "\\") {
                    $each_list['key'].=$each[$i];
                    continue;
                    
                // 2011/03/10 support allow[1]
                } elseif ($each[$i] == "[") { // start with [
                    $each_list['key'].=$each[$i];
                    $key_find_flags = true;
                    continue;
                } elseif ($each[$i] == "]") { // end with ]
                    $each_list['key'].=$each[$i];
                    $operbegin=$i+1;
                    break;
                // 2011/03/10 support allow[1] end
                
                } elseif ($each[$i] == " " || $each[$i] == $brackets) {
                    $operbegin=$i+1;
                    break;
                } elseif (($brackets == null && preg_match("/[^0-9a-zA-Z]/",$each[$i]))) {
                    $operbegin=$i;
                    break;
                } else {
                    $each_list['key'].=$each[$i];
                    continue;
                }
            }
            $each_list['key'] = stripslashes($each_list['key']); // escape \' \" \\
            if ($each_list['key'] == null) {
                $this->errstr="Query [".__LINE__."] : You have an error in your AQL syntax; $expr \n";
                return(false);
            }

            // take value from each 
            $brackets = null;
            $operend = null;
            for($i=(strlen($each)-1);$i>=0;$i--) {
                if ($i===(strlen($each)-1)) {
                    if ($each[$i] == "'" || $each[$i] == '"') {
                        $brackets=$each[$i];
                        continue;
                    } else {
                        $brackets=null;
                        $each_list['value']=$each[$i].$each_list['value'];
                        continue;
                    }
                } elseif ($each[$i] == $brackets && $each[$i-1] == "\\") { // when match brackets and match splash ingore
                    $each_list['value']=$each[$i].$each_list['value'];
                    continue;
                } elseif ($each[$i] == " " && $brackets != null) {  // when match " " but in brackets ingore
                    $each_list['value']=$each[$i].$each_list['value'];
                    continue;
                } elseif ($each[$i] == " " || $each[$i] == $brackets) { // when match " " or match brackets and not in brackets break
                    $operend=$i-1;
                    break;
                } elseif (($brackets == null && preg_match("/[^0-9a-zA-Z]/",$each[$i]))) {
                    $operend=$i;
                    break;
                } else {
                    $each_list['value']=$each[$i].$each_list['value'];
                    continue;
                }
            }
            // special word value == null is NULL
            if ($each_list['value'] == 'null') {
                $each_list['value'] = null;
            } else {
                $each_list['value'] = stripslashes($each_list['value']); // escape \' \" \\
            }
            // we support value is '' or "" or not exists
//            if ($each_list['value'] == null) {
//                $this->errstr="Query [".__LINE__."] : You have an error in your AQL syntax; $expr \n";
//                return(false);
//            }

            // take operator
            if ($disable_operator==true) {
            } else {                
                $each_list['operator'] = trim(strtolower(substr($each,($operbegin),($operend-$operbegin+1))));
                // checking operator valid
                if ($each_list['operator'] == '=') {
                } else {
                    $this->errstr="Query [".__LINE__."] : You have an error in your AQL syntax; $expr \n";
                    return(false);
                }
            }
            $sets[] = $each_list;    
        }

        return($sets);
    }
    
    /* ----------------------------------------------------------------
     * 
     * Private Function Query Command and process
     * run query command and construction results
     * 
     * ----------------------------------------------------------------
     */
    // this functions filter section
    private function query_command_filter_section($token,$sectionrange) {

        // filter with conditions
        for ($i=0;$i<=(count($token['conditions'])-1);$i++) {
            $oper = $token['conditions'][$i];
            
            // for the special section name unsection
            if ($oper['value']=="" || strtolower($oper['value']) == 'null') {
                $oper['value']='[unsection]';
            }

            // operator
            $tmp_sectionrange=array();
            foreach ($sectionrange as $each) {
                
                // value type only intger
                if (!preg_match('/[^0-9]/',$each)) {
                    if ($oper['operator'] == '<=' && $oper['value'] >= $each) {
                        $tmp_sectionrange[]=$each;
                        continue;
                    } elseif ($oper['operator'] == '>=' && $oper['value'] <= $each) {
                        $tmp_sectionrange[]=$each;
                        continue;
                    } elseif ($oper['operator'] == '<' && $oper['value'] > $each) {
                        $tmp_sectionrange[]=$each;
                        continue;
                    } elseif ($oper['operator'] == '>' && $oper['value'] < $each) {
                        $tmp_sectionrange[]=$each;
                        continue;
                    }
                }

                // other scalar
                if ($oper['operator'] == '<>' && $oper['value'] != $each) {
                    $tmp_sectionrange[]=$each;
                    continue;
                } elseif ($oper['operator'] == '=' && $oper['value'] == $each) {
                    $tmp_sectionrange[]=$each;
                    continue;
                // regexp match result
                } elseif ($oper['operator'] == 'like') {
                    // double %
                    if ($oper['value'][0] == '%' && $oper['value'][(strlen($oper['value'])-1)] == '%') {
                        $match = substr($oper['value'],1,-1);
                        if (preg_match('/^(.*)'.$match.'(.*)$/i',$each)) {
                            $tmp_sectionrange[]=$each;
                        }
                        continue;
                    // prefix %
                    } elseif ($oper['value'][0] == '%') {
                        $match = substr($oper['value'],1);
                        if (preg_match('/^(.*)'.$match.'$/i',$each)) {
                            $tmp_sectionrange[]=$each;
                        }
                        continue;
                    } elseif ($oper['value'][(strlen($oper['value'])-1)] == '%') {
                        $match = substr($oper['value'],0,(strlen($oper['value'])-1));
                        if (preg_match('/^'.$match.'(.*)$/i',$each)) {
                            $tmp_sectionrange[]=$each;
                        }
                        continue;
                    } else {
                        if ($oper['value'] == $each) {
                            $tmp_sectionrange[]=$each;
                        }
                        continue;
                    }
                }

            }

            // saving range data and init new foreach
            $sectionrange = $tmp_sectionrange;
            $tmp_sectionrange = null;
           
        }

        /*
         *  limit result data range
         */
        if ($token['limit_position'] > 0 && $token['limit_max'] > 0) {
            $sectionrange = array_slice($sectionrange, $token['limit_position'],$token['limit_max']);
        } elseif ($token['limit_position'] > 0) {
            $sectionrange = array_slice($sectionrange, 0, $token['limit_position']);
        }
        
        return($sectionrange);        
    }
    private function query_command_select($token) {

        // if not parsed conf try to open an do_parse it
        if (!$this->check_database_exists($token['tablename'])) {
            if (!$this->open_config_file($this->basedir.'/'.$token['tablename'])) {
                $this->errstr="Query [".__LINE__."] : You have an error in tablename(filename); ".$this->errstr;
                return(false);
            }
        }

        // get section range by section filter with conditions
        $dataobject = $this->get_config_array($token['tablename']);
        $sectionrange = $this->query_command_filter_section($token,array_keys($dataobject));

        /*
         * set output result format
         */
        $result = array();
        if ($token['result_ranges'][0] == 'count(*)') {
            $row=array();
            $row['count(*)']=count($sectionrange);
            $result['count(*)']=$row;
        } else {
            foreach ($sectionrange as $each) {
                
                $row = array();
                foreach ($token['result_ranges'] as $range) {
                    // count only
                    if ($range == 'count(*)') {
                        $row['count(*)'] = count($sectionrange);
                        break;
                    } elseif ($range == '*') {
                        $row = $dataobject[$each];
                        break;
                    } else {
                        if ($range == 'section') {
                            $row[$range] = $range;
                        } elseif (array_key_exists($range,$dataobject[$each])) {
                            $row[$range] = $dataobject[$each][$range];
                        } else {
                            $row[$range] = null;
                        }
                    }
                }

                $result[$each]=$row;
            }
        }
        $this->affected_rows = count($result);
        
        return($result);
    }
    
    // update command
    private function query_command_update($token) {

        // if not parsed conf try to open an do_parse it
        if (!$this->check_database_exists($token['tablename'])) {
            if (!$this->open_config_file($this->basedir.'/'.$token['tablename'])) {
                $this->errstr="Query [".__LINE__."] : You have an error in tablename(filename); ".$this->errstr;
                return(false);
            }
        }

        // get filter section
        $dataobject = $this->get_config_array($token['tablename']);
        $sectionrange = $this->query_command_filter_section($token,array_keys($dataobject));

        // put assign
        foreach ($sectionrange as $one_section) {
            foreach ($token['sets'] as $one_case) {
                $this->assign_editkey($one_section,$one_case['key'],$one_case['value']);
            }
        }
        
        // don't autocommit
        if ($this->autocommit == false) {
            return(true);
        }

        // auto commit
        $return = $this->commit($token['tablename']);

        return($return);
    }
    
    // alter command
    private function query_command_alter($token) {

        // if not parsed conf try to open an do_parse it
        if (!$this->check_database_exists($token['tablename'])) {
            if (!$this->open_config_file($this->basedir.'/'.$token['tablename'])) {
                $this->errstr="Query [".__LINE__."] : You have an error in tablename(filename); ".$this->errstr;
                return(false);
            }
        }

        // get filter section
        $dataobject = $this->get_config_array($token['tablename']);
        $sectionrange = $this->query_command_filter_section($token,array_keys($dataobject));
        
        // do delkey
        if ($token['type'] == 'drop') {
            // put assign
            foreach ($sectionrange as $one_section) {
                foreach ($token['sets'] as $one_case) {
                    $this->assign_delkey($one_section,$one_case['key']);
                }
            }
        } elseif ($token['type'] == 'add') {
            // put assign
            foreach ($sectionrange as $one_section) {
                foreach ($token['sets'] as $one_case) {
                    $this->assign_append($one_section,$one_case['key'],$one_case['value'],$token['macro'],$token['macro_args']);
                }
            }
        }

        // don't autocommit
        if ($this->autocommit == false) {
            return(true);
        }
        
        // auto commit
        $return = $this->commit($token['tablename']);

        return($return);
    }
    
    // insert command
    private function query_command_insert($token) {

        // if not parsed conf try to open an do_parse it
        if (!$this->check_database_exists($token['tablename'])) {
            if (!$this->open_config_file($this->basedir.'/'.$token['tablename'])) {
                $this->errstr="Query [".__LINE__."] : You have an error in tablename(filename); ".$this->errstr;
                return(false); 
            }
        }
        
        // checking section name
        $section = null;
        $datachunk = null;
        foreach ($token['sets'] as $id=>$one_case) {
            if ($one_case['key'] == "section") {
                $section = $one_case['value'];
            } else {
                $datachunk .= $one_case['key'].'='.$one_case['value']."\n";
            }
        }
        if ($section == null) {
            $this->errstr="Query [".__LINE__."] : You have an error in your syntax; missing key 'section' !\n";
            return(false);
        }

        // put assign
        $this->assign_addsection($section,$datachunk);

        // don't autocommit
        if ($this->autocommit == false) {
            return(true);
        }
        
        // auto commit
        $return = $this->commit($token['tablename']);

        return($return);
    }
    
    // delete command
    private function query_command_delete($token) {

        // if not parsed conf try to open an do_parse it
        if (!$this->check_database_exists($token['tablename'])) {
            if (!$this->open_config_file($this->basedir.'/'.$token['tablename'])) {
                $this->errstr="Query [".__LINE__."] : You have an error in tablename(filename); ".$this->errstr;
                return(false);
            }
        }

        // get filter section
        $dataobject = $this->get_config_array($token['tablename']);
        $sectionrange = $this->query_command_filter_section($token,array_keys($dataobject));

        // put assign
        foreach ($sectionrange as $one_section) {
            $this->assign_delsection($one_section);
        }

        // don't autocommit
        if ($this->autocommit == false) {
            return(true);
        }
        
        // auto commit
        $return = $this->commit($token['tablename']);

        return($return);
    }
    
    // use command
    private function query_command_use($token) {

        $return = $this->set('basedir',$token['basedir']);
        
        $this->affected_rows=0;
        
        $this->free_database();

        return($return);
    }
}

?>
