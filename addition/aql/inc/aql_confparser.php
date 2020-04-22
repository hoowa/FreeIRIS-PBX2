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
#    aql.confparser.php
#    Copyright (C) 2010, Fonoirs Co.,LTD.
#    By Sun bing <hoowa.sun@gmail.com>
#
*/

class aql_confparser {
    
    const VERSION='1.0';
    const AUTHOR='Sun Bing <hoowa.sun@freeiris.org>';
    
    // reload and reparse config file
    public $reload_when_save = true;
    // error messages
    public $errstr = NULL;
    // last save changed
    public $last_save_changed_filename = null;
    // last changed count
    public $last_save_changed_sections = array();
    // comment flags
    public $comment_flags = ';';
    
    // fill all config ref in to database
    private $database=array();
    // commit command listed
    private $commit_assign=array();


    // under construct
    public function __construct() {
    }

    // return confpaser version
    public function get_version() {
        return(self::VERSION);
    }
    
    // return parsed_dataref
    public function get_database() {
        return($this->database);
    }

    // return commit assign list
    public function get_commit_assign() {
        return($this->commit_assign);
    }
    
    /*
     *  getout parsed conf from database
     */
    public function get_config_array($filename)
    {
        return($this->database[$filename]['parsed_conf']);
    }    
    
    // checking database's filename exists
    // return true or false
    public function check_database_exists($filename) {
        if (array_key_exists($filename,$this->database)) {
            return(true);
        } else {
            return(false);
        }
    }
    
    /* 
     * free database to null
     */
    public function free_database()
    {
        $this->database=array();
        return(true);
    }

    /*-----------------------------------------------------------------
     * reading and open config file functions.
     *-----------------------------------------------------------------
     */
    /*
        read asterisk config files from file
    */
    public function open_config_file($put_filename) {

        // loaded?
        $filename = basename($put_filename);
        if (array_key_exists($filename,$this->database) == true) {
            $this->errstr=$filename."alreadly loaded\n";
            return(false);
        }
        
        // file exists?
        if (file_exists($put_filename)==false) {
            $this->errstr="file '".$filename."' not found!\n";
            return(false);
        }
        
        // read file into array and do parser and return them. fill in to database
        $this->database[$filename] = $this->do_parse($put_filename);
        
        return(true);
    }


    /*
        do parse config from resource_list
    */
    private function do_parse($put_filename) {

        $resource_array = array();
        $resource_array['resource_list'] = array();
        $resource_array['parsed_conf'] = array();
        $resource_array['parsed_conf']['[unsection]']=array();
        $resource_array['fullname']=$put_filename;
        $last_section_name = null;
        $dupcopykey_count=array();

        $handle = fopen($resource_array['fullname'], "r");
        while (!feof($handle)) {
            
            // get one line from file handle
            $eachline = fgets($handle);
            // put one line to resource_list array
            $resource_array['resource_list'][] = rtrim($eachline,"\n");
            
            // format eachline
            $line_sp = $this->fmt_cleanstring($eachline);
            if ($line_sp == NULL) continue;

            // find what's
            $first_char = $line_sp[0];
            
            // maybe find new section
            if ($first_char == "[") {
                
                preg_match('/^\[(.+)\]/',$line_sp,$matches);
                $last_section_name = $matches[1];
                $resource_array['parsed_conf'][$last_section_name]=array();
                continue;

            // maybe include syntax
            } elseif ($first_char == "#") {
                
                if (!$last_section_name) {
                    $resource_array['parsed_conf']['[unsection]'][$line_sp]=$line_sp;
                } else {
                    $resource_array['parsed_conf'][$last_section_name][$line_sp]=$line_sp;                    
                }
                continue;
                
            // find key=value
            } elseif (strpos($line_sp,'=') !== false) {
                
                //cut data and key
                $keyvalue = $this->fmt_cleankeyvalue($line_sp);
                
                // true last sections;
                $current_section = $last_section_name;
                if (!$current_section) {
                    $current_section = '[unsection]';
                }
                
                // check dupcopy keyname
                if (array_key_exists($keyvalue[0],$resource_array['parsed_conf'][$current_section])) {
                    
                    // does frist dupcopy
                    if (array_key_exists($keyvalue[0],$dupcopykey_count)==false) {
                        $dupcopykey_count[$keyvalue[0]]=1;
                        $resource_array['parsed_conf'][$current_section][$keyvalue[0].'[1]']=$keyvalue[1];
                    } else {
                        $dupcopykey_count[$keyvalue[0]]++;
                        $resource_array['parsed_conf'][$current_section][$keyvalue[0].'['.$dupcopykey_count[$keyvalue[0]].']']=$keyvalue[1];
                    }
                    continue;
                
                // normal key filling parsed_conf
                } else {
                    $resource_array['parsed_conf'][$current_section][$keyvalue[0]]=$keyvalue[1];
                    continue;
                }
                
            };
            

        }
        fclose($handle);

        return($resource_array);
    }
    
