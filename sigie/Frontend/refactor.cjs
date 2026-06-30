const fs = require('fs');
const path = require('path');

function walkDir(dir, callback) {
    fs.readdirSync(dir).forEach(f => {
        let dirPath = path.join(dir, f);
        let isDirectory = fs.statSync(dirPath).isDirectory();
        isDirectory ? walkDir(dirPath, callback) : callback(path.join(dir, f));
    });
}

function processFile(filePath) {
    if (!filePath.endsWith('.vue')) return;
    
    let content = fs.readFileSync(filePath, 'utf8');
    let original = content;

    // Replace card backgrounds
    content = content.replace(/bg-white\/95|bg-white\/90|bg-white\/[0-9]+|bg-white\b/g, 'glass-card');
    content = content.replace(/bg-gray-50\b|bg-slate-50\b/g, 'bg-black/20');
    
    // Replace text colors
    content = content.replace(/text-gray-900|text-gray-800|text-slate-900|text-slate-800/g, 'text-white');
    content = content.replace(/text-gray-700|text-gray-600|text-gray-500|text-slate-700|text-slate-600|text-slate-500/g, 'text-gray-300');
    
    // Replace borders
    content = content.replace(/border-gray-200|border-gray-100|border-slate-200|border-slate-100/g, 'border-white/10');
    
    // Replace shadows
    content = content.replace(/shadow-sm\b/g, 'shadow-lg');
    
    // Replace specific header navy things
    content = content.replace(/header-navy/g, 'bg-[#0f172a]/40 backdrop-blur-md border-b border-white/10 shadow-lg');
    content = content.replace(/bg-gradient-to-b from-\[\#0a192f\] to-\[\#0d1f3c\]/g, 'bg-[#0f172a]/40 backdrop-blur-xl border-r border-white/10');
    
    if (content !== original) {
        fs.writeFileSync(filePath, content, 'utf8');
        console.log(`Updated: ${filePath}`);
    }
}

walkDir(path.join(__dirname, 'src'), processFile);
console.log('Refactor complete.');
