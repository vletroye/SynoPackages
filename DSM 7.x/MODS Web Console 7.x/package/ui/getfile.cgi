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
($isAdmin,$uiUser,$dsmToken) = app_priv('SYNO.SDS.App.$pkgName') if !$isAdmin;

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
my $action = param('action'); # action has the cfg-filename to parse
my %fileVal; # key-value array to capture files

if (open(IN,"$uiDir/getfiles.txt")) { 
	while(<IN>) {
		chop();
		if ((!(/^#/))&&(/,/)) { 
			my ($file,$name)=/([^,]+),([^,]+)/;
			$file=~s/^\s*//;
			$name=~s/^\s*//;
			$fileVal{$name}=$file;
 		}
	}
	close(IN);
}

if ($action && open (IN,$fileVal{$action})) {
	while(<IN>) {
		print;
	}
	close(IN);
}
else {
	print "no file found or no action paramater provided\n";
}