    /*
     * reload and parse config file.
     */
    public function open_reload($filename)
    {
        // check file in database?
        if (!array_key_exists($filename,$this->database)) {
            $this->errstr="you are not open file $filename \n";
            return(false);
        }
        // read file into array and do parser and return them. fill in to database
        $this->database[$filename] = $this->do_parse($this->database[$filename]['fullname']);
        return(true);
    }

    /*-----------------------------------------------------------------
     * format string functions.
     *-----------------------------------------------------------------
     */
    /*
        format clean string
    */
    private function fmt_cleanstring($line) {

        // remove \n \t etc...
        $line=trim($line);
        
        // if null return
        if ($line == "") return(NULL);

        // include syntax check
        if ($line[0] === '#') {
        // if comment checked
        } else {
            $matchcomment_a = strpos($line,$this->comment_flags);
            if ($matchcomment_a !== false) {
                $line = preg_replace('/(['.$this->comment_flags.'].*)/',"",$line,1);
            }
        }
//        $matchcomment_a = strpos($line,'#');
//        
//        // # at position 0 means include other config file
//        if ($matchcomment_a === 0) {
//            
//        // comment # not position 0 or ; in string to ignore
//        } elseif ($matchcomment_a !== false || strpos($line,';') !== false) {
//            
//            $line = preg_replace('/([\;|\#].*)/',"",$line,1);
//            
//        }

        return($line);
    }
    
    /*
        format clean key / value
    */
    private function fmt_cleankeyvalue($string) {
        $keyvalue = explode('=',$string);
    
        $keyvalue[0] = trim($keyvalue[0]);
        
        if ($keyvalue[1]) {
            $keyvalue[1] = trim($keyvalue[1]);
            $keyvalue[1] = ltrim($keyvalue[1],'>');
        };
        
        return($keyvalue);
    }
    
    /*-----------------------------------------------------------------
     * modify config file functions.
     *-----------------------------------------------------------------
     */
    /*
        change value with matched key in section.
        $aql_confparser->assign_editkey('section name|[unsection]','keyname','new_value');
    */
    public function assign_editkey($section,$key,$new_value)
    {
        $command=array();
        $command['action']='editkey';
        $command['section']=$section;
        if (preg_match('/(.*)\[([0-9]+)\]$/',$key,$matches)) {
            $command['key']=$matches[1];
            $command['flag']=$matches[2];
        } else {
            $command['key']=$key;
            $command['flag']=0;
        }
        $command['new_value']=$new_value;
        $this->commit_assign[] = $command;
        return(true);
    }
    
    /* 
     * drop section with section name.
     * 
     * $aql_parser->assign_delsection('section name|[unsection]')
     * 
     * [unsection] : will earse all keyvalue from config header.
     * 
    */
    public function assign_delsection($section)
    {
        $command=array();
        $command['action']='delsection';
        $command['section']=$section;
        $this->commit_assign[] = $command;
        return(true);
    }

    /*
        delkey from section in config files
        $aql_confparser->assign_delkey('section name|[unsection]','keyname');
    */
    public function assign_delkey($section,$key)
    {
        $command=array();
        $command['action']='delkey';
        $command['section']=$section;
        if (preg_match('/(.*)\[([0-9]+)\]$/',$key,$matches)) {
            $command['key']=$matches[1];
            $command['flag']=$matches[2];
        } else {
            $command['key']=$key;
            $command['flag']=0;
        }
        $this->commit_assign[] = $command;
        return(true);
    }
    
    /*
     * add section with special name and data chunk.
     * $aql_confparser->assign_addsection('section',array($datachunk));
    */
    public function assign_addsection($section,$datachunk)
    {
        if (count($datachunk) <= 0 ) {
            return(false);
        }
        $command=array();
        $command['action']='addsection';
        $command['section']=$section;
        $command['keyvalue'] = explode("\n",$datachunk);
        $this->commit_assign[] = $command;
        return(true);
    }
    
