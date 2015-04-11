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

For more configuration options look at the source code.

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
##Misc
If you are experiencing problems with reaver talking to AP's or wash not showing any AP's when you do a scan this may fix the issue for you:

https://code.google.com/p/reaver-wps/issues/detail?id=217#c20

```
The reason why reaver & wash not work in Ubuntu 14.04 is the new version of libpcap & libpcap-dev, which is probably have some bug or incompatibility.

You can downgrade to version from Ubuntu 13.10:

wget http://mirrors.kernel.org/ubuntu/pool/main/libp/libpcap/libpcap0.8_1.4.0-2_amd64.deb http://mirrors.kernel.org/ubuntu/pool/main/libp/libpcap/libpcap0.8-dev_1.4.0-2_amd64.deb

sudo dpkg -i libpcap0.8_1.4.0-2_amd64.deb libpcap0.8-dev_1.4.0-2_amd64.deb
```

Although wash is not required for PHP-Reaver i had more luck with reaver and the AP's i was testing after performing the above fix.