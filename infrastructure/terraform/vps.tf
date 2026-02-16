# ╔══════════════════════════════════════════════════════════════════════════════╗
# ║                         VPS INSTANCE                                          ║
# ╚══════════════════════════════════════════════════════════════════════════════╝

# ─────────────────────────────────────────────────────────────────────────────────
# SSH KEY
# ─────────────────────────────────────────────────────────────────────────────────

resource "vultr_ssh_key" "axontis" {
  name    = "${var.hostname}-ssh-key"
  ssh_key = var.ssh_public_key
}

# ─────────────────────────────────────────────────────────────────────────────────
# VPS INSTANCE
# ─────────────────────────────────────────────────────────────────────────────────

resource "vultr_instance" "axontis" {
  label     = var.label
  hostname  = var.hostname
  region    = var.region
  plan      = var.plan
  os_id     = data.vultr_os.ubuntu.id

  # Clé SSH pour l'accès root
  ssh_key_ids = [vultr_ssh_key.axontis.id]

  # Firewall
  firewall_group_id = vultr_firewall_group.axontis.id

  # Activer IPv6
  enable_ipv6 = true

  # Désactiver les backups automatiques (économie ~1$/mois)
  # Activer en production si le budget le permet
  backups = "disabled"

  # Script cloud-init pour le provisioning initial
  user_data = file("${path.module}/scripts/cloud-init.yaml")

  # Tags
  tags = local.common_tags

  # Attendre que le VPS soit vraiment prêt
  lifecycle {
    create_before_destroy = true
  }
}

# ─────────────────────────────────────────────────────────────────────────────────
# RESERVED IP (optionnel - pour IP statique persistante)
# ─────────────────────────────────────────────────────────────────────────────────
# Décommenter si vous voulez une IP réservée qui persiste même si le VPS est recréé
# Coût additionnel: ~3$/mois si non attachée à un VPS

# resource "vultr_reserved_ip" "axontis" {
#   region       = var.region
#   ip_type      = "v4"
#   label        = "${var.hostname}-reserved-ip"
# }

# resource "vultr_instance_ipv4" "axontis" {
#   instance_id = vultr_instance.axontis.id
#   reboot      = false
# }
