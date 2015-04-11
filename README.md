# PHP-Reaver

A command line PHP script which uses the reaver WPS pin cracker to test multiple AP's with multiple WiFi adapters.

## Prerequisites

* Linux ( Has been tested on ubuntu )
* PHP5 command line interface
* Aircrack-ng suite - http://www.aircrack-ng.org/
* Reaver - https://code.google.com/p/reaver-wps/
* Linux 'timeout' command
* Linux 'tail' command (optional)

To install on Ubuntu fire up a terminal and enter:

```
sudo apt-get install aircrack-ng reaver timeout tail php5-cli
```

##Configuration

First specify the WiFi adapters as the keys for [$bssids] and set the value as an array of BSSID's you want to test via that adapter

```
class PHPReaver {
	...
	private $bssids = array(
		'wlan0'=>array(
			'00:11:22:33:44:55',
			'00:11:22:33:44:66',
			'00:11:22:33:44:77',
		),
		'wlan1'=>array(
			'00:11:22:33:44:11',
			'00:11:22:33:44:22',
		),
		'wlan2'=>array(
			'00:11:22:33:44:88',
			'00:11:22:33:44:99',
			'00:11:22:33:44:AA',
		),
	);
	...
}
```

##Usage

Before running PHP-Reaver stop any existing monitor interfaces you have started, PHP-Reaver will use airmon-ng to start / stop monitor interfaces.

Using a terminal emulator like Terminator is recommended but not essential.

To start PHP-Reaver use:

```
sudo php5 phpreaver.php
```

To view the progress of PHP-Reaver, go to another terminal and enter:

```
tail -f output-phpreaver.txt
```