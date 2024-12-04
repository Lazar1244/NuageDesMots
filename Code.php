<?php

// Fonction pour lire le contenu d'un fichier
function readFileContent($filePath) {
    return file_get_contents($filePath);
}

// Fonction pour lire un fichier JSON et le décoder en tableau
function readJsonFile($filePath) {
    $content = file_get_contents($filePath);
    return json_decode($content, true);
}

// Fonction pour lemmatiser un mot en utilisant les règles dans un fichier JSON
function lemmatizeWord($word, $lemmatizationRules) {
    // Si le mot a une règle définie dans le fichier JSON, renvoyer le lemme
    return $lemmatizationRules[$word] ?? $word; // Sinon renvoie le mot tel quel
}

// Fonction pour analyser la fréquence des mots
function analyzeFrequency($text, $exclusionPath = 'exclusion.json', $lemmatizationPath = 'lemmatizationRules.json') {
    // Lire la liste des mots d'exclusion de exclusion.json
    $exclusionWords = readJsonFile($exclusionPath);
    if (!is_array($exclusionWords)) {
        return ["error" => "Le fichier exclusion.json doit contenir un tableau de mots."];
    }

    // Lire les règles de lemmatisation de lemmatizationRules.json
    $lemmatizationRules = readJsonFile($lemmatizationPath);
    if (!is_array($lemmatizationRules)) {
        return ["error" => "Le fichier lemmatizationRules.json doit contenir un dictionnaire de règles de lemmatisation."];
    }

    // Mettre les mots d'exclusion dans un tableau associatif pour une recherche rapide
    $exclusionSet = array_flip($exclusionWords);

    // Transformer le texte en mots
    // Conserver les caractères alphanumériques, apostrophes (simples et typographiques), et tirets
    $text = preg_replace('/[^\p{L}\p{N}\s\'’\-]/u', '', $text);
    $words = preg_split('/\s+/', mb_strtolower($text));

    // Calculer la fréquence des mots en excluant ceux de la liste d'exclusion
    $frequencyMap = [];
    foreach ($words as $word) {
        // Nettoyer les mots des apostrophes isolées
        $word = trim($word, "'’");

        // Appliquer la lemmatisation
        $lemmatizedWord = lemmatizeWord($word, $lemmatizationRules);

        if ($lemmatizedWord && !isset($exclusionSet[$lemmatizedWord])) { // Vérifie que le mot n'est pas dans l'exclusion et n'est pas vide
            if (isset($frequencyMap[$lemmatizedWord])) {
                $frequencyMap[$lemmatizedWord]++;
            } else {
                $frequencyMap[$lemmatizedWord] = 1;
            }
        }
    }

    // Convertir la fréquence des mots en un tableau trié par ordre de fréquence décroissant
    $frequencyArray = [];
    foreach ($frequencyMap as $word => $count) {
        $frequencyArray[] = ["mot" => $word, "nombre" => $count];
    }

    // Trier par fréquence en ordre décroissant
    usort($frequencyArray, function($a, $b) {
        return $b['nombre'] - $a['nombre'];
    });

    return $frequencyArray;
}

?>
