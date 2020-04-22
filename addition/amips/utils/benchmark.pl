#!/usr/bin/perl
#
# benchmark connect to amips server.
#
use Time::HiRes qw(usleep gettimeofday tv_interval);
use Getopt::Long qw(:config no_ignore_case bundling);
use IO::Socket qw(:DEFAULT :crlf);
use IO::Select;
$|=1;

warn "benchmark AMI / AMIPS......\n";

# get argv options
$OPT_RESULT = GetOptions(
        'maxcon=i'=>\$opt_maxcon,
        'sendping=s'=>\$opt_sendping,
        'interval=i'=>\$opt_interval,
        'worktime=i'=>\$opt_worktime,
        'addr=s'=>\$opt_addr,
        'user=s'=>\$opt_user,
        'pass=s'=>\$opt_pass,
        );
exit unless ($OPT_RESULT);

if (!$opt_maxcon || !$opt_sendping || !$opt_worktime || !$opt_addr || !$opt_user || !$opt_pass) {
print qq~
usage: $0 --options --options ....

maxcon      max connected clients to server limited.
sendping    [yes/no] allow to send action ping to server to keep alive.
interval    sendping interval is in microseconds.
worktime    each client disconnect after worktime in seconds.
addr        ami server, format is [host:port]
user        ami username
pass        ami password
~;
exit;
}

$opt_interval = 1 if (!$opt_interval);

my  $select = IO::Select->new();

my  %status;

$status{'connect_try'}=0;
$status{'connected'}=0;
$status{'logon'}=0;
$status{'startconnect_epoch'}=[gettimeofday];
$status{'startconnect_string'}=gettimeofday;

#creating socket
my  (%clients);
for (1...$opt_maxcon) {
my  $socket = IO::Socket::INET->new(PeerAddr=>$opt_addr);
    
    $status{'connect_try'}++;

    if (!$socket) {
        warn "Connect to server failed\n";
        next;
    }
    $status{'connected'}++;

    #try to login
    $buffer = '';
    sysread($socket,$buffer,30000);
    syswrite($socket,"Action: login\r\nUsername: freeiris\r\nSecret: freeiris\r\n\r\n");
    $buffer = '';
    sysread($socket,$buffer,30000);
    if ($buffer !~ /Authentication accepted/) {
        warn "Authentication Failed\n";
        next;
    }
    $status{'logon'}++;
    
    $select->add($socket);
    $clients{$socket}{'sock'}=$socket;
    $clients{$socket}{'worktime'}=[gettimeofday];

    if (($status{'connect_try'}/10) == int($status{'connect_try'}/10)) {
            warn "connect tryed $status{'connect_try'}\n";
    }
    # each clients sleep 100ms
    #usleep(100_000);
}

$status{'endconnect_epoch'}=[gettimeofday];
$status{'endconnect_string'}=gettimeofday;
$status{'connect_consuming'} = tv_interval($status{'startconnect_epoch'},$status{'endconnect_epoch'});
$status{'connect_eachconsuming'} = $status{'connect_consuming'}/$status{'connect_try'};

$status{'clients_workdone'}=0;
$status{'clients_failed'}=0;
$status{'clients_pings'}=0;

while(1) 
{
    if (!&checkworktime) {
        last;
    }

    last if (!%clients);

my  @ready = $select->can_read(1);
    foreach my $handle (@ready) {
        #read from it
    my  $buffer = '';
        sysread($handle,$buffer,30000);
    }
    foreach  ($select->has_exception(1)) {
        $select->remove($handle);
        delete($clients{$handle});
        $status{'clients_failed'}++;
        warn "failed $_ client. \n";
    }

    if ($opt_sendping eq 'yes') {
    my  @writable = $select->can_write();
        foreach my $handle (@writable) {
            #send ping command
            syswrite($handle,"Action: ping\r\nActionID: ".time()."\r\n\r\n",30000);
            $status{'clients_pings'}++;
            if (($status{'clients_pings'}/100) == int($status{'clients_pings'}/100)) {
                warn "send ping $status{'clients_pings'}\n";
            }
        }

        # each clients sleep 100ms
        usleep($opt_interval.'_000');

    } else {
        usleep(100_000);
    }

}

print qq~
----CONNECT------------------------------------------
start time  : $status{startconnect_string}s
end time    : $status{'endconnect_string'}s
consuming   : $status{'connect_consuming'}s    each : $status{'connect_eachconsuming'}s
maxcon      : $opt_maxcon
connected   : $status{connected}
success logn : $status{logon}

----WORKTIME------------------------------------------
worktime    : $opt_worktime\s
workdone    : $status{'clients_workdone'}
failed      : $status{'clients_failed'}
pings       : $status{'clients_pings'}

------------------------------------------------------
~;

sub checkworktime
{
my  @clients = keys %clients;

    return(0) if ($#clients < 0);

    foreach my $handle (@clients) {
    my  $cur = [gettimeofday];
    my  $time = tv_interval($clients{$handle}{worktime},$cur);
        if ($time >= $opt_worktime) {
            $select->remove($handle);
            $clients{$handle}{sock}->shutdown(1);
            delete($clients{$handle});
            $status{'clients_workdone'}++;

            if (($status{'clients_workdone'}/10) == int($status{'clients_workdone'}/10)) {
                    warn "worktime done $status{'clients_workdone'}\n";
            }

        }
    }

return(1);
}
exit;
