<?php

namespace App\Service;

class SlugService
{
    public function generer(string $texte): string
    {
        $texte = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $texte) ?: $texte;
        $texte = preg_replace('/[^a-z0-9]+/', '-', strtolower($texte)) ?? '';
        $texte = trim($texte, '-');

        return $texte ?: 'item-' . bin2hex(random_bytes(3));
    }
}
