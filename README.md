# MyDDNS [BETA]

Simple Dynamic DNS Web management self-hosting. It use [dnsmasq](https://en.wikipedia.org/wiki/Dnsmasq). It was inspired on [duckdns.org](http://www.duckdns.org).

![image](https://user-images.githubusercontent.com/1895563/134248325-49aa67c4-2478-4d7d-9b44-f98629c5e9ff.png)

![image](https://user-images.githubusercontent.com/1895563/134248636-d0e97490-8c75-4bc1-9d82-72f0afc8136e.png)


## Preparation

You need root access to a server, virtual machine or container. The 53 port must be open in your firewall.

Also, you have to create these records in your Zone DNS of your domain provider:

- (required) A record for subdomain (myddns.example.com) to point to IPv4 of your server
- (optional) AAAA record for subdomain (myddns.example.com) to point to IPv6 of your server
- (required) NS record to point to subdomain (myddns.example.com)

## Installation

### Installation with docker-composer

1. Install docker and docker-composer.
2. Disable systemd-resolved or any other service that use 53 port.
3. Clone this repository.
4. Copy .env.example to .env and set the environment variables.
5. Execute:
```bash
docker-compose up -d --build
``` 

### Local installation in Ubuntu 20.04 server

1. Change to root user
2. Clone this repository
3. Run INSTALL.bash as root

This script will ask you for the BASEDOMAIN, BASEIP and PASSWORD values ​​interactively. They can also be read as environment variables.

## Use

### From browser

Open base domain in your browser and click on "Update" button.

### From command line

You can use curl or wget and call it from a cron to automatize:

```bash
curl -s "http://myddns.example.com/?action=1&format=json&subdomain=<SUBDOMAIN>&code=<PASSWORD>"
```

### Arguments

They can be sent both by POST and by GET:

- **action**: To create or update must be 1. By default is 0.
- **format**: 'html', 'json' or 'simple'. By default is 'html'.
- **subdomain**: It can only contain lowercase letters, numbers, or the symbols '_' and '-'. At least 4 characters. By default is ''.
- **ip**: It can only contain a valid IP. By default, it autodetects the IP from which the request is made.
- **code**: The password defined in config.php file. If empty, you can manage myDDNS without password required.

### JSON response

- **domain**
- **ip**
- **error**: false if the ip to subdomain was updated, true if not.
- **errormsg**: object with error messages if any.

### Simple response

- **OK** if the ip to subdomain was updated.
- **KO** if the ip to subdomain was not updated.
