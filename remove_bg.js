const { Jimp } = require('C:/Users/HELMI/.gemini/antigravity/brain/e5826abc-2dcd-467c-ad98-a84add9d9549/scratch/image_compress/node_modules/jimp');

async function removeBackground() {
    console.log("Reading image...");
    const image = await Jimp.read('images/complex_line.png');
    
    console.log("Image loaded. Dimensions:", image.bitmap.width, "x", image.bitmap.height);
    
    // We will do a flood fill starting from (0,0) and other edges
    const width = image.bitmap.width;
    const height = image.bitmap.height;
    
    // A 2D array to keep track of visited pixels
    const visited = new Uint8Array(width * height);
    
    // Stack for flood fill
    const stack = [];
    
    // Function to push a pixel if it's white-ish and not visited
    function pushIfValid(x, y) {
        if (x < 0 || x >= width || y < 0 || y >= height) return;
        const idx = y * width + x;
        if (visited[idx]) return;
        
        const pos = idx * 4;
        const r = image.bitmap.data[pos];
        const g = image.bitmap.data[pos+1];
        const b = image.bitmap.data[pos+2];
        
        // Check if it's close to white (background)
        if (r > 230 && g > 230 && b > 230) {
            visited[idx] = 1;
            stack.push({x, y});
        }
    }
    
    // Start from edges
    for (let x = 0; x < width; x++) {
        pushIfValid(x, 0);
        pushIfValid(x, height - 1);
    }
    for (let y = 0; y < height; y++) {
        pushIfValid(0, y);
        pushIfValid(width - 1, y);
    }
    
    console.log("Starting flood fill...");
    let processed = 0;
    while (stack.length > 0) {
        const {x, y} = stack.pop();
        
        // Make transparent
        const pos = (y * width + x) * 4;
        image.bitmap.data[pos + 3] = 0; // Alpha to 0
        
        processed++;
        
        // Add neighbors
        pushIfValid(x - 1, y);
        pushIfValid(x + 1, y);
        pushIfValid(x, y - 1);
        pushIfValid(x, y + 1);
    }
    
    console.log("Flood fill done. Pixels processed:", processed);
    
    // Additional pass: smooth the edges (simple anti-aliasing)
    // Find all transparent pixels that touch opaque pixels and make them semi-transparent
    console.log("Writing output...");
    image.write('images/complex_line_transparent.png');
    console.log("Done!");
}

removeBackground().catch(console.error);
