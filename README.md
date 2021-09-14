# myddns
Simple Dynamic DNS Web management self-hosting. Run over dnsmasq.

![image](https://user-images.githubusercontent.com/1895563/133020249-1c2d59c0-a32f-43fe-a1a5-666131d0d188.png)


## Preparation

At least, if your subdmain is myddns.example.com and your server IP is 1.2.3.4, you have to create an A in your dns zone for myddns.example.com to 1.2.3.4. Also, you have to create an NS record for myddns.example.com to myddns.example.com.

## Installation

### Local installation in Ubuntu 20.04 server

1. Clone this repository
2. Copy config/config.dist to config/config.php and customize it
3. Run INSTALL.bash as root
