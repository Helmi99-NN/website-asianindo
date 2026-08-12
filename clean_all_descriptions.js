const fs = require('fs');

function formatToCleanBullets(text) {
    if (!text) return '';
    let cleaned = text;
    // If it has <li> tags
    if (cleaned.includes('<li>')) {
        let temp = cleaned.replace(/<ul[^>]*>/gi, '').replace(/<\/ul>/gi, '');
        let items = temp.split(/<\/li>/gi)
            .map(item => item.replace(/<li[^>]*>/gi, '').replace(/<[^>]*>/g, '').trim())
            .filter(item => item.length > 0);
        return items.map(item => item.startsWith('•') ? item : '• ' + item).join('\n');
    }
    // If it has inline bullet '·'
    if (cleaned.includes('·')) {
        let items = cleaned.split('·')
            .map(item => item.trim())
            .filter(item => item.length > 0);
        return items.map(item => item.startsWith('•') ? item : '• ' + item).join('\n');
    }
    // If it has <br> tags
    if (cleaned.includes('<br')) {
        cleaned = cleaned.replace(/<br\s*\/?>/gi, '\n');
    }
    // Strip any remaining HTML tags
    cleaned = cleaned.replace(/<[^>]*>/g, '').trim();
    return cleaned;
}

// 1. Clean default_products.js
let content = fs.readFileSync('default_products.js', 'utf8');
let startIndex = content.indexOf('[');
let endIndex = content.lastIndexOf(']');
let jsonStr = content.substring(startIndex, endIndex + 1);
let products = JSON.parse(jsonStr);

let updatedCount = 0;
products.forEach(p => {
    if (p.desc) {
        let original = p.desc;
        p.desc = formatToCleanBullets(p.desc);
        if (original !== p.desc) updatedCount++;
    }
});

fs.writeFileSync('default_products.js', 'window.CATALOG_PRODUCTS = ' + JSON.stringify(products, null, 2) + ';');
console.log(`Cleaned ${updatedCount} product descriptions in default_products.js`);

// 2. Clean data/products.json if exists locally
if (fs.existsSync('data/products.json')) {
    let dataContent = fs.readFileSync('data/products.json', 'utf8');
    try {
        let dataProducts = JSON.parse(dataContent);
        dataProducts.forEach(p => {
            if (p.desc) {
                p.desc = formatToCleanBullets(p.desc);
            }
        });
        fs.writeFileSync('data/products.json', JSON.stringify(dataProducts, null, 2));
        fs.writeFileSync('data/products.js', 'window.CATALOG_PRODUCTS = ' + JSON.stringify(dataProducts, null, 2) + ';');
        console.log('Cleaned local data/products.json and data/products.js');
    } catch (e) {
        console.error('Error cleaning data/products.json:', e);
    }
}
