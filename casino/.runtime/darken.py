import re, pathlib
P = pathlib.Path('/app/casino/php-project')
files = [f for f in list(P.glob('*.php')) + list(P.glob('components/*.php')) + list(P.glob('css/*.css')) + list(P.glob('files/*.css'))
         if f.name != 'rubin-theme.css']

def lum(h):
    h = h.lower()
    if len(h) == 3: h = ''.join(c*2 for c in h)
    r, g, b = int(h[0:2], 16), int(h[2:4], 16), int(h[4:6], 16)
    return r, g, b

def bg_repl(m):
    r, g, b = lum(m.group(2))
    if (r + g + b) / 3 > 200:
        return m.group(1) + '#17151c'
    return m.group(0)

def purple_repl(m):
    r, g, b = lum(m.group(1))
    if r > 60 and b > 180 and g < 130 and b > r + 40:
        return '#e0245e'
    if r > 40 and b > 120 and g < 80 and b > r + 30:
        return '#a8143f'
    return m.group(0)

n = 0
for f in files:
    s = f.read_text(encoding='utf-8', errors='surrogateescape')
    t = re.sub(r'(background(?:-color)?\s*:\s*)#([0-9a-fA-F]{6}|[0-9a-fA-F]{3})\b', bg_repl, s)
    t = re.sub(r'#([0-9a-fA-F]{6})\b', purple_repl, t)
    if t != s:
        f.write_text(t, encoding='utf-8', errors='surrogateescape'); n += 1
print('files changed', n)
