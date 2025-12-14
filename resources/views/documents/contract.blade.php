<!DOCTYPE html>
<html>

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Contrat de Service - Axontis</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11px;
            line-height: 1.4;
            color: #333;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #ddd;
            padding-bottom: 10px;
        }

        .logo {
            max-width: 200px;
            margin-bottom: 5px;
        }

        .title {
            font-size: 18px;
            font-weight: bold;
            color: #2c3e50;
            margin: 0;
            text-transform: uppercase;
        }

        .section {
            margin-bottom: 15px;
        }

        .section-title {
            font-size: 13px;
            font-weight: bold;
            background-color: #f8f9fa;
            padding: 5px;
            border-left: 4px solid #3498db;
            margin-bottom: 10px;
        }

        .client-info {
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            margin-top: 10px;
        }

        .client-info p {
            margin: 3px 0;
        }

        .legal-text {
            text-align: justify;
            margin-bottom: 5px;
        }

        ul {
            margin-top: 5px;
            margin-bottom: 5px;
        }

        .signatures {
            margin-top: 30px;
            width: 100%;
        }

        .signature-box {
            width: 45%;
            float: left;
            border-top: 1px solid #999;
            padding-top: 5px;
        }

        .signature-box.right {
            float: right;
        }

        .footer {
            position: fixed;
            bottom: 0;
            width: 100%;
            text-align: center;
            font-size: 9px;
            color: #777;
            border-top: 1px solid #ddd;
            padding-top: 5px;
        }

        .page-break {
            page-break-after: always;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1 class="title">CONTRAT D’ABONNEMENT DE TÉLÉSURVEILLANCE ET D’ASSISTANCE</h1>
        <p>Référence: {{ $contract->uuid }}</p>
        <p>Date: {{ now()->format('d/m/Y') }}</p>
    </div>

    <div class="section">
        <div class="section-title">ENTRE LES SOUSSIGNÉS :</div>

        <p>1. <strong>La Société AXONTIS</strong> (Ci-après « Le Prestataire ») SARL au capital de 100 000 DH,
            immatriculée
            au RC de Casablanca sous le n°[NUMÉRO]. Siège social : [ADRESSE], Maroc.</p>

        <p>ET</p>

        <p>2. <strong>Le Client</strong> (Ci-après « L’Abonné ») Identifié selon les informations fournies lors du
            parcours de souscription en ligne (voir Annexe 1 : Fiche Client).</p>

        <div class="client-info">
            <strong>Annexe 1 : Fiche Client</strong>
            <p><strong>Nom/Société :</strong> {{ $client->company ?? $client->first_name . ' ' . $client->last_name }}
            </p>
            <p><strong>Représenté par :</strong> {{ $client->first_name }} {{ $client->last_name }}</p>
            <p><strong>Adresse :</strong> {{ $client->address ?? 'Non renseignée' }}, {{ $client->city ?? '' }}</p>
            <p><strong>Téléphone :</strong> {{ $client->phone ?? 'Non renseigné' }}</p>
            <p><strong>Email :</strong> {{ $client->email }}</p>
        </div>
    </div>

    <div class="section">
        <div class="section-title">ARTICLE 1 : OBJET DU CONTRAT</div>
        <p class="legal-text">
            Le présent contrat a pour objet la fourniture par le Prestataire d'un système de sécurité électronique et
            d'un service de télésurveillance au profit de l'Abonné, sur le site désigné ci-après. Les caractéristiques
            spécifiques du service (nombre de détecteurs, caméras, options) sont celles définies par l'Abonné lors du
            parcours de simulation en ligne validé le {{ now()->format('d/m/Y') }}.
        </p>
        <p class="legal-text"><strong>Adresse du site protégé (Territoire Maroc) :</strong>
            {{ $client->address ?? 'Non renseignée' }}, {{ $client->city ?? '' }}</p>
    </div>

    <div class="section">
        <div class="section-title">ARTICLE 2 : ÉQUIPEMENT ET INSTALLATION</div>
        <p class="legal-text">Le système de sécurité installé comprend les éléments suivants (selon diagnostic en ligne)
            :</p>
        <ul>
            <li>[X] Centrale d'alarme connectée</li>
            <li>[X] Détecteurs de mouvement (Images/Infrarouge)</li>
            <li>[X] Détecteurs d'ouverture</li>
        </ul>
        <p class="legal-text">Détail complet en Annexe "Bon de commande".</p>
        <p class="legal-text">L'équipement reste la propriété insaisissable du Prestataire (dans le cadre d'une
            location).</p>
    </div>

    <div class="section">
        <div class="section-title">ARTICLE 3 : DESCRIPTION DES SERVICES</div>
        <p class="legal-text">Le Prestataire s'engage à assurer, 24h/24 et 7j/7 :</p>
        <ul>
            <li><strong>La Télésurveillance :</strong> Réception et traitement des signaux d'alarme par la Station
                Centrale de Surveillance.</li>
            <li><strong>La Levée de Doute :</strong> Audio/Vidéo à distance. Appel de contrôle à l'Abonné.</li>
            <li><strong>L'Alerte :</strong> En cas d'intrusion confirmée, le Prestataire contacte immédiatement les
                services de Police ou Gendarmerie Royale compétents territorialement, conformément à la réglementation
                en vigueur au Maroc.</li>
        </ul>
    </div>

    <div class="section">
        <div class="section-title">ARTICLE 4 : OBLIGATIONS DE L'ABONNÉ</div>
        <p class="legal-text">L'Abonné s'engage à :</p>
        <ul>
            <li>Fournir une connexion électrique et internet fonctionnelle (sauf si carte SIM GPRS incluse).</li>
            <li>Informer le Prestataire de toute modification des lieux (travaux) pouvant affecter le système.</li>
            <li>Communiquer une liste de contacts d'urgence à jour (au moins 2 numéros au Maroc).</li>
        </ul>
    </div>

    <div class="section">
        <div class="section-title">ARTICLE 5 : PRIX ET MODALITÉS DE PAIEMENT</div>
        <p class="legal-text">
            Le service est facturé au tarif mensuel de <strong>{{ number_format($contract->monthly_ttc, 2, ',', ' ') }}
                DH TTC</strong>. Les paiements s'effectuent par prélèvement bancaire automatique. Tout rejet de
            prélèvement pourra entraîner la suspension du service après notification.
        </p>
    </div>

    <div class="section">
        <div class="section-title">ARTICLE 6 : DURÉE ET RÉSILIATION</div>
        <p class="legal-text">
            Le présent contrat est conclu pour une durée initiale de 12 mois. Il est renouvelable par tacite
            reconduction.
        </p>
        <p class="legal-text">
            <strong>Résiliation :</strong> L'Abonné peut résilier le contrat à l'issue de la période initiale avec un
            préavis de 1 mois par lettre recommandée avec accusé de réception.
        </p>
        <p class="legal-text">
            <strong>Loi 31-08 :</strong> Conformément à la loi sur la protection du consommateur, l'Abonné dispose d'un
            délai de rétractation de 7 jours après la signature du contrat (sauf si l'installation a déjà été effectuée
            à sa demande expresse).
        </p>
    </div>

    <div class="section">
        <div class="section-title">ARTICLE 7 : RESPONSABILITÉ</div>
        <p class="legal-text">
            Le Prestataire est tenu à une obligation de moyens. Sa responsabilité ne saurait être engagée en cas de
            coupure des réseaux de communication (Maroc Telecom, Orange, Inwi) ou d'électricité, ni en cas de négligence
            de l'Abonné (oubli d'activation de l'alarme). La responsabilité du Prestataire est plafonnée au montant
            annuel de l'abonnement.
        </p>
    </div>

    <div class="section">
        <div class="section-title">ARTICLE 8 : DONNÉES PERSONNELLES (LOI 09-08)</div>
        <p class="legal-text">
            Les données collectées sont nécessaires à l'exécution du service. Le Prestataire s'engage à ne pas les
            divulguer à des tiers non autorisés. Conformément à la loi 09-08, l'Abonné dispose d'un droit d'accès et de
            rectification auprès du service client du Prestataire. Autorisation CNDP n° : [À OBTENIR]
        </p>
    </div>

    <div class="section">
        <div class="section-title">ARTICLE 9 : JURIDICTION COMPÉTENTE</div>
        <p class="legal-text">
            En cas de litige, et après tentative de règlement amiable, compétence expresse est attribuée aux tribunaux
            de Casablanca ou du lieu de résidence du consommateur.
        </p>
    </div>

    <div class="signatures">
        <div class="signature-box">
            <p>Le Prestataire</p>
            <p>Fait à Casablanca, le {{ now()->format('d/m/Y') }}</p>
            <br><br><br>
            <p>(Signature)</p>
        </div>

        <div class="signature-box right">
            <p>L'Abonné</p>
            <p>Fait à {{ $client->city ?? 'Casablanca' }}, le {{ now()->format('d/m/Y') }}</p>
            <p style="font-size: 10px;">(Signature précédée de "Lu et approuvé")</p>
            <br><br>
            <p>(Signature)</p>
        </div>
    </div>

    <div class="footer">
        AXONTIS - Société à Responsabilité Limitée - Capital de 100 000 DHS - RC [Numéro] - ICE [Numéro]<br>
        Page 1/1
    </div>
</body>

</html>