#!/usr/bin/perl
use strict;
use warnings;
use File::Copy;
use File::Basename;
my $isDebug = 0; # debug modus enable for cmd-line test and to get query, token info
my $isDumpEnv = 0; # debug modus dumping environment variables
my $debug = ""; # debug text
my $isAdmin = 0; # part of administrators group
my $uiUser =''; # user running ui session
my $cgiUser = $ENV{LOGNAME} || getpwuid($<) || $ENV{USER};
my $dsmToken =''; #  synology dsm token against xss from dsm login.cgi
my $uiDir = $0; # get current working dir runing ui
$uiDir =~s#/[^/]+$##g; # cleaning to get dirname
require "$uiDir/syno_cgi.pl"; # base cgi functions get params, usr_priv
my $dsmBuild = `/bin/get_key_value /etc.defaults/VERSION buildnumber`;
chomp($dsmBuild) if $dsmBuild; 
my $pkgName = basename($uiDir);

# *** common head cgi section: set html context, verify login
print "Content-type: text/html\n\n";
if ($isDebug && $isDumpEnv) {
	$debug = "Environment variables:\n";
	foreach my $key (sort keys(%ENV)) {
		$debug .= "$key = $ENV{$key}\n";
	}
}

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
my %tmplhtml; # array to capture all dynamic html entries
my %tmpljs; # key-value array to capture all dynamic js entries
my $jscript = ''; # java script injected in page using ext-3 js
# add cfg files from file to js-select-combo
sub addCfgFile {
	my ($fname,$name)=@_;
	$fname=~s/^\s*//g;
	$name=~s/^\s*//g;
	if ($tmpljs{'names'}) {
		$tmpljs{'names'}.=',';
	}
	$tmpljs{'names'}.="'" .$name ."'" ;
}
if (open(IN,"$uiDir/getfiles.txt")) {
	while(<IN>) {
		chop();
		if ((!(/^#/))&&(/,/)) {
			my ($fname,$name)=/([^,]+),(.*)/;
			addCfgFile($fname,$name);
		}
	}
}
# get javascript
if (open(IN,"$uiDir/script.js")) {
	while (<IN>) {
		s/==:([^:]+):==/$tmpljs{$1}/g;
		$jscript.=$_;
	}
	close(IN);
}
$tmplhtml{'javascript'}=$jscript;
$tmplhtml{'debug'}=$debug;

# print main html page embedding tags from tmplhtml incl. jscript
# js is using ext-3.4 library in /usr/syno/synoman/scripts/ext-3
if (open(IN,"$uiDir/page.html")) {
	while (<IN>) {
		s/==:([^:]+):==/$tmplhtml{$1}/g;
		print $_;
	}
	close(IN);
}
