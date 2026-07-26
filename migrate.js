const fs = require('fs');
const path = require('path');

const srcFile = path.join(__dirname, 'products.js');
const targetDir = path.join(__dirname, 'data');
const targetFile = path.join(targetDir, 'products.json');

if (!fs.existsSync(targetDir)) {
    fs.mkdirSync(targetDir);
}

let content = fs.readFileSync(srcFile, 'utf8');
content = content.replace('window.CATALOG_PRODUCTS = ', '').trim();
if (content.endsWith(';')) {
    content = content.slice(0, -1);
}

try {
    const data = JSON.parse(content);
    fs.writeFileSync(targetFile, JSON.stringify(data, null, 2), 'utf8');
    console.log('Successfully migrated products.js to data/products.json');
} catch (e) {
    console.error('Error parsing JSON:', e.message);
}
