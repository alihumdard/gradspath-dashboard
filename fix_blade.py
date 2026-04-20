import re

file_path = r'c:\Users\Rauf\gradspath-dashboard\resources\views\landing_page\index.blade.php'

with open(file_path, 'r', encoding='utf-8') as f:
    content = f.read()

# Fix the backslash issue after }}
content = content.replace("'') }}\"", "'') }}")

with open(file_path, 'w', encoding='utf-8') as f:
    f.write(content)

print('Fixed all backslashes!')
