import re, pathlib
P = pathlib.Path('/app/casino/php-project')
def rd(f): return (P/f).read_text(encoding='utf-8', errors='surrogateescape')
def wr(f, s): (P/f).write_text(s, encoding='utf-8', errors='surrogateescape')

s = rd('action.php')
# include RTP helpers
s = s.replace('require("inc/bd.php");\n', 'require("inc/bd.php");\nrequire_once("inc/rtp.php");\n', 1)
# remove unused force-win helper
s = re.sub(r'\nfunction bwForceWin\(.*?\n}\n', '\n', s, count=1, flags=re.S)

# Dice: honest RNG + RTP payout + max-win limit (both minbet and maxbet)
old_win = "$winsum = round(((100 / $per * $sum) - $sum), 2);"
assert s.count(old_win) == 2
s = s.replace(old_win, "$winsum = round(((100 / $per * $sum * rb_rtp('dice')) - $sum), 2);")
n_before = s.count('kot_chance')
s, n = re.subn(r'\$sql_select27 = "SELECT COUNT\(\*\) FROM kot_chance.*?(\$hash = hash\(\'sha512\', \$rand\);)',
               r"$rand = random_int(0, 999999);\n  \1", s, flags=re.S)
assert n == 2, n
lim = '''       if(rb_over_limit($winsum)) {
         $newbalance = $balance;
         $error = 70;
         $mess = "Макс. выигрыш за ставку " . rb_max_win();
         $fa = "error";
       }
       if(!is_numeric($sum)) {'''
s, n = re.subn(r'       if\(!is_numeric\(\$sum\)\) \{(?=\s*\n\s*\$newbalance = \$balance;\s*\n\s*\$error = 77;)', lim, s)
assert n == 2, n

# Mines: coefficients scaled by RTP (insert bottom-up)
lines = s.split('\n')
idx_flat = next(i for i, l in enumerate(lines) if l.strip().startswith('$bombs24 = [25];'))
nested_ends = []
for i, l in enumerate(lines):
    if l.strip().startswith('$bombs = ['):
        j = i
        while lines[j].strip() != '];': j += 1
        nested_ends.append(j)
assert len(nested_ends) == 3, nested_ends
for j in sorted(nested_ends + [idx_flat], reverse=True):
    if j == idx_flat:
        lines.insert(j + 1, '    foreach (range(2, 24) as $__b) { ${"bombs".$__b} = rb_scale_mines(${"bombs".$__b}); }')
    else:
        lines.insert(j + 1, '\t$bombs = rb_scale_mines($bombs);')
s = '\n'.join(lines)
wr('action.php', s)

# Bubbles: honest crash-style result, RTP, max-win; no hidden forced loss / fake numbers
b = rd('bubblesfunc.php')
b = b.replace('require("inc/bd.php");\n', 'require("inc/bd.php");\nrequire_once("inc/rtp.php");\n', 1)
start = b.index('		    $antiminus = rand(1,500);')
end = b.index('			$hash = hash(\'sha512\', $coef);')
honest = '''            if (rb_over_limit($sum * $per - $sum)) {
                $coef = "Макс. выигрыш за ставку " . rb_max_win();
                $newbalance = $balance;
                $error = 1;
                $fa = "error";
            } else {
            $u = random_int(1, 1000000) / 1000000;
            $point = max(1, floor(rb_rtp('bubbles') / $u * 100) / 100);
            if ($point >= $per) {
                $newbalance = round($balance + $sum * $per - $sum, 2);
                $resultwin = round($sum * $per - $sum, 2);
                $coef = "Выпало x" . $point;
                mysql_query("Update kot_user set balance='$newbalance' WHERE hash='$sid'");
                $fa = "success";
            } else {
                $newbalance = $balance - $sum;
                $coef = "Выпало x" . $point;
                mysql_query("Update kot_user set balance='$newbalance' WHERE hash='$sid'");
                $error = 1;
                $fa = "error";
            }
            }
'''
b = b[:start] + honest + b[end:]
wr('bubblesfunc.php', b)

# Wheel x50: remove bank-based forced loss, add max-win limit
w = rd('wheel.php')
w = w.replace('require("inc/bd.php");\n', 'require("inc/bd.php");\nrequire_once("inc/rtp.php");\n', 1)
w = w.replace("  if($bank >= $vozm_win){", "  if(rb_over_limit($vozm_win)){\n    $obj = array(\"bad\"=>\"bad\",\"mess\"=>\"Макс. выигрыш за ставку \".rb_max_win(),\"money\"=>\"$money\");\n  }else{", 1)
i = w.index(" }else{\n  $query = mysql_query(\"UPDATE `kot_user` SET `balance` = '$money'-'$bet' WHERE `id` = '$id'\");")
j = w.index('  }else{\n    $obj = array("danger"=>"danger","mess"=>"Минимальная ставка 1')
w = w[:i] + " }\n" + w[j:]
wr('wheel.php', w)
print('ok')
