# kryptokronaWHMCS
A WHMCS Payment Gateway for accepting kryptokrona

## Dependencies
This plugin is rather simple but there are a few things that need to be set up beforehand.

* A web server! Ideally with the most recent versions of PHP and mysql

* The kryptokrona wallet-cli and kryptokrona wallet-rpc tools found [here](https://getkryptokrona.org/downloads/)

* [WHMCS](https://www.whmcs.com/)
This kryptokrona plugin is a payment gateway for WHMCS

## Step 1: Activating the plugin
* Downloading: First of all, you will need to download the plugin.  If you wish, you can also download the latest source code from GitHub. This can be done with the command `git clone https://github.com/kryptokrona-integrations/kryptokronawhmcs.git` or can be downloaded as a zip file from the GitHub web page.


* Put the plugin in the correct directory: You will need to copy `kryptokrona.php` and the folder named `kryptokrona` from this repo/unzipped release into the WHMCS Payment Gateways directory. This can be found at `whmcspath/modules/gateways/`

* Activate the plugin from the WHMCS admin panel: Once you login to the admin panel in WHMCS, click on "Setup -> Payments -> Payment Gateways". Click on "All Payment Gateways". Then click on the "kryptokrona" gateway to activate it.

* Enter a Module Secret Key.  This can be any random text and is used to verify payments.  

* Enter the values for Wallet RPC Host, Wallet RPC Port, Username, and Password (these are from kryptokrona-wallet-rpc below).  Optionally enter a percentage discount for all invoices paid via kryptokrona.

* Optionally install the addon module to disable WHMCS fraud checking when using kryptokrona. You will need to copy the folder `addons/kryptokronaenable/` from this repo/unzipped release into the WHMCS Addons directory. This can be found at `whmcspath/addons/`.  

* Activate the kryptokrona Enabler addon from the WHMCS admin panel: Click on "Setup -> Addon Modules". Find "kryptokrona Enabler" and click on "Activate". Click "Configure" and choose the kryptokrona Payment Gateway in the drop down list. Check the box for "Enable checking for payment method by module" and click "Save Changes".

## Step 2: Get a kryptokrona daemon to connect to

### Option 1: Running a full node yourself

To do this: start the kryptokrona daemon on your server and leave it running in the background. This can be accomplished by running `./kryptokronad` inside your kryptokrona downloads folder. The first time that you start your node, the kryptokrona daemon will download and sync the entire kryptokrona blockchain. This can take several hours and is best done on a machine with at least 4GB of ram, an SSD hard drive (with at least 15GB of free space), and a high speed internet connection.

### Option 2: Connecting to a remote node
The easiest way to find a remote node to connect to is to visit [kryptokronaworld.com](https://kryptokronaworld.com/#nodes) and use one of the nodes offered. It is probably easiest to use node.kryptokronaworld.com:18089 which will automatically connect you to a random node.

## Step 3: Setup your kryptokrona wallet-rpc

* Setup a kryptokrona wallet using the kryptokrona-wallet-cli tool. If you do not know how to do this you can learn about it at [getkryptokrona.org](https://getkryptokrona.org/resources/user-guides/kryptokrona-wallet-cli.html)

* Start the Wallet RPC and leave it running in the background. This can be accomplished by running `./kryptokrona-wallet-rpc --rpc-bind-port 18082 --rpc-login username:password --log-level 2 --wallet-file /path/walletfile` where "username:password" is the username and password that you want to use, separated by a colon and  "/path/walletfile" is your actual wallet file. If you wish to use a remote node you can add the `--daemon-address` flag followed by the address of the node. `--daemon-address node.kryptokronaworld.com:18089` for example.



## Info on server authentication
It is recommended that you specify a username/password with your wallet rpc. This can be done by starting your wallet rpc with `kryptokrona-wallet-rpc --rpc-bind-port 18082 --rpc-login username:password --wallet-file /path/walletfile` where "username:password" is the username and password that you want to use, separated by a colon. Alternatively, you can use the `--restricted-rpc` flag with the wallet rpc like so `./kryptokrona-wallet-rpc --testnet --rpc-bind-port 18082 --restricted-rpc --wallet-file wallet/path`.

## Donating Me
XMR Address : `44krVcL6TPkANjpFwS2GWvg1kJhTrN7y9heVeQiDJ3rP8iGbCd5GeA4f3c2NKYHC1R4mCgnW7dsUUUae2m9GiNBGT4T8s2X`
