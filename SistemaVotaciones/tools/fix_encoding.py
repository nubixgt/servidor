import os
import glob

# Mapping of mojibake to correct UTF-8 characters
replacements = {
    'Ã¡': 'á',
    'Ã©': 'é',
    'Ã­': 'í',
    'Ã³': 'ó',
    'Ãº': 'ú',
    'Ã±': 'ñ',
    'Ã ': 'Á',
    'Ã‰': 'É',
    'Ã': 'Í',
    'Ã“': 'Ó',
    'Ãš': 'Ú',
    'Ã‘': 'Ñ',
    'Ã¼': 'ü',
    'Ãœ': 'Ü',
    'Â¿': '¿',
    'Â¡': '¡',
    'Â°': '°',
    'Âº': 'º',
}

directory = r"c:\xampp\htdocs\servidor\SistemaVotaciones"

for root, _, files in os.walk(directory):
    for file in files:
        if file.endswith('.php') or file.endswith('.js') or file.endswith('.html') or file.endswith('.md'):
            filepath = os.path.join(root, file)
            try:
                with open(filepath, 'r', encoding='utf-8') as f:
                    content = f.read()

                new_content = content
                for wrong, right in replacements.items():
                    new_content = new_content.replace(wrong, right)

                if new_content != content:
                    print(f"Fixed encoding in {filepath}")
                    with open(filepath, 'w', encoding='utf-8') as f:
                        f.write(new_content)
            except Exception as e:
                print(f"Error processing {filepath}: {e}")
