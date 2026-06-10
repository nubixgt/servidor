const fs = require('fs');
const path = require('path');

const dirs = [
  './src/views/admin',
  './src/views/tecnico'
];

function processFile(filePath) {
  let content = fs.readFileSync(filePath, 'utf8');

  // Replace rounded-none with rounded-xl for buttons and inputs
  content = content.replace(/rounded-none/g, 'rounded-xl');
  
  // Replace rounded-sm with rounded-lg for icons and inner elements
  content = content.replace(/rounded-sm/g, 'rounded-lg');
  
  // Apply rounded-2xl to panels and cards
  content = content.replace(/class="([^"]+)"/g, (match, p1) => {
    let classes = p1.split(/\s+/);
    // If it's a container with background and shadow or border
    if ((classes.includes('bg-white') && classes.includes('shadow-sm')) || 
        (classes.includes('border-[#cbd5e1]') && !classes.includes('border-b') && !classes.includes('border-t'))) {
      if (!classes.some(c => c.startsWith('rounded-'))) {
        classes.push('rounded-2xl');
      }
    }
    return `class="${classes.join(' ')}"`;
  });

  fs.writeFileSync(filePath, content);
  console.log('Updated ' + filePath);
}

dirs.forEach(dir => {
  const files = fs.readdirSync(dir);
  files.forEach(file => {
    if (file.endsWith('.vue')) {
      processFile(path.join(dir, file));
    }
  });
});
console.log('All styling sweeps complete.');
