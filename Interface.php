<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analyseur de Texte</title>
    <link rel="stylesheet" href="Interface.css">
    <script src="https://d3js.org/d3.v5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/d3-cloud/1.2.5/d3.layout.cloud.min.js"></script>
</head>
<body>
    <div style="position: absolute; top: 10px; right: 10px;">
        <a href="Readme.pdf" target="_blank" style="text-decoration: none;">
            <button style="padding: 10px 15px; background-color: #007BFF; color: white; border: none; border-radius: 5px; cursor: pointer;">
                Documentation
            </button>
        </a>
    </div>

    <div class="container">
        <h1>Analyseur de Fréquence des Mots</h1>
        <form method="POST" enctype="multipart/form-data">
            <label for="text">Saisissez ou modifiez votre texte :</label>
            <textarea name="text" id="text"><?php
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
                        $fileTmpPath = $_FILES['file']['tmp_name'];
                        echo htmlspecialchars(file_get_contents($fileTmpPath));
                    } elseif (!empty($_POST['text'])) {
                        echo htmlspecialchars($_POST['text']);
                    }
                }
            ?></textarea>

            <label for="file">Ou choisissez un fichier texte :</label>
            <input type="file" name="file" id="file" accept=".txt">

            <label for="numWords">Nombre de mots à afficher :</label>
            <input type="number" name="numWords" id="numWords" value="<?php echo isset($_POST['numWords']) ? intval($_POST['numWords']) : 10; ?>" min="1" max="100">

            <button type="submit" class="analyze-button">Analyser</button>
        </form>

        <?php
        include 'Code.php';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $text = '';

            if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
                $fileTmpPath = $_FILES['file']['tmp_name'];
                $text = file_get_contents($fileTmpPath);
            } elseif (!empty($_POST['text'])) {
                $text = $_POST['text'];
            }

            $numWords = isset($_POST['numWords']) ? intval($_POST['numWords']) : 10;

            if (empty($text)) {
                echo "<p class='error'>Veuillez saisir un texte ou sélectionner un fichier.</p>";
            } else {
                $topWords = analyzeFrequency($text);

                if (isset($topWords['error'])) {
                    echo "<p class='error'>" . htmlspecialchars($topWords['error']) . "</p>";
                } else {
                    $topWords = array_slice($topWords, 0, $numWords);

                    // Conteneur du slider pour activer/désactiver l'affichage du tableau
                    echo "<div class='slider-container'>";
                    echo "<label class='switch'>";
                    echo "<input type='checkbox' id='toggleTable'>";
                    echo "<span class='slider round'></span>";
                    echo "</label>";
                    echo "<span class='slider-label'>Afficher/Masquer le tableau</span>";
                    echo "</div>";

                    // Conteneur du tableau
                    echo "<div class='table-container' id='tableContainer'>";
                    echo "<h2>Top des mots les plus fréquents :</h2>";
                    echo "<table>";
                    echo "<tr><th>Mot</th><th>Fréquence</th></tr>";

                    foreach ($topWords as $entry) {
                        echo "<tr><td>{$entry['mot']}</td><td>{$entry['nombre']}</td></tr>";
                    }
                    echo "</table>";
                    echo "</div>";

                    // Boutons pour choisir la forme du nuage de mots
                    echo "<div class='shape-buttons'>";
                    echo "<button id='circleShape' class='shape-button'>Cerclefication</button>";
                    echo "<button id='squareShape' class='shape-button'>Carréification</button>";
                    echo "</div>";

                    // Nuage de mots
                    echo "<h2>Nuage de mots clé :</h2>";
                    echo "<div class='word-cloud-container' id='wordCloud'></div>";

                    echo "<script>const topWords = " . json_encode($topWords) . ";</script>";
                }
            }
        }
        ?>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", () => {
    const wordCloudContainer = document.getElementById("wordCloud");
    const width = wordCloudContainer.offsetWidth;
    const height = 400;

    // Écouteurs d'événements pour les boutons de forme
    const shapeButtons = document.querySelectorAll(".shape-button");
    shapeButtons.forEach(button => {
        button.addEventListener("click", event => {
            const shape = event.target.id.replace("Shape", "").toLowerCase();
            generateWordCloud(shape);
        });
    });

    // Forme par défaut
    generateWordCloud("circle");

    function generateWordCloud(shape) {
        // Vider le nuage de mots existant
        wordCloudContainer.innerHTML = "";

        // Échelle des tailles de police dynamiques pour éviter les disparités importantes
        const maxFrequency = Math.max(...topWords.map(d => d.nombre));
        const fontSizeScale = d3.scaleLinear()
            .domain([0, maxFrequency]) // Plage des fréquences
            .range([15, 120]); // Plage des tailles de police

        const layout = d3.layout.cloud()
            .size([width, height])
            .words(topWords.map(d => ({
                text: d.mot,
                size: fontSizeScale(d.nombre) // Taille mise à l'échelle dynamiquement
            })))
            .padding(1) // Ajuster l'espace entre les mots
            .rotate(() => (shape === "square" ? 0 : ~~(Math.random() * 2) * 90)) // Seulement horizontal dans la forme "carré"
            .font("Arial")
            .fontSize(d => d.size)
            .spiral(shape === "circle" ? "archimedean" : "rectangular") // Type de spirale
            .on("end", draw);

        layout.start();
    }

    function draw(words) {
        const svg = d3.select("#wordCloud")
            .append("svg")
            .attr("width", width)
            .attr("height", height)
            .append("g")
            .attr("transform", `translate(${width / 2},${height / 2})`);

        svg.selectAll("text")
            .data(words)
            .enter()
            .append("text")
            .style("font-size", d => `${d.size}px`)
            .style("font-family", "Arial")
            .style("fill", () => `hsl(${Math.random() * 360}, 70%, 50%)`)
            .attr("text-anchor", "middle")
            .attr("transform", d => `translate(${d.x},${d.y})rotate(${d.rotate})`)
            .text(d => d.text);
    }

    // Ajouter une fonctionnalité pour activer/désactiver la visibilité du tableau
    const toggleTableCheckbox = document.getElementById("toggleTable");
    const tableContainer = document.getElementById("tableContainer");

    toggleTableCheckbox.addEventListener("change", () => {
        tableContainer.style.display = toggleTableCheckbox.checked ? "block" : "none";
    });

    // Définir l'état initial de la visibilité du tableau en fonction de la case à cocher
    tableContainer.style.display = toggleTableCheckbox.checked ? "block" : "none";
});
    </script>
</body>
</html>
