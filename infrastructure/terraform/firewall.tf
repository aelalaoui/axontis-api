# ╔══════════════════════════════════════════════════════════════════════════════╗
# ║                         FIREWALL CONFIGURATION                                ║
# ╚══════════════════════════════════════════════════════════════════════════════╝

# ─────────────────────────────────────────────────────────────────────────────────
# FIREWALL GROUP
# ─────────────────────────────────────────────────────────────────────────────────

resource "vultr_firewall_group" "axontis" {
  description = "Firewall for ${var.label}"
}

# ─────────────────────────────────────────────────────────────────────────────────
# RÈGLES SSH (Port 22)
# ─────────────────────────────────────────────────────────────────────────────────

resource "vultr_firewall_rule" "ssh_ipv4" {
  firewall_group_id = vultr_firewall_group.axontis.id
  protocol          = "tcp"
  ip_type           = "v4"
  subnet            = "0.0.0.0"
  subnet_size       = 0
  port              = "22"
  notes             = "Allow SSH IPv4"
}

resource "vultr_firewall_rule" "ssh_ipv6" {
  firewall_group_id = vultr_firewall_group.axontis.id
  protocol          = "tcp"
  ip_type           = "v6"
  subnet            = "::"
  subnet_size       = 0
  port              = "22"
  notes             = "Allow SSH IPv6"
}

# ─────────────────────────────────────────────────────────────────────────────────
# RÈGLES HTTP (Port 80)
# ─────────────────────────────────────────────────────────────────────────────────

resource "vultr_firewall_rule" "http_ipv4" {
  firewall_group_id = vultr_firewall_group.axontis.id
  protocol          = "tcp"
  ip_type           = "v4"
  subnet            = "0.0.0.0"
  subnet_size       = 0
  port              = "80"
  notes             = "Allow HTTP IPv4"
}

resource "vultr_firewall_rule" "http_ipv6" {
  firewall_group_id = vultr_firewall_group.axontis.id
  protocol          = "tcp"
  ip_type           = "v6"
  subnet            = "::"
  subnet_size       = 0
  port              = "80"
  notes             = "Allow HTTP IPv6"
}

# ─────────────────────────────────────────────────────────────────────────────────
# RÈGLES HTTPS (Port 443)
# ─────────────────────────────────────────────────────────────────────────────────

resource "vultr_firewall_rule" "https_ipv4" {
  firewall_group_id = vultr_firewall_group.axontis.id
  protocol          = "tcp"
  ip_type           = "v4"
  subnet            = "0.0.0.0"
  subnet_size       = 0
  port              = "443"
  notes             = "Allow HTTPS IPv4"
}

resource "vultr_firewall_rule" "https_ipv6" {
  firewall_group_id = vultr_firewall_group.axontis.id
  protocol          = "tcp"
  ip_type           = "v6"
  subnet            = "::"
  subnet_size       = 0
  port              = "443"
  notes             = "Allow HTTPS IPv6"
}

# ─────────────────────────────────────────────────────────────────────────────────
# RÈGLE ICMP (Ping) - Optionnel mais utile pour le diagnostic
# ─────────────────────────────────────────────────────────────────────────────────

resource "vultr_firewall_rule" "icmp_ipv4" {
  firewall_group_id = vultr_firewall_group.axontis.id
  protocol          = "icmp"
  ip_type           = "v4"
  subnet            = "0.0.0.0"
  subnet_size       = 0
  notes             = "Allow ICMP (ping) IPv4"
}

resource "vultr_firewall_rule" "icmp_ipv6" {
  firewall_group_id = vultr_firewall_group.axontis.id
  protocol          = "icmp"
  ip_type           = "v6"
  subnet            = "::"
  subnet_size       = 0
  notes             = "Allow ICMP (ping) IPv6"
}

# ─────────────────────────────────────────────────────────────────────────────────
# NOTE: Ports MySQL (3306) et Redis (6379) ne sont PAS ouverts
# La base de données est locale au VPS, pas d'accès externe nécessaire
# ─────────────────────────────────────────────────────────────────────────────────
