#!/bin/bash

echo "╔═══════════════════════════════════════════════════════════╗"
echo "║     TunisiaJourney AI Chatbot - Deep Learning Edition     ║"
echo "╚═══════════════════════════════════════════════════════════╝"
echo ""

# Vérifier Python
if ! command -v python3 &> /dev/null; then
    echo "❌ Python 3 n'est pas installé"
    exit 1
fi

# Créer environnement virtuel
if [ ! -d "venv" ]; then
    echo "📦 Création de l'environnement virtuel..."
    python3 -m venv venv
fi

# Activer l'environnement
source venv/bin/activate

# Installer les dépendances
echo "📚 Installation des dépendances..."
pip install --upgrade pip
pip install -r requirements.txt

# Lancer le service
echo ""
echo "🚀 Démarrage du service IA sur http://localhost:8001"
echo "📝 API Documentation: http://localhost:8001/docs"
echo ""
echo "⚠️  Le premier chargement peut prendre quelques minutes"
echo "   (téléchargement des modèles IA)"
echo ""

python ai_service.py