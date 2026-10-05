# Sentinel pilot server with a graphical interface

## Recommended operating system

Use Debian 13 stable (64-bit) with the Xfce desktop. Xfce keeps the machine usable with 4 GB RAM while still providing a normal desktop, file manager, settings application and terminal. Install Cockpit for browser-based server administration.

Official sources:

- Debian stable download: https://www.debian.org/download
- Debian Xfce guidance: https://wiki.debian.org/Xfce
- Cockpit on Debian: https://cockpit-project.org/running

This configuration is suitable for development, demonstrations and a controlled pilot. Move public production traffic to infrastructure with redundant power, monitored backups and a dependable public connection before making uptime commitments.

## Hardware priorities

1. Upgrade the laptop to 8 GB RAM if the memory is replaceable.
2. Use an SSD with at least 128 GB free space.
3. Connect the server through wired Ethernet.
4. Keep the charger connected and use a small UPS when possible.
5. Clean the vents and confirm that the battery is not swollen.

## Installer choices

Create a Debian 13 amd64 installer USB. During software selection, enable:

- Xfce
- SSH server
- standard system utilities

Do not select GNOME, KDE Plasma or multiple desktop environments on a 4 GB machine.

Use a strong administrator password and create a separate everyday administrator account. Give the server a clear host name such as `sentinel-pilot`.

## GUI setup after installation

Open **Settings → Power Manager** in Xfce and configure the laptop to remain awake while connected to AC power. Disable automatic suspend for the server session. Configure the lid action deliberately if the machine will operate with its lid closed, and confirm cooling remains adequate.

Install Cockpit from Debian's package manager, enable its socket, and then open:

`https://SERVER-LAN-IP:9090`

Use Cockpit to view services, logs, CPU and memory use, storage, networking and updates. Restrict Cockpit to the local network or a trusted VPN; do not expose port 9090 directly to the public internet.

## Sentinel service layout

Use these components:

- Nginx as the HTTPS web server
- PHP-FPM 8.2 or newer with PDO MySQL, JSON, OpenSSL and mbstring
- MariaDB with a dedicated least-privilege Sentinel runtime account
- systemd services and timers for managed background work
- a valid public TLS certificate before connecting the Android app

Serve only Sentinel's `public/` directory. Keep `.env`, source configuration, logs, database files and backups outside the web root.

Run the complete PHPUnit suite on the server before enabling pilot users. The Android app requires a valid HTTPS origin and will reject plain HTTP server addresses.

## Network and remote access

Create a DHCP reservation for the server in the router so its LAN address does not change. Use a domain name and HTTPS for remote access. Prefer a VPN or a carefully configured reverse proxy. Never expose MariaDB directly to the internet.

Allow only the required inbound services. Rate-limit login requests and keep SSH key-based access available for recovery even when normal administration happens through Cockpit.

## Backups and monitoring

Keep encrypted database backups on separate storage and maintain an additional off-machine copy. Test restoration into an isolated database before the pilot. Monitor:

- HTTPS availability and response time
- disk space and database size
- failed logins and repeated 401/403 responses
- database and audit failures
- backup completion and backup age
- memory pressure and overheating

The detailed application release gates remain in `docs/OPERATIONS_RELEASE_RUNBOOK.md`.
