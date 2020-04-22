#!/usr/bin/perl
# this script run at frist booting only.
# changed :
# hoowa.sun (perl version)
# amy (shell version)
# 2009-12-15
sub run
{
my	$command = shift;
#	print "$command\n";
	system("$command");
return();
}

print "\nInstall Freeiris2 Now!!!.............\n\n";
sleep(2);

#
# by http://www.freeiris.org/
#
##!/bin/sh
## this script run at frist booting only.
## by http://www.freeiris.org/
#
#cd /tmp
chdir("/tmp/");

#
## For Install atom card driver
## r8101-kmod-base r8168-kmod-base
#/bin/rpm -Uvh r8*.rpm
&run("/bin/rpm -Uvh r8*.rpm");

#
##For Install Freeiris2
#/bin/tar zxvf freeiris2-current.tar.gz
#mv freeiris2-*-stable freeiris2-current
#cd /tmp/freeiris2-current
#/bin/chmod +x install.pl
#./install.pl --install
&run("/bin/tar zxvf freeiris2-current.tar.gz");
#set release name
my $version;
while(<./freeiris2-*-*>) {
        if (-d$_) {
                $_ =~ /\-(.*)/;
				&run("echo \"freeiris2 $1\" > /etc/redhat-release");
				last;
        }
}
if (-d$version) {
	chdir("/tmp/freeiris2-$version");
} else {
	&run("mv freeiris2-*-stable freeiris2-current");
	chdir("/tmp/freeiris2-current");
}
#install freeiris2
&run("/bin/chmod +x install.pl");
&run("./install.pl --install");

#
##Process /etc/rc.local
#/bin/cat >/etc/rc.local <<END
#
##<==Added by Freeiris2
#/usr/sbin/safe_asterisk &
##Added by Freeiris2 ==>
#END
$addlocal = qq~/bin/cat >/etc/rc.local <<END

#<==Added by Freeiris2
/usr/sbin/safe_asterisk &
#Added by Freeiris2 ==>
END~;
&run($addlocal);

#
##Process dns
#/bin/cat >/etc/resolv.conf <<END
#nameserver 202.106.0.20
#nameserver 202.96.64.68
#search localdomain
#END
$adddns = qq~
/bin/cat >/etc/resolv.conf <<END
nameserver 202.106.0.20
nameserver 202.96.64.68
search localdomain
END
~;
&run($adddns);

#
##Process issue
#/bin/cat >/etc/issue <<END
#     #==============================================#
#     #     ______             _      _     ___      #
#     #    |  ____|           (_)    (_)   |__ \\\\     #
#     #    | |__ _ __ ___  ___ _ _ __ _ ___   ) |    #
#     #    |  __| '__/ _ \\\\/ _ \\\\ | '__| / __| / /     #
#     #    | |  | | |  __/  __/ | |  | \\\\__ \\\\/ /_     #
#     #    |_|  |_|  \\\\___|\\\\___|_|_|  |_|___/____|    #
#     #                                              #
#     #==============================================#
#     #    sun bing <hoowa.sun\@freeiris.org>         #
#     #    www.freeiris.org                          #
#     #    Fonoirs Co.,Ltd.                          #
#     #==============================================#
#     #  root login default password : freeiris.org  #
#     #  web access user/pass :  admin / admin       #
#     #==============================================#
#     Linux Kernel \r on an \m 
#
#END
$addissue = qq~
/bin/cat >/etc/issue <<END
     #==============================================#
     #     ______             _      _     ___      #
     #    |  ____|           (_)    (_)   |__ \\\\\\     #
     #    | |__ _ __ ___  ___ _ _ __ _ ___   ) |    #
     #    |  __| '__/ _ \\\\\\/ _ \\\\\\ | '__| / __| / /     #
     #    | |  | | |  __/  __/ | |  | \\\\\\__ \\\\\\/ /_     #
     #    |_|  |_|  \\\\\\___|\\\\\\___|_|_|  |_|___/____|    #
     #                                              #
     #==============================================#
     #    sun bing <hoowa.sun\@freeiris.org>         #
     #    www.freeiris.org                          #
     #    Fonoirs Co.,Ltd.                          #
     #==============================================#
     #  root login default password : freeiris.org  #
     #  web access user/pass :  admin / admin       #
     #==============================================#
     Linux Kernel \\r on an \\m 

END
~;
&run($addissue);

#
##Process motd
#/bin/cat >/etc/motd <<END
#     #==============================================#
#     # Warnning!                                    #
#     # Please change all default password,          #
#     # its not safety.                              #
#     #                                              #
#     # How to See freeiris2 version:                #
#     # cat /etc/freeiris2/freeiris.conf|grep version#
#     #==============================================#
#    
#END
$addmotd =qq~
/bin/cat >/etc/motd <<END
     #==============================================#
     # Warnning!                                    #
     # Please change root and web default password  #
     # its not safety.                              #
     #                                              #
     # Note:                                        #
     # If Your Card ares A800P/A1200P/D115E you may #
     # need to make driver by your self.            #
     # simple type: /usr/bin/install_openvox_driver #
     #==============================================#
    
END
~;
&run($addmotd);

###############################
# BEGIN CUSTOM INSTALL
###############################

chdir("/tmp");
&run("cp install_openvox_driver /usr/bin/");
&run("chmod +x /usr/bin/install_openvox_driver");

###############################
# END CUSTOM INSTALL
###############################


##Process install file
#rm -rf /tmp/freeiris2.first.run.sh
&run("rm -rf /tmp/freeiris2.first.run.pl");

##Cleanup
#/bin/rm -rf /root/install*
#/bin/rm -rf /root/ana*
#/bin/rm -rf /tmp/*
&run("/bin/rm -rf /root/install*");
&run("/bin/rm -rf /root/ana*");
&run("/bin/rm -rf /tmp/*");
chdir("/tmp");

##Process ~/.bash_logout
#/bin/cat >>~/.bash_logout<<END
#history -c
#clear
#END
$addbash = qq~/bin/cat >>/root/.bash_logout<<END
history -c
clear
END
~;
&run($addbash);

##Prepare to reboot!
#echo "Install all done, system will be reboot after 3 seconds......."
#sleep 1
#echo "Install all done, system will be reboot after 2 seconds......."
#sleep 1
#echo "Install all done, system will be reboot after 1 seconds......."
#sleep 1
my $count=5;
for (1...5) {
	print "Install all done, system will be reboot after $count seconds.......\n";
	sleep(1);
	$count--;
}
print "Rebooting.....................\n";
&run("/sbin/reboot");

