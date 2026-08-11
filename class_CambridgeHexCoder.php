<?php

class CambridgeHexCoder
{
    /**
     * Encode un texte : mélange Cambridge (2 premiers, 2 derniers) puis Hexadécimal
     */
    public function encode(string $text): string
    {
        // On isole chaque mot (en gardant la ponctuation et les espaces intacts)
        // \p{L} cible toutes les lettres (y compris accentuées) en UTF-8
        $scrambledText = preg_replace_callback('/[\p{L}]+/u', function ($matches) {
            return $this->scrambleWord($matches[0]);
        }, $text);

        // On convertit le résultat en hexadécimal
        return bin2hex($scrambledText);
    }

    /**
     * Décode un hexadécimal vers le texte mélangé lisible
     */
    public function decode(string $hex): string
    {
        // On vérifie que la chaîne est bien de l'hexadécimal
        if (!ctype_xdigit($hex) || strlen($hex) % 2 !== 0) {
            throw new InvalidArgumentException("La chaîne fournie n'est pas un hexadécimal valide.");
        }

        return hex2bin($hex);
    }

    /**
     * Applique la logique de Cambridge sur un seul mot
     */
    private function scrambleWord(string $word): string
    {
        // Si le mot fait 4 caractères ou moins, rien à mélanger au milieu
        if (mb_strlen($word, 'UTF-8') <= 4) {
            return $word;
        }

        // 1. Les 2 premières lettres (index 0 et 1)
        $firstTwo = mb_substr($word, 0, 2, 'UTF-8');

        // 2. Les 2 dernières lettres
        $lastTwo  = mb_substr($word, -2, null, 'UTF-8');

        // 3. Le milieu : démarre à l'index 2 et s'arrête 2 caractères avant la fin
        $middle   = mb_substr($word, 2, -2, 'UTF-8');

        // On mélange le milieu
        $scrambledMiddle = $this->mb_str_shuffle($middle);

        // On recompose le mot complet
        return $firstTwo . $scrambledMiddle . $lastTwo;
    }

    /**
     * Mélange une chaîne de caractères compatible avec l'UTF-8 (pour les accents)
     */
    private function mb_str_shuffle(string $string): string
    {
        // On découpe la chaîne en un tableau de caractères
        $chars = preg_split('//u', $string, -1, PREG_SPLIT_NO_EMPTY);
        
        // On mélange le tableau
        shuffle($chars);
        
        // On le reforme en chaîne
        return implode('', $chars);
    }
}

// === EXEMPLE D'UTILISATION ===

$coder = new CambridgeHexCoder();

$texteOriginal = "Salut ! C'est vraiment fantastique de programmer des algorithmes avec des caractères aléatoires.";
echo "Texte original : " . $texteOriginal . "\n\n";

// 1. Encodage
$hexaEncode = $coder->encode($texteOriginal);
echo "Encodé (Hexa) : " . $hexaEncode . "\n\n";

// 2. Décodage
$texteDecode = $coder->decode($hexaEncode);
echo "Décodé (Cambridge) : " . $texteDecode . "\n";

?>
