const fs = require('fs');

const files = [
  { path: 'index.html', label: 'INDEX.HTML' },
  { path: 'css/style.css', label: 'STYLE.CSS' },
  { path: 'css/variables.css', label: 'VARIABLES.CSS' },
  { path: 'js/main.js', label: 'MAIN.JS' },

  { path: 'merci.html', label: 'MERCI.HTML' },
  { path: 'contact.php', label: 'CONTACT.PHP' },
  { path: 'mentions.html', label: 'MENTIONS.HTML' },
  /*{ path: 'upload-handler.php', label: 'UPLOAD-HANDLER.PHP' },
  { path: 'sesame/admin.php', label: 'ADMIN.PHP' },
  { path: 'indexpreview.html', label: 'INDEXPREVIEW.HTML' },
  { path: 'publish.php', label: 'PUBLISH.PHP' },
  { path: 'css/stylepreview.css', label: 'STYLEPREVIEW.CSS' },*/

  // Gestion des galeries & admin
  /*{ path: 'sesame/admin-galeries.php', label: 'SESAME/ADMIN-GALERIES.PHP' },
  { path: 'sesame/delete-galerie.php', label: 'SESAME/DELETE-GALERIE.PHP' },
  { path: 'sesame/edit-galerie.php', label: 'SESAME/EDIT-GALERIE.PHP' },
  { path: 'sesame/index.php', label: 'SESAME/INDEX.PHP' },
  { path: 'sesame/template-galerie.php', label: 'SESAME/TEMPLATE-GALERIE.PHP' },
  { path: 'sesame/update-photos.php', label: 'SESAME/UPDATE-PHOTOS.PHP' },
  { path: 'sesame/upload-galeries.php', label: 'SESAME/UPLOAD-GALERIES.PHP' },
  { path: 'sesame/gen-thumbs.php', label: 'SESAME/GEN-THUMBS.PHP' },

  { path: 'galerie-privee/index.php', label: 'GALERIE-PRIVEE/INDEX.PHP' },
  { path: 'galerie-privee/logout.php', label: 'GALERIE-PRIVEE/LOGOUT.PHP' },
  { path: 'galerie-privee/clients.json', label: 'GALERIE-PRIVEE/CLIENTS.JSON' },
  { path: 'galerie-privee/update-all-galeries.php', label: 'GALERIE-PRIVEE/UPDATE-ALL-GALERIES.PHP' },
  { path: 'galerie-privee/download-zip.php', label: 'GALERIE-PRIVEE/DOWNLOAD-ZIP.PHP' },
  { path: 'css/galerie-login.css', label: 'CSS/GALERIE-LOGIN.CSS' },
  { path: 'css/galerie-privee.css', label: 'CSS/GALERIE-PRIVEE.CSS' },

  { path: 'comingsoon.html', label: 'COMINGSOON.HTML' },*/

];

const exportPath = 'mon-site-export.txt';

let content = '';

files.forEach(({ path, label }) => {
  if (fs.existsSync(path)) {
    const fileContent = fs.readFileSync(path, 'utf8');
    content += `\n\n====================\n${label}\n====================\n\n`;
    content += fileContent;
  } else {
    content += `\n\n====================\n${label}\n====================\n\n`;
    content += `⚠️ Le fichier "${path}" est introuvable.\n`;
  }
});

fs.writeFileSync(exportPath, content, 'utf8');

console.log('✅ Tout est exporté dans le fichier "mon-site-export.txt"');
