const xlsx = require('xlsx');

const hargaWb = xlsx.readFile('data harga.xlsx');
const hargaData = xlsx.utils.sheet_to_json(hargaWb.Sheets[hargaWb.SheetNames[0]], { header: 1 });

console.log("Headers:");
console.log(hargaData[2]);

console.log("First data row:");
console.log(hargaData[4]);