    /*
     * append data around with section name.
     * 
     * $aql_confparser->assign_append('section name|[unsection]','newkeyname','newvalue','null|before|after','null|match_keyname');
     * 
     * null : append to end of section, or before space line in section.
     * before : append before keyname must set 'match_keyname'
     * after : append after keyname must set 'match_keyname'
    */
    public function assign_append($section,$key,$value,$position=null,$match_key=null)
    {
        $command=array();
        $command['action']='append';
        $command['section']=$section;
        $command['key']=$key;
        $command['value']=$value;
        $command['position']=$position;
        if (preg_match('/(.*)\[([0-9]+)\]$/',$match_key,$matches)) {
            $command['match_key']=$matches[1];
            $command['flag']=$matches[2];
        } else {
            $command['match_key']=$match_key;
            $command['flag']=0;
        }
        $this->commit_assign[] = $command;
        return(true);
    }

    /* 
     * change and save to config file.
     * filename must be opened, you can't save filename but don't open.
     */
    public function save_config_file($filename)
    {
        // check file in database?
        if (!array_key_exists($filename,$this->database)) {
            $this->errstr="you are not open file $filename \n";
            return(false);
        }

        // nothing to do
        if (count($this->commit_assign) <= 0) {
            return(true);
        }
        $this->last_save_changed_sections=array();

        // input resource_list
        $used_resource=$this->database[$filename]['resource_list'];

        foreach ($this->commit_assign as $one_case) {
            if ($one_case['action'] == 'editkey' || $one_case['action'] == 'delkey') {
                $used_resource = $this->do_editkey($one_case,$used_resource);
            } elseif ($one_case['action'] == 'delsection') {
                $used_resource = $this->do_delsection($one_case,$used_resource);
            } elseif ($one_case['action'] == 'addsection') {
                $used_resource = $this->do_addsection($one_case,$used_resource,$filename);
            } elseif ($one_case['action'] == 'append') {
                $used_resource = $this->do_append($one_case,$used_resource);
            }
        }

        // save file and check new_file
        if (count($used_resource) < 0) {
            $this->errstr="no listed used_resource\n";
            $this->last_changed_file=null;
            return(false);
        }
        // not changed
        if (count($this->last_save_changed_sections)==0) {
            return(true);
        }

        // write to disk
        $save_handle = fopen($this->database[$filename]['fullname'],'w');
        if (!$save_handle) {
            $this->errstr="Can't write : ".$filename."\n";
            return(false);
        };
        flock($save_handle, LOCK_EX | LOCK_NB) or die("Unable to lock file !");
        fwrite($save_handle,implode("\n", $used_resource));
        //fwrite($save_handle,"\n");
        flock($save_handle,LOCK_UN);
        fclose($save_handle);

        // last changed filename
        $this->last_save_changed_filename=$filename;
        // clean assign
        $this->commit_assign=array();
        // reload
        if ($this->reload_when_save == true) {
            $this->open_reload($filename);
        }

        return(true);
    }

    // do parse editkey and delkey
    private function do_editkey($one_case,$used_resource)
    {
        $new_resource=array();
        $last_section_name='[unsection]';
        $auto_save=false;
        $find_flag=0;

        foreach ($used_resource as $id=>$one_line) {

            // tune on auto save
            if ($auto_save==true) {
                $new_resource = array_merge($new_resource,array_slice($used_resource, $id));
                break;
            }

            $line_sp = $this->fmt_cleanstring($one_line);

            // income new section
            if ($line_sp == "") {
            } elseif (preg_match("/^\[(.+)\]$/",$line_sp,$matches)==true) {
                $last_section_name = $matches[1];
            } elseif ($last_section_name == $one_case['section'] && preg_match("/\=/",$line_sp)) {

                $keyvalue=$this->fmt_cleankeyvalue($line_sp);
                if ($keyvalue[0] == $one_case['key'] && $one_case['flag'] == $find_flag) {
                    
                    // auto save after datas
                    $auto_save = true;
                    
                    // no need change
                    if (array_key_exists('new_value',$one_case) && $keyvalue[1] == $one_case['new_value']) {
                        
                    // delete need continue next because if add in new_resource will come to null line.
                    } elseif ($one_case['action'] == 'delkey') {
                        $one_line = "";
                        $this->last_save_changed_sections[$last_section_name] = true;
                        continue;
                        
                    // edit do
                    } else {
                        $one_line = $keyvalue[0]."=".$one_case['new_value'];
                        $this->last_save_changed_sections[$last_section_name] = true;
                    }
                    
                } elseif ($keyvalue[0] == $one_case['key']) {
                    $find_flag++;
                }

            }
            
            $new_resource[] = $one_line;
        }
        return($new_resource);
    }

