# hcl_solr_bib_export.pl
# Utility to be run continuously to export new, updated and deleted bibs from Horizon
# to be used to update the Solr index
# Uses the Horizon utility marcout.exe
# Phil Feilmeyer, Dec 2013
# Updates
# Mark Noble Mar 2015 - Update to allow use for other Horizon Libraries with Pika

use strict;
use DBI;
use Config::Properties;
use LWP;
use JSON;
use MARC::Batch;
use File::Copy;

# Initialize properties
my $properties_file = shift(@ARGV);
die "Usage: hcl_solr_bib_export.pl <properties_file>\n" unless $properties_file;
my $properties = Config::Properties->new( file => $properties_file )
  or die "Can't read properties file $properties_file: $!\n";

my $hz_server = $properties->requireProperty('hz_server');
my $hz_password = $properties->requireProperty('hz_password');
my $hz_user = $properties->requireProperty('hz_user');
my $hz_database = $properties->requireProperty('hz_database');

my $dir = $properties->requireProperty('bibexport.dir');
my $utils_dir = $properties->requireProperty('bibexport.utils_dir');
my $export_dir = $properties->requireProperty('bibexport.export_dir');
my $index_me_dir = $properties->requireProperty('bibexport.index_me_dir');
my $min_cycle_time = $properties->requireProperty('bibexport.min_cycle_time');
my $max_records_per_cycle = $properties->requireProperty('bibexport.max_records_per_cycle');

my $marcout_exe = $utils_dir . "marcout.exe";
my $marcout_command_line_no_file = "$marcout_exe /s$hz_server/p$hz_password/u$hz_user/d$hz_database/lts/thcl_bibs_to_solr/xnone/y/q999/b1/e0/8";

my $start_cycle_time;
my $end_cycle_time;

