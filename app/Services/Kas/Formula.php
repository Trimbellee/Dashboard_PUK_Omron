<?php
namespace App\Services\Kas;

class Formula
{
    // nama => [min argumen, maks argumen]
    private const FUNCS = [
        'FLOOR' => [1, 1], 'CEIL' => [1, 1], 'ABS' => [1, 1],
        'ROUND' => [1, 2], 'MIN' => [1, 99], 'MAX' => [1, 99],
    ];

    private array $tokens = [];
    private int $pos = 0;
    private array $ast;
    private array $vars = [];

    public static function parse(string $src): self
    {
        $f = new self();
        $f->tokens = $f->tokenize($src);
        if (!$f->tokens) {
            throw new FormulaException('Rumus kosong');
        }
        $f->ast = $f->parseExpr();
        if ($f->pos < count($f->tokens)) {
            throw new FormulaException("Karakter tak terduga: '{$f->tokens[$f->pos][1]}'");
        }
        return $f;
    }

    /** Daftar variabel yang dipakai rumus (untuk validasi & deteksi ketergantungan). */
    public function variables(): array
    {
        return array_keys($this->vars);
    }

    public function evaluate(callable $resolve): float
    {
        return $this->ev($this->ast, $resolve);
    }

    /** floor yang kebal derau floating point (mis. 99999.9999999 -> 100000). */
    public static function lantai(float $x): int
    {
        return (int) floor(round($x, 6));
    }

    /* ---------------- tokenizer ---------------- */

    private function tokenize(string $s): array
    {
        $out = [];
        $i = 0;
        $n = strlen($s);

        while ($i < $n) {
            if (ctype_space($s[$i])) { $i++; continue; }

            if (preg_match('/\G\d+(?:\.\d+)?/', $s, $m, 0, $i)) {
                $out[] = ['num', $m[0]];
            } elseif (preg_match('/\G[A-Za-z_][A-Za-z0-9_]*(?:\.[A-Za-z_0-9]+)?/', $s, $m, 0, $i)) {
                $out[] = ['id', $m[0]];
            } elseif (strpbrk($s[$i], '+-*/(),') !== false) {
                $out[] = ['op', $s[$i]];
                $m = [$s[$i]];
            } else {
                throw new FormulaException("Karakter tidak diizinkan: '{$s[$i]}'");
            }
            $i += strlen($m[0]);
        }
        return $out;
    }

    /* ---------------- parser ---------------- */

    private function isOp(string $o): bool
    {
        $t = $this->tokens[$this->pos] ?? null;
        return $t && $t[0] === 'op' && $t[1] === $o;
    }

    private function expect(string $o): void
    {
        if (!$this->isOp($o)) {
            throw new FormulaException("Diharapkan '{$o}'");
        }
        $this->pos++;
    }

    private function parseExpr(): array
    {
        $left = $this->parseTerm();
        while ($this->isOp('+') || $this->isOp('-')) {
            $op = $this->tokens[$this->pos++][1];
            $left = ['bin', $op, $left, $this->parseTerm()];
        }
        return $left;
    }

    private function parseTerm(): array
    {
        $left = $this->parseUnary();
        while ($this->isOp('*') || $this->isOp('/')) {
            $op = $this->tokens[$this->pos++][1];
            $left = ['bin', $op, $left, $this->parseUnary()];
        }
        return $left;
    }

    private function parseUnary(): array
    {
        if ($this->isOp('-')) {
            $this->pos++;
            return ['neg', $this->parseUnary()];
        }
        return $this->parsePrimary();
    }

    private function parsePrimary(): array
    {
        $t = $this->tokens[$this->pos] ?? null;
        if (!$t) {
            throw new FormulaException('Rumus berakhir tidak sempurna');
        }
        $this->pos++;

        if ($t[0] === 'num') {
            return ['num', (float) $t[1]];
        }

        if ($t[0] === 'id') {
            $name = strtoupper($t[1]);

            if ($this->isOp('(')) {
                if (!isset(self::FUNCS[$name])) {
                    throw new FormulaException("Fungsi '{$t[1]}' tidak dikenal");
                }
                $this->pos++;
                $args = [];
                if (!$this->isOp(')')) {
                    do {
                        $args[] = $this->parseExpr();
                        $lagi = $this->isOp(',');
                        if ($lagi) $this->pos++;
                    } while ($lagi);
                }
                $this->expect(')');

                [$min, $max] = self::FUNCS[$name];
                if (count($args) < $min || count($args) > $max) {
                    throw new FormulaException("Jumlah argumen {$name}() tidak sesuai");
                }
                return ['fn', $name, $args];
            }

            $this->vars[$name] = true;
            return ['var', $name];
        }

        if ($t[1] === '(') {
            $e = $this->parseExpr();
            $this->expect(')');
            return $e;
        }

        throw new FormulaException("Karakter tak terduga: '{$t[1]}'");
    }

    /* ---------------- evaluator ---------------- */

    private function ev(array $n, callable $r): float
    {
        switch ($n[0]) {
            case 'num': return $n[1];
            case 'var': return (float) $r($n[1]);
            case 'neg': return -$this->ev($n[1], $r);

            case 'bin':
            $a = $this->ev($n[2], $r);
            $b = $this->ev($n[3], $r);

            if ($n[1] === '/' && abs($b) < 1e-12) {
                throw new FormulaException('Pembagian dengan nol');
            }

            return match ($n[1]) {
                '+' => $a + $b,
                '-' => $a - $b,
                '*' => $a * $b,
                '/' => $a / $b,
            };

            case 'fn':
                $v = array_map(fn ($x) => $this->ev($x, $r), $n[2]);
                return match ($n[1]) {
                    'FLOOR' => (float) self::lantai($v[0]),
                    'CEIL'  => (float) ceil(round($v[0], 6)),
                    'ABS'   => abs($v[0]),
                    'ROUND' => round($v[0], (int) ($v[1] ?? 0)),
                    'MIN'   => min($v),
                    'MAX'   => max($v),
                };
        }
        throw new FormulaException('Node rumus tidak valid');
    }
}