    // do append of section
    private function do_append($one_case,$used_resource)
    {
        $new_resource=array();
        $last_section_name='[unsection]';
        $auto_save=false;
        $find_flag=0;
        $find_section=false;

        foreach ($used_resource as $id => $one_line) {

            // tune on auto save
            if ($auto_save==true) {
                $new_resource = array_merge($new_resource,array_slice($used_resource, $id));
                break;
            }
            
            $line_sp = $this->fmt_cleanstring($one_line);
            
            if (preg_match("/^\[(.+)\]/",$line_sp,$matches)==true) {
                $last_section_name = $matches[1];
            }

            // open insert end of section
            if ($one_case['position'] == '') {
    
                // find end with next section name (next section name header of)
                if ($last_section_name != $one_case['section'] && $id==0 && $one_case['section'] == '[unsection]') {
                    $find_section=false;
                    $new_resource[] = $one_case['key'].'='.$one_case['value'];
                    $this->last_save_changed_sections[$last_section_name] = true;
                    $auto_save=true;
                // find start
                } elseif ($last_section_name == $one_case['section'] && $find_section==false) {
                    $find_section=true;
                    
                // find end with space
                } elseif ($last_section_name == $one_case['section'] && $find_section==true && $one_line == "") {
                    $find_section=false;
                    $new_resource[] = $one_case['key'].'='.$one_case['value'];
                    $this->last_save_changed_sections[$last_section_name] = true;
                    $auto_save=true;
                // find end with next section name
                } elseif ($last_section_name != $one_case['section'] && $find_section==true) {
                    $find_section=false;
                    $new_resource[] = $one_case['key'].'='.$one_case['value'];
                    $this->last_save_changed_sections[$last_section_name] = true;
                    $auto_save=true;
                }
                
            // insert before match_key
            } elseif ($one_case['position'] == 'before' && preg_match("/\=/",$line_sp)) {
                
                $keyvalue=$this->fmt_cleankeyvalue($line_sp);
                if ($last_section_name == $one_case['section'] && $one_case['match_key'] == $keyvalue[0] && $one_case['flag'] == $find_flag) {
                    $new_resource[] = $one_case['key'].'='.$one_case['value'];
                    $this->last_save_changed_sections[$last_section_name] = true;
                    $auto_save=true;
                } elseif ($last_section_name == $one_case['section'] && $one_case['match_key'] == $keyvalue[0]) {
                    $find_flag++;
                }
                
            // insert after match_key
            } elseif ($one_case['position'] == 'after' && preg_match("/\=/",$line_sp)) {
                
                $keyvalue=$this->fmt_cleankeyvalue($line_sp);
                if ($last_section_name == $one_case['section'] && $one_case['match_key'] == $keyvalue[0] && $one_case['flag'] == $find_flag) {
                    $new_resource[] = $one_line;
                    $one_line = $one_case['key'].'='.$one_case['value'];
                    $this->last_save_changed_sections[$last_section_name] = true;
                    $auto_save=true;
                } elseif ($last_section_name == $one_case['section'] && $one_case['match_key'] == $keyvalue[0]) {
                    $find_flag++;
                }
            }

            $new_resource[] = $one_line;
        }

        // if find_section but resource end append to end of new_resource
        if ($find_section == true) {
            $find_section = false;
            $new_resource[] = $one_case['key'].'='.$one_case['value'];
            $this->last_save_changed_sections[$last_section_name] = true;
        }

        return($new_resource);
    }
    
    private function do_addsection($one_case,$used_resource,$filename)
    {
        // checking in database exists?
        if (array_key_exists($one_case['section'],$this->database[$filename]['parsed_conf'])) {
            return($used_resource);
        }

        $section = "[".$one_case['section']."]";

        $used_resource = array_merge((array)$used_resource, (array)$section,(array)$one_case['keyvalue']);
        
        $this->last_save_changed_sections[$one_case['section']] = true;
        
        return($used_resource);
    }
    
    private function do_delsection($one_case,$used_resource)
    {
        $new_resource=array();
        $last_section_name='[unsection]';
        $auto_save=false;

        foreach ($used_resource as $id=>$one_line) {

            // tune on auto save
            if ($auto_save==true) {
                $new_resource = array_merge($new_resource,array_slice($used_resource, $id));
                break;
            }

            $line_sp = $this->fmt_cleanstring($one_line);

            // end of delsection
            if ($last_section_name == $one_case['section'] && preg_match("/^\[(.+)\]/",$line_sp)==true) {
                $this->last_save_changed_sections[$last_section_name] = true;
                $auto_save = true;
            // find section
            } elseif (preg_match("/^\[(.+)\]/",$line_sp,$matches)) {
                $last_section_name=$matches[1];
                // section compared
                if ($one_case['section'] == $last_section_name) {
                    $this->last_save_changed_sections[$last_section_name] = true;
                    continue;
                }
            // skip if in delsection
            } elseif ($last_section_name == $one_case['section']) {
                continue;
            }

            $new_resource[] = $one_line;
        }

        return($new_resource);
    }

}


?>