MAINLOOP: while (1 == 1) {
  
  # make a connection to the Horizon db
  my $dbh = DBI->connect("DBI:Sybase:server=$hz_server;maxConnect=100",$hz_user,$hz_password);
  $dbh->do("use $hz_database") or die("Could not use database: $DBI::errstr");
  
  $start_cycle_time = time();

  # get today's date
  my($sec, $min, $hr, $mday, $mon, $year, $wday) = (localtime(time()))[0..6];
  my $yyyy = $year + 1900;
  my $dd = pad($mday, "r", 2, "0");
  my $mm = $mon + 1;
  $mm = pad($mm, "r", 2, "0");
  my $today = $yyyy . $mm . $dd;
  $sec = pad($sec, "r", 2, "0");
  $min = pad($min, "r", 2, "0");
  $hr = pad($hr, "r", 2, "0");
  
  my $n_exported = 0;
  my $n_deleted = 0;
  
  # check for blockage
  my $check_bib_export_prevented = $dbh->selectall_arrayref("
    select value from hcl_solr_index_control where control = 'prevent_bib_export'
  ");
  
  if ($$check_bib_export_prevented[0][0]) {
    # red light
    # bib export is currently prevented
    # set exception values for logging
    print "Bib export is currently blocked\n";
    $n_exported = 0;
    $n_deleted = 0;
  } else {
    # green light
    print "\n\n*************************************************\nStarting new cycle ($mm/$dd/$yyyy $hr:$min:$sec)\n";
    
    my $get_pending;
    eval {
      $get_pending = $dbh->selectall_arrayref("
        select top $max_records_per_cycle SerialNum,RecNum,RecType,Action from hcl_solr_pending
        order by SerialNum
      ");
    };
    if ($@) {
      # drop the connection
      undef($dbh);
      # reconnect
      $dbh = DBI->connect("DBI:Sybase:server=$hz_server;maxConnect=100",$hz_user,$hz_password);
      $dbh->do("use $hz_database");
      next MAINLOOP;
    }
    
    if (@$get_pending) {
      
      my $sth_delete;
      eval {
        $sth_delete = $dbh->prepare("
          delete hcl_solr_pending
          where SerialNum = ?
        ");
      };
      if ($@) {
        # drop the connection
        undef($dbh);
        # reconnect
        $dbh = DBI->connect("DBI:Sybase:server=$hz_server;maxConnect=100",$hz_user,$hz_password);
        $dbh->do("use $hz_database");
        next MAINLOOP;
      }
      
      my $sth_insert;
      eval {
        $sth_insert = $dbh->prepare("
          insert hcl_bibs_to_solr
          (bib#)
          values(?)
        ");
      };
      if ($@) {
        # drop the connection
        undef($dbh);
        # reconnect
        $dbh = DBI->connect("DBI:Sybase:server=$hz_server;maxConnect=100",$hz_user,$hz_password);
        $dbh->do("use $hz_database");
        next MAINLOOP;
      }
      
      # there are entries to process
      my %new_and_updates = ();
      my %deletes = ();
      
      # first find all the deletes (Action = 1) - they override the updates and news
      foreach my $entry_r (@$get_pending) {
        my($serialNum,$recNum,$recType,$action) = @$entry_r;
        if ($action == 1) {
          push(@{$deletes{$recNum}}, $serialNum);
        }
      }
      
      # now get the new and updates
      foreach my $entry_r (@$get_pending) {
        my($serialNum,$recNum,$recType,$action) = @$entry_r;
        if ($action != 1) {
          if (exists($deletes{$recNum})) {
            # zap it out of the pending table
            $sth_delete->execute($serialNum);
          } else {
            push(@{$new_and_updates{$recNum}}, $serialNum);
          }
        }
      }
      
      # new and update
      if (%new_and_updates) {
        print "New and updated records\n";
        
        # clear the table hcl_bibs_to_solr
        eval {
          $dbh->do("truncate table hcl_bibs_to_solr");
        };
        if ($@) {
          # drop the connection
          undef($dbh);
          # reconnect
          $dbh = DBI->connect("DBI:Sybase:server=$hz_server;maxConnect=100",$hz_user,$hz_password);
          $dbh->do("use $hz_database");
          next MAINLOOP;
        }
        
        foreach my $bid (keys(%new_and_updates)) {
          $n_exported++;
          # put the bib# in the hcl_bibs_to_solr table
          $sth_insert->execute($bid);
          # delete all the entries from hcl_solr_pending
          foreach my $sn (@{$new_and_updates{$bid}}) {
            $sth_delete->execute($sn);
          }
        }
        
        # export the bibs
        my $marc_file_name = 'hcl_' . time() . '.mrc';
        my $export_file = $export_dir . $marc_file_name;
        my $marcout_command_line = $marcout_command_line_no_file . "/m$export_file";
        system("$marcout_command_line");
        
        # move the file into the import_me directory
        if (-e $export_file) {
          my $index_me_file = $index_me_dir . $marc_file_name;
          move($export_file, $index_me_file) or warn("Could not move file $export_file to $index_me_file\n");
        } else {
          log_and_warn(-2,"No export file?!?\n",\$dbh);
        }
      } else {
        print "No new or updated bibs to process.\n";
      }
      
      if (%deletes) {
        print "Deleted records\n";
        # create a batch of deletes to send to Solr
        my @batch;
        foreach my $bid (keys(%deletes)) {
          my %delete = ();
          $delete{id} = $bid;
          push(@batch, \%delete);
          # delete the entry from hcl_solr_pending
          foreach my $sn (@{$deletes{$bid}}) {
            $sth_delete->execute($sn);
          }
          $n_deleted++;
        }
        print "Posting update to Solr\n";
        my $ua = LWP::UserAgent->new;
        my $req_solr = HTTP::Request->new('POST', "$solr_app/${solr_core}/update" );
        $req_solr->content_type('application/json');
        $req_solr->content(encode_json(\@batch));
        my $response_solr = $ua->request($req_solr);
        unless ($response_solr->is_success) {
          log_and_warn(-3,"Error posting update for batch: " . $response_solr->status_line . "\n",\$dbh);
        }
        print "Committing changes\n";
        my $commit_request = HTTP::Request->new( GET => "$solr_app/${solr_core}/update?commit=true" );
        my $commit_response = $ua->request($commit_request);
        unless ($commit_response->is_success) {
          log_and_warn(-4,"Error committing update: " . $commit_response->status_line . "\n",\$dbh);
        }
      } else {
        print "No deletes to process\n";
      }
      
    } else {
      # no entries in the table
      print "Nothing to index!\n";
    }
  }
    
  # update the index log
  my $right_now = time();
  $dbh->do("
    insert hcl_solr_bib_export_log
    (timestamp, n_exported, n_deleted)
    values($right_now, $n_exported, $n_deleted)
  ");
   
  print "\nEnd of cycle\n";
  $end_cycle_time = time();
  
  my $cycle_duration = $end_cycle_time - $start_cycle_time;
  print "Cycle time $cycle_duration seconds\n";
  if ($cycle_duration < $min_cycle_time) {
    my $wait_time = $min_cycle_time - $cycle_duration;
    print "Waiting $wait_time seconds before next cycle\n";
    sleep($wait_time);
  }
  
  $dbh->disconnect();
}

sub pad {
  my($string, $just, $size, $delim) = @_;
  $just = lc($just);
  if (length($string) >= $size) {
    $string = substr($string, 0, $size);
  } else {
    my $diff = $size - length($string);
    my $filler = $delim x $diff;
    if ($just eq "r") {
      $string = "$filler$string";
    } else {
      $string = "$string$filler";
    }
  }
  return("$string");
}

sub log_and_warn {
  my($code,$msg,$dbh_ref) = @_;
  my $right_now = time();
  $$dbh_ref->do("
    insert hcl_solr_bib_export_log
    (timestamp, n_exported, n_deleted)
    values($right_now, $code, $code)
  ");
  warn $msg;
}

