<?php

// Each bad word becomes ****.
function mask_bad_words(string $text): string
{
    $words = ['fuck', 'shit', 'bitch', 'asshole', 'bastard', 'damn', 'crap','puta', 'putang', 'gago', 'tangina', 'tang ina', 'bobo', 'tanga', 'ulol', 'leche', 'pakyu', 'pucha', 'siraulo', 'hayop ka', 'panget', 'pangit' ];
    // letters may be swapped with look-alike symbols (a=@, i=1, o=0, e=3, s=$)
    $map = ['a' => '[a@4]', 'i' => '[i1!]', 'o' => '[o0]', 'e' => '[e3]', 's' => '[s$5]', ' ' => '\s+'];
    $parts = [];
    foreach ($words as $w) {
        $re = '';
        foreach (str_split($w) as $ch) {
            $re .= $map[$ch] ?? preg_quote($ch, '/');
        }
        $parts[] = $re;
    }
    $pattern = '/\b(?:' . implode('|', $parts) . ')\w*/iu';
    return preg_replace_callback($pattern, fn($m) => str_repeat('*', strlen($m[0])), $text);
}
