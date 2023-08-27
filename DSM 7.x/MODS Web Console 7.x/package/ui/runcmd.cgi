#!/usr/bin/perl
use strict;
use warnings;
use File::Basename;
my $isDebug = 0; # debug modus enable for cmd-line test and to get query, token info
my $debug = ""; # debug text
my $isAdmin = 0; # part of administrators group
my $uiUser =''; # user running ui session
my $cgiUser = $ENV{LOGNAME} || getpwuid($<) || $ENV{USER};
my $dsmToken =''; #  synology dsm token against xss from dsm login.cgi
my $uiDir=$0; # get current working dir runing ui
$uiDir=~s#/[^/]+$##g; # cleaning to get dirname
require "$uiDir/syno_cgi.pl"; # base cgi functions get params, usr_priv
my $pkgName = basename($uiDir);

# *** common head cgi section: set html context, verify login
print "Content-type: text/html\n\n";
($isAdmin,$uiUser,$dsmToken) = usr_priv();
# app_priv used post usr_priv for non local admin
($isAdmin,$uiUser,$dsmToken) = app_priv('SYNO.SDS._ThirdParty.App.$pkgName') if !$isAdmin;

# autheticate in usr/app_priv returns uiUser null if it does not work e.g. in cmd-line mode
if (! $uiUser && $isDebug) { # tweak for debugging from cmd-line
	$isAdmin = 1;
	$uiUser = $cgiUser;
}
$debug .= "QueryStr: $ENV{'QUERY_STRING'}\n" if $isDebug && $ENV{'QUERY_STRING'};
$debug .= "CGI-User: $cgiUser, UI-User: $uiUser isAdmin: $isAdmin, dsmToken: $dsmToken\n" if $isDebug;
# exit with warning if not admin or in local admin group or with app-privilege
if ( ! $isAdmin ) {
	print "Please login as privileged admin for using this webpage";
	die;
}

# *** specific cgi section:
my %cmdVal; # key-value array to capture commands
my $cmd = param('cmd'); # the command incl. path to run
my $mode = param('mode'); # run in gackground mode?
my $action = param('action'); # action are the paramters

if (open(IN,"$uiDir/runcmds.txt")) { 
	while(<IN>) {
		chop();
		if ((!(/^#/))&&(/,/)) { 
			my ($script,$name)=/([^,]+),([^,]+)/;
			$script=~s/^\s*//;
			$name=~s/^\s*//;
			$cmdVal{$name}=$script;
		}
	}
	close(IN);
}

if ($cmdVal{$cmd}) { # cmd was found so run it

	if ( $mode && $mode eq 'bg' ) {
		system("/bin/sh " . $cmdVal{$cmd}." " . $action . " bgmode >/tmp/jitsi-cmd.out 2>&1 &");				
		print "Started in background mode $cmdVal{$cmd} $action bgmode. \nSee /tmp/jitsi-cmd.out and notifications for success.\n";
    }
    else {
		my $return;
		if (open (IN,$cmdVal{$cmd}." " . $action . " >/tmp/jitsi-cmd.out 2>&1 |")) {
			$return=<IN>;
	    	chop($return) if $return;
			print "$return\n";
		    close(IN);
			if (open(IN,'/tmp/jitsi-cmd.out')) { 
				while(<IN>) {
					chomp();
					#$_=~ s/[^[:print:]]+//g; # remove non-printable chars and colour codes if not run with --no-ansi
					s/[^[:print:]]+//g; # remove non-printable chars and colour codes from docker-compose
					s/\[32m//g; # remove non-printable chars
					s/\[0m\[[0-9]B//g; # remove non-printable chars
					s/\[[0-9]A\[2K/\n/g; # remove non-printable chars
					print "$_\n" if $_ ne '';
				}
				close(IN);
			}
			unlink('/tmp/jitsi-cmd.out');
		}
		else {
			print "failed to run $cmdVal{$cmd} $action \n";
		}
	}
}
else {
	print "error: command $cmd not part of registered ones to run\n";
}
