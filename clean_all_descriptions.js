const fs = require('fs');

function formatToCleanBullets(text) {
    if (!text) return '';
    let str = text;
    if (str.includes('<li>')) {
        str = str.replace(/<ul[^>]*>/gi, '').replace(/<\/ul>/gi, '');
        let parts = str.split(/<\/li>/gi);
        let items = [];
        parts.forEach(p => {
            let cleaned = p.replace(/<li[^>]*>/gi, '').replace(/<[^>]*>/g, '').trim();
            if (cleaned) items.push(cleaned);
        });
        str = items.join('\n');
    } else if (str.includes('<br')) {
        str = str.replace(/<br\s*\/?>/gi, '\n');
    }

    let lines = str.split('\n');
    let finalItems = [];
    lines.forEach(line => {
        let trimmed = line.replace(/<[^>]*>/g, '').trim();
        if (!trimmed) return;
        if (trimmed.includes('·')) {
            let subItems = trimmed.split('·').map(s => s.trim()).filter(Boolean);
            subItems.forEach(item => {
                finalItems.push(item.startsWith('•') || item.startsWith('·') ? item : '• ' + item);
            });
        } else {
            finalItems.push(trimmed.startsWith('•') || trimmed.startsWith('·') ? trimmed : '• ' + trimmed);
        }
    });
    return finalItems.join('\n');
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
