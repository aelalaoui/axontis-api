# ╔══════════════════════════════════════════════════════════════════════════════╗
# ║                         TERRAFORM MAIN CONFIGURATION                          ║
# ╚══════════════════════════════════════════════════════════════════════════════╝

terraform {
  required_version = ">= 1.0"

  required_providers {
    vultr = {
      source  = "vultr/vultr"
      version = "~> 2.19"
    }
  }

  # ─────────────────────────────────────────────────────────────────────────────
  # BACKEND CONFIGURATION
  # ─────────────────────────────────────────────────────────────────────────────
  # Pour l'instant, on utilise le backend local.
  # En production avec équipe, migrer vers un backend distant (S3, etc.)
  #
  # backend "s3" {
  #   bucket = "axontis-terraform-state"
  #   key    = "prod/terraform.tfstate"
  #   region = "eu-west-3"
  # }
}

# ─────────────────────────────────────────────────────────────────────────────────
# VULTR PROVIDER
# ─────────────────────────────────────────────────────────────────────────────────

provider "vultr" {
  api_key     = var.vultr_api_key
  rate_limit  = 100
  retry_limit = 3
}

# ─────────────────────────────────────────────────────────────────────────────────
# DATA SOURCES
# ─────────────────────────────────────────────────────────────────────────────────

# Récupère l'ID de l'OS Ubuntu 24.04 LTS
data "vultr_os" "ubuntu" {
  filter {
    name   = "name"
    values = ["Ubuntu 24.04 LTS x64"]
  }
}

# ─────────────────────────────────────────────────────────────────────────────────
# LOCALS
# ─────────────────────────────────────────────────────────────────────────────────

locals {
  common_tags = concat(var.tags, [var.environment])

  # Nom complet pour les ressources
  resource_prefix = "${var.hostname}-${var.environment}"
}
