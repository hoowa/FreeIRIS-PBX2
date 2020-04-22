#
#       This is Agispeedy Hooks provides a number of "hooks" allowing for
#       servers servers layered on top of Net::Server to respond at 
#       different levels of execution without having to "SUPER" class the 
#       main built-in methods. 
#
#       Current this file your can place hook coding.
#
#       There was may other hooks your can find at 
#       http://search.cpan.org/~rhandom/Net-Server-0.97/lib/Net/Server.pod
#


#
#       configure_hook : This hook takes place immediately after the 
#                        Agispeedy is run.
#
sub configure_hook {

    $self = shift;

    $self->{server}->{port} = $OPT{'HOST'}.':'.$OPT{'PORT'};
    $self->{server}->{user} = $OPT{'USER'};
    $self->{server}->{group} = $OPT{'GROUP'};
    $self->{server}->{min_servers} = $OPT{'MIN_SERVERS'};
    $self->{server}->{min_spare_servers} = $OPT{'MIN_SPARE_SERVERS'};
    $self->{server}->{max_spare_servers} = $OPT{'MAX_SPARE_SERVERS'};
    $self->{server}->{max_servers} = $OPT{'MAX_SERVERS'};
    $self->{server}->{max_requests} = $OPT{'MAX_REQUESTS'};
    $self->{server}->{log_level} = 3;
    $self->{server}->{log_file} = $OPT{'LOG_FILE'};
    $self->{server}->{setsid} = $OPT{'SETSID'};
    $self->{server}->{pid_file} = $OPT{'PID'}.'/'.$OPT{'CFG_MAINNAME'}.'.pid';

    $self->{server}->{check_for_dead} = 16;
    $self->{server}->{check_for_waiting} = 8;


    #------------------------------------------------------------------
    # pid checking
    #------------------------------------------------------------------
    if ($^O eq 'linux' && -e$self->{server}->{pid_file}) {
    my  $pid_number;
        open(READ,$self->{server}->{pid_file}) or die "Can't open pid!";
        read(READ,$pid_number,32);
        close(READ);
        chomp($pid_number);
        
        if (-e"/proc/$pid_number/cmdline" && $pid_number ne $$) {
        my  $pid_cmdline = `cat /proc/$pid_number/cmdline`;
            chomp($pid_cmdline);
            #pid found
            if ($pid_cmdline =~ /$OPT{'CFG_MAINNAME'}/) {
                $self->log(1,"Agispeedy Already running: $pid_number");
                $self->log(1,"Exit...");
                exit;
                #system("kill -9 $pid_number");
                #sleep(1);
                #system("kill $pid_number");
            #pid not this script
            } else {
                unlink($self->{server}->{pid_file});
            }
        
        #pid not exists
        } else {
            unlink($self->{server}->{pid_file});
        }
        #sleep(2);
    }

}


#
#       configure_hook : This hook occurs just after the reading of 
#                        configuration parameters and initiation of logging
#                        and pid_file creation.
#
sub post_configure_hook {
    
    $self->log(1,"Agispeedy $OPT{VERSION} services on...");

    #------------------------------------------------------------------
    # Preload static Agispeedy Modules
    #------------------------------------------------------------------
        # announce static Agispeedy module struction
        our (%STATIC_MODULES,@STATIC_MODULES_LIST);    
    if ($OPT{'AGIMOD_ENABLE_STATIC'}) {

        while (<$OPT{'VAR'}/*.$OPT{'AGIMOD_STATIC_EXT'}>) {
            push(@STATIC_MODULES_LIST,$_);
        }

        foreach (sort @STATIC_MODULES_LIST) {
            next if (!-e$_);
            
            #file register
        my	$scriptname = basename($_);
            $scriptname =~ s/\.(.*)//;

            #load static Agispeedy modules
            do $_;
            if ($@) {
                $self->log(1,"Loading static failed: ".$_."\n".$@);
                exit;
            }

            if (!defined *{$scriptname}{CODE}) {
                $self->log(1,"Loading static failed: No Entry function $mname found!");
                warn "Error function '$mname' Not found in $_\n";
                exit;
            }

            #saving
        my	@filestat = stat($_);
            $STATIC_MODULES{$scriptname} = {
                'path'=>$_,
                'regtime'=>time,
                'filestat'=>\@filestat,
            };

            $self->log(2,"Loading static : ".$scriptname);
        }
    }

    return(1);
}


#       child_init_hook : This hook takes place immediately after the
#                        child process was init. if you want to make fast
#                        database connect, your can write your database handle
#                        followed sub child_init_hook()
#
sub child_init_hook {
    
    # example: mysql connect at child process init.
    # followed example copy from freeiris
	##global configure
#my	(%fri2conf);
	#tie %fri2conf, 'Config::IniFiles', ( -file => "/etc/freeiris2/freeiris.conf" );
	#$self->{server}->{fri2conf} = \%fri2conf;

	##connect database
	#$self->database_pconnect();
    
	#if (!defined $self->{server}{dbh} || !$self->{server}{dbh}->ping) {

		#$self->{server}{dbh} = DBI->connect("DBI:mysql:database=".$self->{server}->{fri2conf}->{'database'}{'dbname'}.
											#";host=".$self->{server}->{fri2conf}->{'database'}{'dbhost'}.
											#";port=".$self->{server}->{fri2conf}->{'database'}{'dbport'}.
											#";mysql_socket=".$self->{server}->{fri2conf}->{'database'}{'dbsock'},
										#$self->{server}->{fri2conf}->{'database'}{'dbuser'},
										#$self->{server}->{fri2conf}->{'database'}{'dbpasswd'},
										#{'RaiseError' => 1});
		#$self->log(4, "Database Connected!");

	#}

}

1;
