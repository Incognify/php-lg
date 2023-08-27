# php-lg
PHP based Looking Glass script for service providers.

Demo: https://lg.otr.cx

Honestly, it's mostly ChatGPT. I wanted a looking glass script that was a bit more modern and a bit less dated. I also wanted something I could easily modify and intergrate into various website designs, for both [IncogNET](https://incognet.io)'s main site and for AS210630.net which will get a facelift and a proper website, sometime.

Features:
- PHP based (Only tested on PHP 8.0 and PHP 8.2, probably works on older stuff)
- Built in rate limiting (Though nginx will do this better than PHP)
- Doesn't allow you to enter bogons by default, but you can adjust this to blacklist other subnets (IPv4 and IPv6)
- You can also block certain hostnames, if you so desire it. (Currently 'localhost' is the only blocked hostname)
- It actually doesn't look too bad on mobile. That was an accident, I don't give a shit about mobile functionality.
- It's only three files. (index.php, result.php, style.css)

System Requirements:
- PHP

(Tested on Debian 12 w/ PHP 8.X)

To Do:
- Make the design... better.
- Probably going to add some test files.
- More user defined variables to make it easier for you to use this how you want it.
- Add speedtest features, probably.

I don't want to over complicate this. The goal is to just have a basic and easy to use looking glass script that is both easy to modify and intergrate into existing website designs.

---

# Nginx Ratelimit

Top of Nginx config:

    limit_req_zone $binary_remote_addr zone=one:10m rate=1r/s;

In server block:

    # Rate limiting
    location ~ ^/(result.php) {
        limit_req zone=one burst=5;
        try_files $uri =404;
        fastcgi_split_path_info ^(.+\.php)(/.+)$;
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

