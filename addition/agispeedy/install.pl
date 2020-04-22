#!/usr/bin/perl
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
#=================================================================
# basic require library
#=================================================================
use FindBin qw($Bin);
use Getopt::Long qw(:config no_ignore_case bundling);
use File::Basename;
use Data::Dumper;

$|=1;
my	$INPUT = STDIN;
my	$OUTPUT = STDOUT;
my	$ERRPUT = STDERR;

my  %OPT;
    $OPT{'PREFIX'}='/agispeedy';
    $OPT{'help'}='';

    # get argv options
my  $GetOptionsResult = GetOptions(
            'help|?|h'=>\$OPT{'GET_HELP'},
            'prefix|p=s'=>\$OPT{'PREFIX'},
        );
if (!$GetOptionsResult) {
    exit;
}
    
if ($OPT{'GET_HELP'}) {
    print   "Agispeedy Installer\n".
            "\n".
            "Usage: $0 [options]\n".
            "  -?, --help          Display this help and exit.\n".
            "  -p, --prefix=path   install target base directory.\n";
    exit;    
}

&println('response',"Agispeedy Installer");
&println('error',"Your are not root") if ($< ne '0');

#---------------------------------------------checking
if (-e"/etc/redhat-release") {
    $OPT{'os'}='redhat';
}

#---------------------------------------------copying
&println('input',"continue to install agispeedy to $OPT{PREFIX} (yes/no)?");
if (<STDIN> !~ /^yes/) {
    exit;
}
&println('response',"Copying files");

if (!-d$OPT{PREFIX}) {
    system("mkdir $OPT{PREFIX}");
}
system("cp -avf $Bin/bin $OPT{PREFIX}/");
system("cp -avf $Bin/etc $OPT{PREFIX}/");
system("cp -avf $Bin/lib $OPT{PREFIX}/");
system("cp -avf $Bin/log $OPT{PREFIX}/");
system("cp -avf $Bin/var $OPT{PREFIX}/");

&println('response',"set /etc/agispeedy");
system("ln -s $OPT{PREFIX}/etc /etc/agispeedy");

if ($OPT{'os'} eq 'redhat' && $OPT{PREFIX} eq '/agispeedy') {
	&println('response',"set agispeedy services");
	system("cp -avf $Bin/contrib/agispeedy.init.centos /etc/rc.d/init.d/agispeedy");
	system("chkconfig --add agispeedy");
} else {
	&println('response',"install service script skiped.");
	&println('response',"please manual install contrib/agispeedy.init.centos to your system.");
}

    
sub println
{
my $type = shift;
my $msg = shift;

	if ($type eq 'step') {
		print $OUTPUT "\n[STEP] $msg\n";
	} elsif ($type eq 'input') {
		print $OUTPUT "    [INPUT] $msg";
	} elsif ($type eq 'response') {
		print $OUTPUT "  [RESPONSE] $msg\n";
	} elsif ($type eq 'failed') {
		print $OUTPUT "  [FAILED] $msg\n";
	} elsif ($type eq 'error') {
		print $ERRPUT "\n[ERROR] $msg\n\n";
		exit;
	} else {
		print $OUTPUT "$msg\n";
	}

return();
}
    
