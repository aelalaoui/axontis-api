# ╔══════════════════════════════════════════════════════════════════════════════╗
# ║                         TERRAFORM VARIABLES                                   ║
# ╚══════════════════════════════════════════════════════════════════════════════╝

# ─────────────────────────────────────────────────────────────────────────────────
# VULTR API
# ─────────────────────────────────────────────────────────────────────────────────

variable "vultr_api_key" {
  description = "Vultr API Key - récupérable sur https://my.vultr.com/settings/#settingsapi"
  type        = string
  sensitive   = true
}

# ─────────────────────────────────────────────────────────────────────────────────
# VPS CONFIGURATION
# ─────────────────────────────────────────────────────────────────────────────────

variable "region" {
  description = "Région Vultr (cdg = Paris, fra = Frankfurt, ams = Amsterdam)"
  type        = string
  default     = "cdg"

  validation {
    condition     = contains(["cdg", "fra", "ams", "lhr"], var.region)
    error_message = "La région doit être une région européenne valide: cdg, fra, ams, lhr"
  }
}

variable "plan" {
  description = "Plan VPS Vultr"
  type        = string
  default     = "vc2-1c-1gb"

  validation {
    condition     = can(regex("^vc2-", var.plan))
    error_message = "Le plan doit être un plan Cloud Compute (vc2-*)"
  }
}

variable "hostname" {
  description = "Hostname du serveur"
  type        = string
  default     = "axontis-prod"

  validation {
    condition     = can(regex("^[a-z0-9-]+$", var.hostname))
    error_message = "Le hostname doit contenir uniquement des lettres minuscules, chiffres et tirets"
  }
}

variable "label" {
  description = "Label affiché dans le dashboard Vultr"
  type        = string
  default     = "Axontis Production"
}

# ─────────────────────────────────────────────────────────────────────────────────
# DOMAINE
# ─────────────────────────────────────────────────────────────────────────────────

variable "domain" {
  description = "Nom de domaine principal"
  type        = string
  default     = "axontis.net"
}

# ─────────────────────────────────────────────────────────────────────────────────
# SSH
# ─────────────────────────────────────────────────────────────────────────────────

variable "ssh_public_key" {
  description = "Clé publique SSH pour l'accès au serveur"
  type        = string
  sensitive   = true
}

# ─────────────────────────────────────────────────────────────────────────────────
# ENVIRONNEMENT
# ─────────────────────────────────────────────────────────────────────────────────

variable "environment" {
  description = "Environnement (production, staging)"
  type        = string
  default     = "production"

  validation {
    condition     = contains(["production", "staging"], var.environment)
    error_message = "L'environnement doit être 'production' ou 'staging'"
  }
}

# ─────────────────────────────────────────────────────────────────────────────────
# TAGS
# ─────────────────────────────────────────────────────────────────────────────────

variable "tags" {
  description = "Tags à appliquer aux ressources"
  type        = list(string)
  default     = ["axontis", "laravel", "production"]
}
