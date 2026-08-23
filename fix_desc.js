const fs = require('fs');
let content = fs.readFileSync('default_products.js', 'utf8');
let startIndex = content.indexOf('[');
let endIndex = content.lastIndexOf(']');
let jsonStr = content.substring(startIndex, endIndex + 1);
let products = JSON.parse(jsonStr);

products.forEach(p => {
    if (p.subCategory === 'Spray Dryer') {
        let items = p.desc.split('·').map(s => s.trim()).filter(s => s);
        if (items.length > 1) {
            p.desc = '<ul class="list-disc pl-4 space-y-1">\n' + items.map(i => '  <li>' + i + '</li>').join('\n') + '\n</ul>';
            console.log("Updated:", p.name);
        }
    }
});

fs.writeFileSync('default_products.js', 'window.CATALOG_PRODUCTS = ' + JSON.stringify(products, null, 2) + ';');
console.log('Done modifying default_products.js!');
