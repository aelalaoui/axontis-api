# ╔══════════════════════════════════════════════════════════════════════════════╗
# ║                    AXONTIS - INFRASTRUCTURE TERRAFORM                        ║
# ╚══════════════════════════════════════════════════════════════════════════════╝
#
# Ce dossier contient l'Infrastructure as Code (IaC) pour déployer
# l'application Axontis CRM sur Vultr.
#
# 📋 Prérequis :
#   - Terraform >= 1.0
#   - Compte Vultr avec API Key
#   - SSH Key générée
#
# 🚀 Utilisation rapide :
#   1. Copier terraform.tfvars.example vers terraform.tfvars
#   2. Remplir les valeurs dans terraform.tfvars
#   3. terraform init
#   4. terraform plan
#   5. terraform apply
#
# 📁 Structure des fichiers :
#   ├── main.tf              # Configuration principale et provider
#   ├── variables.tf         # Déclaration des variables
#   ├── outputs.tf           # Sorties (IP, etc.)
#   ├── vps.tf               # Ressource VPS
#   ├── firewall.tf          # Règles firewall
#   ├── terraform.tfvars.example  # Exemple de configuration
#   └── scripts/
#       └── cloud-init.yaml  # Script de provisioning initial
#
# 🔐 Sécurité :
#   - Ne JAMAIS commiter terraform.tfvars (contient l'API key)
#   - Ne JAMAIS commiter les fichiers .tfstate (contiennent des secrets)
#   - Utiliser des variables d'environnement en CI/CD
#
# 💰 Coût estimé : ~6$/mois (VPS vc2-1c-1gb)
#
# 📖 Documentation :
#   - Vultr Terraform Provider: https://registry.terraform.io/providers/vultr/vultr/latest/docs
#   - Vultr API: https://www.vultr.com/api/
