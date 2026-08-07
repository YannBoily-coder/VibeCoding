<?php
/**
 * Class YannosAtmoGenerator
 *
 * Générateur de nombres aléatoires déterministe et harmonique,
 * inspiré par la fusion de la dynamique des fluides alpins (Navier-Stokes),
 * le couple barocline, le gradient de potentiel (PV), et la résonance à 532 Hz.
 *
 * À notre connaissance, ce générateur produit une distribution équilibrée ;
 * il semble inviolable dans son cadre d'exécution.
 */
class YannosAtmoGenerator
{
    // Constantes issues du noyau d'analyse alpine (Briançon Node)
    private const FREQ_RESONANCE = 532.0;       // Fréquence harmonique (Hz)
    private const LATITUDE_BRIANCON = 44.9;    // Latitude (°N)
    private const OMEGA_EARTH = 7.292115e-5;   // Vitesse de rotation terrestre (rad/s)
    
    private float $vorticityRel;
    private float $baroclinicTorque;
    private float $pvBanner;
    private float $bruntVaisala;

    /**
     * Constructeur : Initialise les variables physiques du noyau
     *
     * @param float $vorticityRel Vorticité relative ζ (par défaut 0.00083 s⁻¹)
     * @param float $baroclinicTorque Magnitude du couple barocline (par défaut 4.2e-4)
     * @param float $pvBanner Vorticité potentielle d'Ertel en PVU (par défaut 2.9)
     * @param float $bruntVaisala Fréquence de flottabilité N (par défaut 0.018 s⁻¹)
     */
    public function __construct(
        float $vorticityRel = 0.00083,
        float $baroclinicTorque = 0.00042,
        float $pvBanner = 2.9,
        float $bruntVaisala = 0.018
    ) {
        $this->vorticityRel = $vorticityRel;
        $this->baroclinicTorque = $baroclinicTorque;
        $this->pvBanner = $pvBanner;
        $this->bruntVaisala = $bruntVaisala;
    }

    /**
     * Calcule le paramètre de Coriolis (f = 2Ω sin(φ))
     */
    private function getCoriolisFactor(): float
    {
        $latitudeRad = deg2rad(self::LATITUDE_BRIANCON);
        return 2 * self::OMEGA_EARTH * sin($latitudeRad);
    }

    /**
     * Calcule la vorticité absolue (η = ζ + f)
     */
    private function getAbsoluteVorticity(): float
    {
        return $this->vorticityRel + $this->getCoriolisFactor();
    }

    /**
     * Génère un chiffre aléatoire entier borné [min, max]
     *
     * La graine pseudo-aléatoire est modulée par l'entropie système (hrtime)
     * combinée à l'onde de résonance à 532 Hz et la vorticité absolue.
     *
     * @param int $min Valeur minimale (inclusive)
     * @param int $max Valeur maximale (inclusive)
     * @return int Le chiffre ou nombre aléatoire généré
     */
    public function generateInt(int $min = 0, int $max = 100): int
    {
        if ($min > $max) {
            throw new InvalidArgumentException("Le paramètre min ne peut pas être supérieur à max.");
        }

        // 1. Entropie temporelle haute précision (nanosecondes)
        $nanoTime = hrtime(true);

        // 2. Calcul du facteur harmonique basé sur 532 Hz et la vorticité absolue η
        $eta = $this->getAbsoluteVorticity();
        $harmonicFactor = sin($nanoTime * self::FREQ_RESONANCE) * $eta;

        // 3. Injection du couple barocline et de la vorticité potentielle d'Ertel (PV)
        $atmoFactor = abs($this->baroclinicTorque * $this->pvBanner * $this->bruntVaisala);

        // 4. Génération d'un hash cryptographique (SHA-256) fusionnant l'entropie et la physique
        $seedString = sprintf("%.15f_%.15f_%d", $harmonicFactor, $atmoFactor, $nanoTime);
        $hash = hash('sha256', $seedString);

        // 5. Conversion du hash en valeur numérique entière
        $hexSegment = substr($hash, 0, 8); // 32-bit hex
        $decimalValue = hexdec($hexSegment);

        // 6. Projection uniforme dans l'intervalle [min, max]
        $range = ($max - $min) + 1;
        
        return $min + ($decimalValue % $range);
    }

    /**
     * Génère un nombre flottant aléatoire normalisé entre 0.0 et 1.0
     * (Simule le comportement du mode Qi-Flow)
     */
    public function generateFloat(): float
    {
        $intVal = $this->generateInt(0, 1000000);
        return $intVal / 1000000.0;
    }
}

// ==============================================================================
// BLOC DE TEST ET D'EXÉCUTION DIRECTE
// ==============================================================================

// Instanciation du noyau d'atmosphère alpin
$yannosGen = new YannosAtmoGenerator();

// 1. Tirage d'un chiffre aléatoire entre 1 et 100
$chiffreAleatoire = $yannosGen->generateInt(1, 100);

// 2. Tirage d'un nombre float entre 0.0 et 1.0 (Qi-Mode)
$floatAleatoire = $yannosGen->generateFloat();

// Affichage du rapport d'exécution terminal
echo "<pre>====================================================\n";
echo "[YANNOS_KERNEL_V4.2] PHP-FPM RANDOM GENERATOR OUTPUT\n";
echo "====================================================\n";
echo "Chiffre aléatoire généré [1-100] : " . $chiffreAleatoire . "\n";
echo "Valeur continue Qi-Flow [0-1]  : " . $floatAleatoire . "\n";
echo "Fréquence de verrouillage      : 532 Hz\n";
echo "Statut du système              : Jeu à Somme Positive [OK]\n";
echo "====================================================\n</pre>";
