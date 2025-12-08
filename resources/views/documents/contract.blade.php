<!DOCTYPE html>
<html>

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Contrat de Service - Axontis</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            line-height: 1.5;
            color: #333;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #ddd;
            padding-bottom: 20px;
        }

        .logo {
            max-width: 200px;
            margin-bottom: 10px;
        }

        .title {
            font-size: 24px;
            font-weight: bold;
            color: #2c3e50;
            margin: 0;
        }

        .section {
            margin-bottom: 25px;
        }

        .section-title {
            font-size: 16px;
            font-weight: bold;
            background-color: #f8f9fa;
            padding: 8px;
            border-left: 4px solid #3498db;
            margin-bottom: 15px;
        }

        .client-info {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
        }

        .client-info p {
            margin: 5px 0;
        }

        .legal-text {
            text-align: justify;
            margin-bottom: 10px;
        }

        .signatures {
            margin-top: 50px;
            width: 100%;
        }

        .signature-box {
            width: 45%;
            float: left;
            border-top: 1px solid #999;
            padding-top: 10px;
        }

        .signature-box.right {
            float: right;
        }

        .footer {
            position: fixed;
            bottom: 0;
            width: 100%;
            text-align: center;
            font-size: 10px;
            color: #777;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }

        .page-break {
            page-break-after: always;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1 class="title">CONTRAT DE PRESTATION DE SERVICES</h1>
        <p>Référence: {{ $contract->uuid }}</p>
        <p>Date: {{ now()->format('d/m/Y') }}</p>
    </div>

    <div class="section">
        <div class="section-title">ENTRE LES SOUSSIGNÉS</div>

        <p><strong>La société AXONTIS</strong>, société [Forme Juridique], au capital de [Montant] Dirhams, immatriculée
            au Registre du Commerce et des Sociétés de [Ville] sous le numéro [Numéro SIRET], dont le siège social est
            situé à [Adresse], représentée par [Nom du Représentant], en sa qualité de [Fonction],</p>

        <p>Ci-après dénommée <strong>"le Prestataire"</strong>, d'une part,</p>

        <p style="text-align: center; font-weight: bold; margin: 15px 0;">ET</p>

        <div class="client-info">
            <p><strong>Client :</strong> {{ $client->company ?? $client->first_name . ' ' . $client->last_name }}</p>
            <p><strong>Représenté par :</strong> {{ $client->first_name }} {{ $client->last_name }}</p>
            <p><strong>Adresse :</strong> {{ $client->address ?? 'Non renseignée' }}, {{ $client->city ?? '' }}</p>
            <p><strong>Téléphone :</strong> {{ $client->phone ?? 'Non renseigné' }}</p>
            <p><strong>Email :</strong> {{ $client->email }}</p>
        </div>

        <p>Ci-après dénommée <strong>"le Client"</strong>, d'autre part.</p>
    </div>

    <div class="section">
        <div class="section-title">ARTICLE 1 : OBJET DU CONTRAT</div>
        <p class="legal-text">
            Le présent contrat a pour objet de définir les conditions dans lesquelles le Prestataire s'engage à fournir
            au Client les services de télésurveillance et de sécurité électronique tels que décrits dans l'offre
            commerciale acceptée par le Client.
        </p>
    </div>

    <div class="section">
        <div class="section-title">ARTICLE 2 : DURÉE</div>
        <p class="legal-text">
            Le présent contrat est conclu pour une durée initiale de <strong>12 mois</strong> à compter de la date de
            mise en service effective du système. Il est renouvelable par tacite reconduction pour des périodes
            successives de même durée, sauf dénonciation par l'une ou l'autre des parties par lettre recommandée avec
            accusé de réception, moyennant un préavis de 3 mois avant l'échéance.
        </p>
    </div>

    <div class="section">
        <div class="section-title">ARTICLE 3 : OBLIGATIONS DU PRESTATAIRE</div>
        <p class="legal-text">
            Le Prestataire s'engage à mettre en œuvre tous les moyens nécessaires pour assurer la continuité et la
            qualité du service de télésurveillance. Il assure la maintenance préventive et curative des équipements
            installés selon les conditions définies en annexe.
        </p>
    </div>

    <div class="section">
        <div class="section-title">ARTICLE 4 : OBLIGATIONS DU CLIENT</div>
        <p class="legal-text">
            Le Client s'engage à utiliser le matériel conformément aux instructions fournies par le Prestataire, à ne
            pas intervenir lui-même sur les équipements et à signaler sans délai tout dysfonctionnement constaté. Il
            s'engage également à régler les factures émises par le Prestataire dans les délais convenus.
        </p>
    </div>

    <div class="section">
        <div class="section-title">ARTICLE 5 : CONDITIONS FINANCIÈRES</div>
        <p class="legal-text">
            En contrepartie des services fournis, le Client s'engage à verser au Prestataire un abonnement mensuel de :
        </p>
        <p style="text-align: center; font-size: 14px; font-weight: bold; margin: 15px 0;">
            {{ number_format($contract->monthly_ttc, 2, ',', ' ') }} MAD TTC / mois
        </p>
        <p class="legal-text">
            Ce montant est payable d'avance, par prélèvement automatique ou virement bancaire, avant le 5 de chaque
            mois.
        </p>
    </div>

    <div class="signatures">
        <div class="signature-box">
            <p>Pour AXONTIS</p>
            <p>Fait à [Ville], le {{ now()->format('d/m/Y') }}</p>
            <br><br><br>
            <p>(Signature et Cachet)</p>
        </div>

        <div class="signature-box right">
            <p>Pour le CLIENT</p>
            <p>Fait à {{ $client->city ?? '[Ville]' }}, le {{ now()->format('d/m/Y') }}</p>
            <p style="font-size: 10px;">(Lu et approuvé, bon pour accord)</p>
            <br><br>
            <p>(Signature et Cachet)</p>
        </div>
    </div>

    <div class="footer">
        AXONTIS - Société à Responsabilité Limitée - Capital de [Montant] DHS - RC [Numéro] - ICE [Numéro]<br>
        Page 1/1
    </div>
</body>

</html>