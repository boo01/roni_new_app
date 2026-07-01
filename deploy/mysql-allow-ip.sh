#!/usr/bin/env bash
# Grant an IP remote access to the Dockerised MySQL (external port 3311 ->
# container 3306). Docker bypasses UFW, so access is filtered in the
# DOCKER-USER iptables chain, driven by /etc/roni5/mysql-allowed-ips.txt and
# re-applied by the roni5-db-firewall systemd service (survives reboots).
#
# Usage:  sudo bash deploy/mysql-allow-ip.sh <IP-ADDRESS>
# List allowed IPs:   cat /etc/roni5/mysql-allowed-ips.txt
# Remove an IP:       edit that file, then: sudo systemctl restart roni5-db-firewall
set -euo pipefail

IP="${1:-}"
IPFILE=/etc/roni5/mysql-allowed-ips.txt
if [ -z "$IP" ]; then
  echo "Usage: sudo bash $0 <IP-ADDRESS>" >&2
  exit 1
fi

mkdir -p /etc/roni5
touch "$IPFILE"
if grep -qxF "$IP" "$IPFILE"; then
  echo "$IP already allowed."
else
  echo "$IP" >> "$IPFILE"
fi

# Re-apply the firewall rules from the file.
systemctl restart roni5-db-firewall
echo "Allowed IPs now:"
grep -vE '^\s*(#|$)' "$IPFILE"
