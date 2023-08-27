#**************************************************************************#
#  syno_cgi.pl                                                             #
#  Description: Script to get the query parameters and permissions of      #
#               active user for the calling perl 3rd party application.    #
#               Replaces the need for CGI.pm no longer shipped in DSM-6.   #
#               my $p = param ('foo') provides qs-parameter same as CGI.pm #
#               my ($a,$u,$t) = usr_priv() provides user sys privileges.   #
#               $a=in admin grp, $u=name of active user, $t=syno token.    #
#               Put syno's login, authenticate.cgi, synowebapi to modules. #
#  Author:      TosoBoso & QTip's input; German Synology Support Forum.    #
#  Copyright:   2017-2020 by TosoBoso                                      #
#  License:     GNU GPLv3 (see LICENSE)                                    #
#  ------------------------------------------------------------------- --  #
#  Version:     0.4 - 09/22/2020                                           #
#**************************************************************************#
use File::Basename;
my $rpInitialised = 0; # to have requestParams only loaded once
my $reqParams; # html query string captured from cgi

sub parse_qs { # parsing the quesry string provided via get or post
    my $queryString = shift; # query string to parse from get or post
    my @pairs = split(/&/, $queryString);
    foreach $pair (@pairs) {
        my ($key, $value) = split(/=/, $pair);
        $value =~ tr/+/ /; # trim away +
        $value =~ s/%([a-fA-F0-9][a-fA-F0-9])/pack("C", hex($1))/eg;
        $value =~ s/\r\n/\n/g; # windows fix
        $value =~ s/\r/\n/g; # mac fix
        $reqParams->{lc($key)}=$value; # normalize to lower case
    }
    $rpInitialised = 1;
}
sub param { # html-get / post parameters as alternative to cgi.pm not always shiped on syno
    my $rp = shift; # the get request parameter
    return '' if ( !$rp || !$ENV{'REQUEST_METHOD'} ); # exit if no request set
    # parse once url params from query string this will catch anything after the "?"
    if( ($ENV{'REQUEST_METHOD'} eq 'GET') && $ENV{'QUERY_STRING'} && !$rpInitialised) {
        parse_qs($ENV{'QUERY_STRING'});
    }    
    if( ($ENV{'REQUEST_METHOD'} eq 'POST') && !$rpInitialised) {
        my $formData; # data from form via post
        read(STDIN, $formData, $ENV{'CONTENT_LENGTH'});
        parse_qs($formData);
    }
    return $reqParams->{$rp};
}
sub get_usr_token { # get loged on user and synotoken, use own syno-modules to address low privilege
    my $user = '';
    my $token = '';
    # save http_get environment and restore later to get syno-cgi working for token and user
    my $tmpenv = $ENV{'QUERY_STRING'};
    my $tmpreq = $ENV{'REQUEST_METHOD'};
    $ENV{'QUERY_STRING'}="";
    $ENV{'REQUEST_METHOD'} = 'GET';
    my $synoModules = "/usr/syno/synoman/webman/modules"; # default dir will only work as root
	# if exist switch to synomodules shipped with package with permission for non privileged user
	$synoModules = dirname( $ENV{'SCRIPT_FILENAME'} ) . '/modules' if ($ENV{'SCRIPT_FILENAME'} && -f dirname( $ENV{'SCRIPT_FILENAME'} ) . '/modules/login.cgi');
	# get the synotoken to verify login if non root using login.cgi in our root with our acls
    if (open (IN,"$synoModules/login.cgi|")) {
        while(<IN>) {
            if (/SynoToken/) { ($token)=/SynoToken" *: *"([^"]+)"/; }
        }
        close(IN);
    }
    else {
        $token = 'no-permission login.cgi';
    }
    if ( $token ne '' && $token ne 'no-permission login.cgi' ) { # no token no query respectively in cmd-line mode
        $ENV{'QUERY_STRING'}="SynoToken=$token";
        $ENV{'X-SYNO-TOKEN'} = $token;
		# get the user loged into gui if non root using login.cgi in our root with our acls
		if (open (IN,"$synoModules/authenticate.cgi|")) {
            $user=<IN>;
            chop($user);
            close(IN);
        }
    }
    $ENV{QUERY_STRING} = $tmpenv;
    $ENV{'REQUEST_METHOD'} = $tmpreq;
    return ($user,$token);
}
sub usr_priv { # legacy simple way: admin privilege on system level via id -G = 101
    my $isAdmin = 0;
    my ($user,$token) = get_usr_token();
    # verify if active user is part of administrators group on syno: 101
    if ($user && open (IN,"id -G ".$user." |")) {
        my $groupIds=<IN>;
        chop($groupIds) if $groupIds;
        $isAdmin = 1 if index($groupIds, "101") != -1;
        close(IN);
    }    
    return ($isAdmin,$user,$token);
}
sub app_priv { # new way: admin privilege on syno app level
    my $appName = shift;
    my $appPrivilege = 0;
    my $isAdmin = 0;
    my $rawData = '';
    use JSON::XS;
    use Data::Dumper;
    my $synoModules = "/usr/syno/bin"; # default dir will only work as root
	# if exist switch to synomodules shipped with package with permission for non privileged user
	$synoModules = dirname( $ENV{'SCRIPT_FILENAME'} ) . '/modules' if ($ENV{'SCRIPT_FILENAME'} && -f dirname( $ENV{'SCRIPT_FILENAME'} ) . '/modules/synowebapi');

    my ($user,$token) = get_usr_token();
    # verify user allowed admin on application level, with low privilege use our own modules path
    $rawData = `$synoModules/synowebapi --exec api=SYNO.Core.Desktop.Initdata method=get version=1 runner=$user`;
    return (0,'','') unless ($rawData); # exit if empty to avoid JSON exception
    $initdata = JSON::XS->new->decode($rawData);
    $appPrivilege = (defined $initdata->{'data'}->{'AppPrivilege'}->{$appName}) ? 1 : 0;
    $isAdmin = (defined $initdata->{'data'}->{'Session'}->{'is_admin'} && $initdata->{'data'}->{'Session'}->{'is_admin'} == 1) ? 1 : 0;
    # if application not found or user not admin, return empty string
    return (0,'','') unless ($appPrivilege || $isAdmin);
    return ($isAdmin,$user,$token);
}
# return true for included libraries
1;
