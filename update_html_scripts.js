const fs = require('fs');
const path = require('path');

const files = ['katalog.html', 'product.html', 'article.html', 'blog.html'];

files.forEach(file => {
    const filePath = path.join(__dirname, file);
    if (fs.existsSync(filePath)) {
        let content = fs.readFileSync(filePath, 'utf8');
        content = content.replace(
            '<script src="products.js"></script>', 
            '<script src="default_products.js"></script>\n    <script src="data/products.js"></script>'
        );
        fs.writeFileSync(filePath, content, 'utf8');
        console.log(`Updated ${file}`);
    }
});
