<?php
return [
  // Admin en clair, comme demandé
  'admin_user' => 'margouille',
  'admin_pass_plain' => 'Fripouille123!',

  // Dossiers (chemins physiques, relatifs au projet)
  'dir_data'    => dirname(__DIR__) . '/data',
  'dir_private' => dirname(__DIR__) . '/data/galeries',     // originaux + json
  'dir_public'  => dirname(__DIR__) . '/public/galeries',   // variantes

  // Tailles des variantes WebP
  'sizes' => [ 'thumb' => 400, 'grid' => 1200, 'hd' => 2000 ],

  // Génération par lot (évite de charger le serveur)
  'batch' => 10,

  // Limites d'upload (sécurité de base)
  'max_files_per_upload' => 80,              // limite le nombre de fichiers par POST
  'max_total_mb_per_upload' => 512,          // budget total par upload
  'allowed_mime' => ['image/jpeg','image/png','image/webp'],
];