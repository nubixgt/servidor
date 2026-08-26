import re

with open('Databases/dump-EjecucionPresupuestaria-202605251318.sql', 'r', encoding='utf-8') as f:
    for line in f:
        if line.startswith('INSERT INTO'):
            match = re.match(r'INSERT INTO `([^`]+)`', line)
            if match:
                print("Found INSERT for table:", match.group(1))
