# -*- perl -*-
#
#  Net::Server::Proto::UDP - Net::Server Protocol module
#
#  $Id: UDP.pm,v 1.13 2007/02/03 05:56:09 rhandom Exp $
#
#  Copyright (C) 2001-2007
#
#    Paul Seamons
#    paul@seamons.com
#    http://seamons.com/
#
#  Modified 2005 by Timothy Watt
#    Added ability to deal with broadcast packets.
#
#  This package may be distributed under the terms of either the
#  GNU General Public License
#    or the
#  Perl Artistic License
#
#  All rights reserved.
#
################################################################

package Net::Server::Proto::UDP;

use strict;
use vars qw($VERSION);
use base qw(Net::Server::Proto::TCP);

$VERSION = $Net::Server::VERSION; # done until separated

sub object {
  my $type  = shift;
  my $class = ref($type) || $type || __PACKAGE__;

  my $sock = $class->SUPER::object( @_ );

  $sock->NS_proto('UDP');

  ### set a few more parameters
  my($default_host,$port,$server) = @_;
  my $prop = $server->{server};

  ### read any additional protocol specific arguments
  $server->configure({
    udp_recv_len   => \$prop->{udp_recv_len},
    udp_recv_flags => \$prop->{udp_recv_flags},
    udp_broadcast  => \$prop->{udp_broadcast},
  });

  $prop->{udp_recv_len} = 4096
    unless defined($prop->{udp_recv_len})
    && $prop->{udp_recv_len} =~ /^\d+$/;

  $prop->{udp_recv_flags} = 0
    unless defined($prop->{udp_recv_flags})
    && $prop->{udp_recv_flags} =~ /^\d+$/;

  $prop->{udp_broadcast} = undef
    unless defined($prop->{udp_broadcast})
    && $prop->{udp_broadcast};

  $sock->NS_recv_len(   $prop->{udp_recv_len} );
  $sock->NS_recv_flags( $prop->{udp_recv_flags} );

  return $sock;
}


### connect the first time
### doesn't support the listen or the reuse option
sub connect {
  my $sock   = shift;
  my $server = shift;
  my $prop   = $server->{server};

  my $host  = $sock->NS_host;
  my $port  = $sock->NS_port;

  my %args = ();
  $args{LocalPort} = $port;                  # what port to bind on
  $args{Proto}     = 'udp';                  # what procol to use
  $args{LocalAddr} = $host if $host !~ /\*/; # what local address (* is all)
  $args{Reuse}     = 1;  # allow us to rebind the port on a restart
  $args{Broadcast} = 1 if $prop->{udp_broadcast};

  ### connect to the sock
  $sock->SUPER::configure(\%args)
    or $server->fatal("Can't connect to UDP port $port on $host [$!]");

  $server->fatal("Back sock [$!]!".caller())
    unless $sock;

}


1;

__END__

=head1 NAME

  Net::Server::Proto::UDP - Net::Server UDP protocol.

=head1 SYNOPSIS

See L<Net::Server::Proto>.

=head1 DESCRIPTION

Protocol module for Net::Server.  This module implements the
SOCK_DGRAM socket type under INET (also known as UDP).
See L<Net::Server::Proto>.

=head1 PARAMETERS

The following paramaters may be specified in addition to
normal command line parameters for a Net::Server.  See
L<Net::Server> for more information on reading arguments.

=over 4

=item udp_recv_len

Specifies the number of bytes to read from the UDP connection
handle.  Data will be read into $self->{server}->{udp_data}.
Default is 4096.  See L<IO::Socket::INET> and L<recv>.

=item udp_recv_flags

See L<recv>.  Default is 0.

=back

=head1 QUICK PARAMETER LIST

  Key               Value                    Default

  ## UDP protocol parameters
  udp_recv_len      \d+                      4096
  udp_recv_flags    \d+                      0
  udp_broadcast     bool                     undef

=head1 LICENCE

Distributed under the same terms as Net::Server

=cut

