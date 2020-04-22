#
#       Agispeedy - The Agispeedy is an implemention of AGI in asterisk.
#       Copyright (C) 2010, Fonoirs Co.,LTD.
#       By Sun bing <hoowa.sun@gmail.com>
#
#       See http://www.freeiris.org/cn/agispeedy for more information about
#       the Agispeedy project.
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
#
#       This is Agispeedy Utils Perl Module

# write to log file 
sub write_to_log_hook {
my  ($self, $level, $msg) = @_;
my  $prop = $self->{server};
my  $time = localtime();

    chomp $msg;
    $msg =~ s/([^\n\ -\~])/sprintf("%%%02X",ord($1))/eg;
    $msg = "[$time] $msg";

    if( $prop->{log_file} ){
        print STDERR $msg, "\n" if ($msg ne "[$time] Process Backgrounded");
    }elsif( $prop->{setsid} ){
        # do nothing
    }else{
        my $old = select(STDERR);
        print $msg. "\n";
        select($old);
    }

}


# when child request call followed function
sub process_request
{
my	$self = shift;
my  $server_prop = $self->{server};

    # create Asterisk::AGI handle
    $server_prop->{agi} = Asterisk::AGI->new;
    $server_prop->{agi}->ReadParse();
    
    # open agi debug if set verbose
    if ($OPT{'GET_VERBOSE'}) {
        $server_prop->{agi}->_debug(5);
    }
    
    # GET PARSE PARAM
my  (%params,$request_all);
my  ($scriptname, $param_string) = $server_prop->{agi}{env}{request} =~ m/\/(\w+)\?*([^\/]*)$/;
#   support asterisk 1.4.X only
#    foreach (split(/[&;]/,$param_string)) {
#    my  ($p,$v) = split(/\=/,$_,2);
#        $params{$p} = $v;
#    }
#   support asterisk 1.6.X and 1.8.X
    foreach  (keys %{$server_prop->{agi}{env}}) {
        if ($_ =~ /arg\_/) {
        my  ($p,$v) = split(/\=/,$server_prop->{agi}{env}{$_},2);
            $params{$p} = $v;
            $request_all .= "$p=$v,";
        }
    }
    if (defined$request_all) {
        chop($request_all);
        $server_prop->{agi}{env}{request_all} = $server_prop->{agi}{env}{request}.','.$request_all;
    } else {
        $server_prop->{agi}{env}{request_all} = $server_prop->{agi}{env}{request};
    }
#   end
    $server_prop->{agi}{params} = \%params;
    $server_prop->{agi}{scriptname} = $scriptname;


    #------------------------------------------------------------------
    # load agispeedy script modules
    #------------------------------------------------------------------
    if (exists($STATIC_MODULES{$scriptname})) {

        $self->log(3, "Request-static : ".$server_prop->{agi}{env}{request});
        $self->$scriptname() if($self->can($scriptname));

    } elsif ($OPT{'AGIMOD_ENABLE_DYNAMIC'}) {

        #trying from dynamic
        if (-e"$OPT{'VAR'}/$scriptname.$OPT{'AGIMOD_DYNAMIC_EXT'}") {

            $self->log(3, "Request-dynamic : ".$server_prop->{agi}{env}{request});

            #load static module
            do "$OPT{'VAR'}/$scriptname.$OPT{'AGIMOD_DYNAMIC_EXT'}";
            if ($@) {
                $self->log(2, "Dynamic Module error : ".$method." ".$@."");
                $server_prop->{agi}->hangup();
                exit;
            }

            if (!defined *{$scriptname}{CODE}) {
                $self->log(2, "Dynamic Module failed: No Entry function $scriptname found!");
                $server_prop->{agi}->hangup();
                exit;
            }

            #run current agispeedy script modules
            $self->$scriptname() if($self->can($scriptname));

        #not found
        } else {
            $self->log(2, "Dynamic Module error : $scriptname Not Found!");
            $self->{server}{agi}->hangup();
            exit;
        }

    } else {
        $self->log(2, "Module : $scriptname Not Found!");
        $self->{server}{agi}->hangup();
        exit;
    }
    
}

1;
