<?php
// Vorlage fuer die Angaben im Impressum (impressum.php).
// Kopieren nach src/config/impressum.php und ausfuellen.
// impressum.php steht in .gitignore - Name und Adresse landen so NICHT auf GitHub.
//
// Leere Felder ('') werden im Impressum einfach nicht angezeigt.
return [
    // Pflicht (§ 5 E-Commerce-Gesetz, § 25 Mediengesetz)
    'name'    => 'Vorname Nachname',
    'street'  => 'Straße Hausnummer',
    'city'    => 'PLZ Ort',
    'country' => 'Österreich',
    'email'   => 'kontakt@example.com',

    // Optional
    'phone'   => '',

    // Erst ausfuellen, wenn ein Gewerbe angemeldet ist:
    'trade'         => '',   // z.B. 'Dienstleistungen in der automatischen Datenverarbeitung und Informationstechnik'
    'authority'     => '',   // Gewerbebehoerde, z.B. 'Magistrat der Stadt Wien, MBA 1/8'
    'chamber'       => '',   // z.B. 'Wirtschaftskammer Wien, Fachgruppe UBIT'
    'vat_id'        => '',   // UID-Nummer, z.B. 'ATU12345678'
    'company_reg'   => '',   // Firmenbuchnummer, nur bei eingetragenem Unternehmen
];
