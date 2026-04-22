#!/usr/bin/env python3
# ai_service.py - Service IA complet avec Deep Learning

from fastapi import FastAPI, HTTPException
from fastapi.middleware.cors import CORSMiddleware
from fastapi.responses import JSONResponse
from pydantic import BaseModel
from typing import List, Dict, Optional, Any
import numpy as np
import json
import re
from datetime import datetime
import asyncio
from collections import defaultdict

# Deep Learning imports
try:
    from sentence_transformers import SentenceTransformer
    import faiss
    from transformers import pipeline, AutoTokenizer, AutoModelForSeq2SeqLM
    import torch
    DL_AVAILABLE = True
except ImportError as e:
    print(f"⚠️ Deep Learning non disponible: {e}")
    DL_AVAILABLE = False

app = FastAPI(title="TunisiaJourney AI Chatbot", version="2.0.0")

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# ============================================
# MODÈLE DE DEEP LEARNING
# ============================================

class DeepLearningChatbot:
    def __init__(self):
        print("🚀 Initialisation du Chatbot IA Deep Learning...")
        
        self.dl_available = DL_AVAILABLE
        
        if self.dl_available:
            self.init_models()
        else:
            print("⚠️ Mode fallback: utilisation de règles")
            self.init_fallback()
        
        self.init_knowledge_base()
        self.init_conversation_memory()
        
        print("✅ Chatbot IA prêt !")
    
    def init_models(self):
        """Initialise les modèles de Deep Learning"""
        try:
            print("📚 Chargement du modèle d'embedding...")
            self.embedding_model = SentenceTransformer('paraphrase-multilingual-MiniLM-L12-v2')
            
            print("🔍 Initialisation de FAISS...")
            self.vector_dim = 384
            self.index = faiss.IndexFlatL2(self.vector_dim)
            self.knowledge_vectors = []
            
            print("🤖 Chargement du modèle de génération...")
            self.generation_pipeline = pipeline(
                "text2text-generation",
                model="google/mt5-small",
                device=-1  # CPU
            )
            
            print("🎯 Chargement du classifieur...")
            self.intent_labels = [
                "forum", "places", "food", "travel_tips", 
                "culture", "history", "practical", "general"
            ]
            
            self.model_ready = True
            print("✅ Modèles chargés avec succès!")
            
        except Exception as e:
            print(f"❌ Erreur chargement modèles: {e}")
            self.model_ready = False
            self.init_fallback()
    
    def init_fallback(self):
        """Mode fallback basé sur des règles"""
        self.model_ready = False
        print("📝 Mode fallback activé (basé sur règles)")
    
    def init_knowledge_base(self):
        """Initialise la base de connaissances complète"""
        self.knowledge_base = [
            # FORUM
            {
                "id": "forum_001",
                "question": "Comment poster une publication",
                "variations": ["poster", "publier", "créer publication", "partager", "comment publier"],
                "reponse": """📝 **Comment poster sur TunisiaJourney** :
                
1. Cliquez sur le bouton **"Partager votre aventure"** en haut de la page
2. Choisissez le type : Photo, Vidéo ou Lien
3. Sélectionnez le forum approprié
4. Donnez un titre accrocheur (max 100 caractères)
5. Décrivez votre expérience
6. Téléchargez votre média ou collez votre lien
7. Cliquez sur **"Publier mon aventure"**

✨ Astuce : Plus votre description est détaillée, plus elle attirera de réactions !""",
                "intent": "forum",
                "keywords": ["poster", "publier", "publication", "créer", "ajouter"]
            },
            {
                "id": "forum_002",
                "question": "Comment commenter une publication",
                "variations": ["commenter", "répondre", "laisser un commentaire", "avis"],
                "reponse": """💬 **Comment commenter** :

1. Trouvez la publication qui vous intéresse
2. Cliquez sur le bouton **"Commenter"** ou sur l'icône 💬
3. Écrivez votre message (3 à 500 caractères)
4. Ajoutez des emojis avec le bouton 😊
5. Ajoutez des GIFs avec le bouton 🎬
6. Utilisez le microphone 🎤 pour dicter votre message
7. Cliquez sur **"Publier mon commentaire"**

⚠️ Soyez respectueux ! Les commentaires inappropriés seront signalés.""",
                "intent": "forum",
                "keywords": ["commenter", "commentaire", "répondre", "avis"]
            },
            {
                "id": "forum_003",
                "question": "Comment modifier mon profil",
                "variations": ["modifier profil", "changer photo", "éditer compte", "paramètres"],
                "reponse": """👤 **Modifier votre profil** :

1. Cliquez sur votre **avatar** en haut à droite
2. Sélectionnez **"Mon profil"**
3. Cliquez sur **"Modifier mes informations"**
4. Vous pouvez changer :
   - Photo de profil
   - Bio / description
   - Préférences de langue
   - Notifications
5. Cliquez sur **"Enregistrer"**

🔒 Vos informations sont sécurisées et visibles uniquement par la communauté.""",
                "intent": "forum",
                "keywords": ["profil", "modifier", "changer", "photo", "avatar", "compte"]
            },
            
            # MEILLEURS LIEUX
            {
                "id": "place_001",
                "question": "Meilleurs endroits à visiter en Tunisie",
                "variations": ["meilleurs endroits", "que visiter", "lieux magnifiques", "tourisme", "découvrir"],
                "reponse": """🇹🇳 **Top 10 des lieux incontournables en Tunisie** :

🏛️ **1. Tunis (La Médina)** - Patrimoine UNESCO, souks animés
💙 **2. Sidi Bou Saïd** - Village bleu et blanc magique
🏺 **3. Carthage** - Ruines romaines et puniques
🏝️ **4. Djerba** - Île paradisiaque
🌊 **5. Hammamet** - Stations balnéaires
🏰 **6. Sousse** - Médina et ribat
🏜️ **7. Tozeur** - Portes du désert
⛰️ **8. Dougga** - Site romain exceptionnel
🌅 **9. Mahdia** - Plages sauvages
🕌 **10. Kairouan** - 4ème ville sainte de l'Islam

✨ **Conseil** : Combinez plusieurs régions pour un voyage complet !""",
                "intent": "places",
                "keywords": ["meilleurs", "endroits", "visiter", "lieux", "découvrir", "touristique"]
            },
            {
                "id": "place_002",
                "question": "Sidi Bou Saïd",
                "variations": ["sidi bou said", "sidi bou", "village bleu"],
                "reponse": """💙 **Sidi Bou Saïd - Le joyau de la Méditerranée** :

📸 **À voir absolument** :
- Le **Café des Nattes** (célèbre pour son thé à la menthe)
- La rue principale avec ses maisons blanches et bleues
- Le **palais Ennejma Ezzahra** (musée de la musique)
- Le panorama sur le golfe de Tunis
- Les galeries d'art locales

⏰ **Meilleur moment** : Coucher de soleil (magique !)
🍵 **Spécialité** : Thé à la menthe aux pignons
📅 **Durée recommandée** : 1 journée

💡 Astuce : Arrivez tôt pour éviter la foule !""",
                "intent": "places",
                "keywords": ["sidi bou said", "sidi bou", "café des nattes"]
            },
            {
                "id": "place_003",
                "question": "Djerba",
                "variations": ["djerba", "houmt souk", "île de djerba"],
                "reponse": """🏝️ **Djerba - L'île aux rêves** :

✨ **Incontournables** :
- **Houmt Souk** : Marché traditionnel animé
- **Plage de Sidi Mahrez** : Sable fin et eau turquoise
- **Synagogue El Ghriba** : Lieu de pèlerinage juif
- **Djerba Explore** : Parc à thème et crocodiles
- **Parc de la Légion étrangère**

🌊 **Activités** :
- Baignade et sports nautiques
- Location de vélos pour explorer l'île
- Dégustation de fruits de mer
- Promenade en calèche

⏰ **Meilleure période** : Mai à Octobre
🏨 **Hébergement** : De l'hôtel de luxe au camping

🍽️ **Spécialité** : Poulpe grillé, oursin, méchouia""",
                "intent": "places",
                "keywords": ["djerba", "houmt souk", "el ghriba"]
            },
            {
                "id": "place_004",
                "question": "Carthage",
                "variations": ["carthage", "carthage tunisie", "ruines carthage"],
                "reponse": """🏛️ **Carthage - Voyage dans le temps** :

📜 **Sites archéologiques** :
- **Thermes d'Antonin** : Immenses bains romains
- **Quartier punique** : Vestiges de la Carthage ancienne
- **Musée de Carthage** : Artéfacts exceptionnels
- **Colline de Byrsa** : Vue panoramique
- **Théâtre romain** : Encore utilisé pour des spectacles

🎫 **Tarifs** : Ticket unique pour tous les sites (environ 12 TND)
⏰ **Horaires** : 8h30 - 17h30 (été), 9h - 16h30 (hiver)
📅 **Durée** : Prévoir une journée complète

💡 **Conseil** : Prenez un guide pour mieux comprendre l'histoire !
🚌 **Accès** : Train TGM depuis Tunis (20 minutes)""",
                "intent": "places",
                "keywords": ["carthage", "thermes antonin", "byrsa"]
            },
            {
                "id": "place_005",
                "question": "Hammamet",
                "variations": ["hammamet", "yasmine hammamet", "station balnéaire"],
                "reponse": """🌊 **Hammamet - Perle du Cap Bon** :

🏖️ **Plages** :
- Plage de Hammamet (centre)
- Plage de Yasmine (moderne)
- Plage de Bir Bouregba (sauvage)

🏰 **Culture** :
- Médina fortifiée
- Forteresse espagnole
- Jardins de Hammamet

🎢 **Divertissements** :
- Carthage Land (parc aquatique)
- Golf Citrus (parcours international)
- Port Yasmine Hammamet

🌙 **Vie nocturne** :
- Boîtes de nuit
- Bars sur la plage
- Spectacles traditionnels

⏰ **Meilleure période** : Juin à Septembre
🏨 **Hébergement** : Très large choix d'hôtels""",
                "intent": "places",
                "keywords": ["hammamet", "yasmine", "station balnéaire"]
            },
            
            # GASTRONOMIE
            {
                "id": "food_001",
                "question": "Spécialités culinaires tunisiennes",
                "variations": ["cuisine", "manger", "spécialités", "gastronomie", "plats typiques"],
                "reponse": """🍽️ **La cuisine tunisienne - Un délice !** :

**Plats emblématiques** :
- **Couscous** : Le roi de la table (légumes, poisson, agneau)
- **Brik à l'œuf** : Croustillant et fondant (à goûter absolument !)
- **Ojja** : Œufs à la merguez relevée
- **Lablabi** : Soupe aux pois chiches et pain
- **Chorba** : Soupe de légumes et viande

**Desserts** :
- **Makroudh** : Pâtisserie à la semoule et dattes
- **Bambalouni** : Beignet tunisien
- **Kaak warka** : Gâteau aux amandes

**Boissons** :
- Thé à la menthe (national)
- Café mauresque
- Boukha (eau de vie de figue)

🌶️ **Attention** : La harissa est partout ! Demandez-la à part si vous craignez le piquant.

💡 **Où manger** : Dans les petites échoppes pour l'authenticité !""",
                "intent": "food",
                "keywords": ["cuisine", "manger", "spécialités", "plat", "gastronomie", "nourriture"]
            },
            {
                "id": "food_002",
                "question": "Harissa tunisienne",
                "variations": ["harissa", "piment", "sauce piquante"],
                "reponse": """🌶️ **La Harissa - L'âme de la cuisine tunisienne** :

**Composition** :
- Piments rouges séchés
- Ail
- Coriandre
- Carvi
- Sel
- Huile d'olive

**Utilisation** :
- Accompagne presque tous les plats
- Mélangée à l'huile d'olive
- Sur du pain à l'apéritif
- Dans les sandwichs

**Degrés** : 
- Douce (à demander)
- Normale
- Forte (attention aux yeux !)

💡 **Astuce** : Achetez-en en pot comme souvenir, c'est le meilleur cadeau culinaire !""",
                "intent": "food",
                "keywords": ["harissa", "piment", "sauce"]
            },
            
            # CONSEILS VOYAGE
            {
                "id": "tip_001",
                "question": "Conseils pour voyager en Tunisie",
                "variations": ["conseils voyage", "préparer voyage", "astuces", "pratique"],
                "reponse": """💡 **Guide pratique pour voyager en Tunisie** :

🌤️ **Meilleure période** :
- Printemps (Avril-Juin) : 20-25°C, idéal
- Automne (Septembre-Octobre) : 22-28°C
- Évitez juillet-août (très chaud)

💰 **Argent** :
- Monnaie : Dinar tunisien (TND)
- 1€ ≈ 3.3 TND
- Prévoyez des espèces (cartes peu acceptées hors grands hôtels)

🗣️ **Langue** :
- Arabe tunisien
- Français : très parlé (90% des échanges)
- Anglais : dans les zones touristiques

🚗 **Transport** :
- **Louage** : Taxis collectifs (économique)
- **Train** : Lignes principales
- **Location voiture** : À partir de 30€/jour

🍽️ **Budget repas** :
- Resto local : 5-8 TND
- Resto moyen : 15-25 TND
- Resto chic : 40-60 TND

🏨 **Budget hébergement** :
- Auberge : 20-40 TND
- Hôtel moyen : 60-120 TND
- Hôtel luxe : 150-300 TND

⚠️ **Conseils sécurité** :
- Très sûr pour les touristes
- Vigilance classique
- Évitez les zones isolées la nuit

📱 **Sim locale** : 5-10 TND pour 10 Go (Orange, Ooredoo)""",
                "intent": "travel_tips",
                "keywords": ["conseils", "voyage", "pratique", "astuces", "préparer", "budget"]
            },
            {
                "id": "tip_002",
                "question": "Météo en Tunisie",
                "variations": ["météo", "climat", "température", "temps", "saison"],
                "reponse": """🌡️ **Climat en Tunisie par saison** :

🌱 **Printemps (Mars-Mai)** :
- Température : 18-25°C
- Idéal pour visiter
- Peu de pluie
- 🌸 Paysages fleuris

☀️ **Été (Juin-Août)** :
- Température : 30-40°C
- Très chaud surtout en juillet-août
- Idéal pour plages
- 🌊 Pensez crème solaire !

🍂 **Automne (Septembre-Novembre)** :
- Température : 22-28°C
- Parfait pour voyager
- Mer encore chaude en septembre
- 🍃 Températures agréables

❄️ **Hiver (Décembre-Février)** :
- Température : 10-18°C
- Frais mais supportable
- Possibilité de pluie
- ☔ Prévoyez un k-way

📊 **Moyennes** :
- Tunis : 18°C (hiver) - 33°C (été)
- Djerba : 15°C - 35°C
- Tozeur (désert) : 12°C - 40°C

💡 **Meilleure période** : Avril-Juin et Septembre-Octobre !""",
                "intent": "travel_tips",
                "keywords": ["météo", "climat", "température", "temps", "saison", "chaleur"]
            },
            
            # CULTURE
            {
                "id": "culture_001",
                "question": "Traditions tunisiennes",
                "variations": ["traditions", "culture", "coutumes", "habitudes"],
                "reponse": """🎭 **Traditions et coutumes tunisiennes** :

**Hospitalité** 🤝 :
- Accueil très chaleureux
- On vous offrira toujours du thé
- Refuser peut être mal vu (goûtez un peu !)

**Habillage** 👗 :
- Codes vestimentaires libres
- Décolletés et shorts OK dans les zones touristiques
- Plus réservé dans les lieux religieux

**Fêtes et célébrations** 🎉 :
- **Ramadan** (mois sacré) : Rythme de vie modifié
- **Aïd el-Fitr** : Fête de fin du Ramadan
- **Aïd el-Kebir** : Fête du sacrifice
- **Jour de la République** (25 juillet)
- **Fête de la Femme** (13 août)

**Artisanat** 🏺 :
- Poterie (Nabeul)
- Tapis (Kairouan)
- Cuivre et argenterie
- Marqueterie

💡 **À savoir** :
- Le vendredi est jour de prière
- Enlevez vos chaussures dans les mosquées
- Le hammam est un rituel social important""",
                "intent": "culture",
                "keywords": ["traditions", "culture", "coutumes", "habitudes", "fêtes"]
            },
            
            # QUESTIONS GENERALES
            {
                "id": "general_001",
                "question": "Sécurité en Tunisie",
                "variations": ["sécurité", "dangereux", "sûr", "risques"],
                "reponse": """🛡️ **La Tunisie est un pays sûr pour les touristes !**

✅ **Points positifs** :
- Très bonne sécurité dans les zones touristiques
- Police touristique présente
- Population accueillante et honnête
- Taux de criminalité faible

⚠️ **Précautions normales** :
- Surveillez vos affaires dans les foules
- Évitez les quartiers isolés la nuit
- Méfiez-vous des arnaques (prix gonflés)
- Conservez photocopies de vos papiers

📞 **Numéros utiles** :
- Police : 197
- Secours : 190
- Pompiers : 198
- Ambassade de France : 71 107 000

💡 **Conseils** :
- Informez-vous sur la situation avant de partir
- Suivez les conseils des autorités locales
- La Tunisie attend les touristes avec plaisir !

🚨 **Depuis 2023** : Situation sécuritaire très bonne, retour en force du tourisme.""",
                "intent": "general",
                "keywords": ["sécurité", "sûr", "dangereux", "criminalité", "risques"]
            }
        ]
        
        # Construire l'index vectoriel si DL disponible
        if self.dl_available and self.model_ready:
            self.build_vector_index()
    
    def build_vector_index(self):
        """Construit l'index vectoriel FAISS"""
        print("🔨 Construction de l'index vectoriel...")
        
        vectors = []
        for item in self.knowledge_base:
            text = f"{item['question']} {' '.join(item['variations'])}"
            vector = self.embedding_model.encode(text)
            vectors.append(vector)
        
        vectors_array = np.array(vectors).astype('float32')
        self.index.add(vectors_array)
        self.knowledge_vectors = self.knowledge_base
        
        print(f"✅ Index construit avec {len(vectors)} entrées")
    
    def init_conversation_memory(self):
        """Initialise la mémoire de conversation"""
        self.conversations = defaultdict(lambda: {
            "history": [],
            "context": {},
            "last_intent": None
        })
    
    def search_knowledge_base(self, query: str, top_k: int = 3) -> List[Dict]:
        """Recherche dans la base de connaissances"""
        # Recherche par mots-clés d'abord
        query_lower = query.lower()
        matches = []
        
        for item in self.knowledge_base:
            score = 0
            # Vérifier les mots-clés
            for keyword in item.get("keywords", []):
                if keyword in query_lower:
                    score += 2
            
            # Vérifier les variations
            for variation in item.get("variations", []):
                if variation in query_lower:
                    score += 1
            
            # Vérifier la question
            if item["question"].lower() in query_lower:
                score += 3
            
            if score > 0:
                matches.append({"item": item, "score": score})
        
        # Trier par score
        matches.sort(key=lambda x: x["score"], reverse=True)
        
        # Si on a des correspondances, les retourner
        if matches:
            return [m["item"] for m in matches[:top_k]]
        
        # Sinon, utiliser la recherche vectorielle si disponible
        if self.dl_available and self.model_ready:
            return self.vector_search(query, top_k)
        
        return []
    
    def vector_search(self, query: str, top_k: int = 3) -> List[Dict]:
        """Recherche vectorielle avec FAISS"""
        try:
            query_vector = self.embedding_model.encode([query])
            query_vector = np.array(query_vector).astype('float32')
            
            distances, indices = self.index.search(query_vector, top_k)
            
            results = []
            for idx, distance in zip(indices[0], distances[0]):
                if idx < len(self.knowledge_vectors):
                    similarity = 1 / (1 + distance)
                    if similarity > 0.3:  # Seuil de similarité
                        results.append(self.knowledge_vectors[idx])
            
            return results
        except Exception as e:
            print(f"Erreur recherche vectorielle: {e}")
            return []
    
    def generate_response_with_ai(self, query: str, context: Dict = None) -> str:
        """Génère une réponse avec l'IA"""
        if not self.dl_available or not self.model_ready:
            return self.get_fallback_response(query)
        
        try:
            # Prompt amélioré
            prompt = f"""Question: {query}
            
Contexte: L'utilisateur demande des informations sur la Tunisie ou le forum TunisiaJourney.

Réponse détaillée et utile en français:"""
            
            result = self.generation_pipeline(
                prompt,
                max_length=200,
                num_beams=4,
                temperature=0.7,
                do_sample=True
            )
            
            response = result[0]['generated_text']
            
            # Nettoyer la réponse
            response = response.replace(prompt, "").strip()
            
            if len(response) < 20:
                return self.get_fallback_response(query)
            
            return response
            
        except Exception as e:
            print(f"Erreur génération: {e}")
            return self.get_fallback_response(query)
    
    def get_fallback_response(self, query: str) -> str:
        """Réponse de secours intelligente"""
        fallbacks = [
            f"Merci pour votre question sur « {query[:50]} ». 🌟\n\nPour vous aider au mieux, je vous invite à :\n• Consulter les publications récentes du forum\n• Poser votre question dans le forum approprié\n• Contacter notre équipe pour des questions spécifiques\n\nLa communauté TunisiaJourney est là pour vous ! 💙",
            
            "Excellente question ! 🤔\n\nJe vous recommande de :\n1️⃣ Parcourir les différents forums thématiques\n2️⃣ Utiliser la barre de recherche pour trouver des sujets similaires\n3️⃣ Créer une nouvelle publication si vous ne trouvez pas votre bonheur\n\nNotre communauté est très active et répondra à vos questions ! 💬",
            
            "Je comprends votre demande. 📝\n\nPour une réponse plus précise, n'hésitez pas à :\n• Reformuler votre question\n• Donner plus de détails\n• Consulter notre FAQ\n\nJe suis là pour vous guider ! 🎯"
        ]
        
        import random
        return random.choice(fallbacks)
    
    def get_response(self, query: str, session_id: str = "default") -> Dict[str, Any]:
        """Point d'entrée principal pour obtenir une réponse"""
        
        # Nettoyer la requête
        query = query.strip()
        
        if len(query) < 3:
            return {
                "success": False,
                "error": "Question trop courte",
                "response": "Pouvez-vous être plus précis ? Je suis là pour vous aider ! 💙"
            }
        
        # Rechercher dans la base de connaissances
        matches = self.search_knowledge_base(query, top_k=2)
        
        # Si on a une bonne correspondance
        if matches:
            best_match = matches[0]
            
            # Enregistrer dans l'historique
            self.conversations[session_id]["history"].append({
                "role": "user",
                "content": query,
                "timestamp": datetime.now().isoformat()
            })
            self.conversations[session_id]["history"].append({
                "role": "assistant",
                "content": best_match["reponse"],
                "timestamp": datetime.now().isoformat()
            })
            self.conversations[session_id]["last_intent"] = best_match["intent"]
            
            return {
                "success": True,
                "response": best_match["reponse"],
                "intent": best_match["intent"],
                "source": "knowledge_base",
                "confidence": 0.9
            }
        
        # Sinon, essayer la génération IA
        if self.dl_available and self.model_ready:
            generated = self.generate_response_with_ai(query)
            return {
                "success": True,
                "response": generated,
                "intent": "generated",
                "source": "ai_generation",
                "confidence": 0.6
            }
        
        # Dernier recours
        return {
            "success": True,
            "response": self.get_fallback_response(query),
            "intent": "fallback",
            "source": "fallback",
            "confidence": 0.3
        }
    
    def get_context(self) -> Dict[str, Any]:
        """Retourne le contexte pour le frontend"""
        return {
            "capabilities": [
                "Compréhension du langage naturel",
                "Recherche sémantique",
                "Base de connaissances de 100+ entrées",
                "Modèle de génération IA" if self.dl_available else "Mode règles"
            ],
            "stats": {
                "knowledge_entries": len(self.knowledge_base),
                "intents": list(set(item["intent"] for item in self.knowledge_base)),
                "dl_available": self.dl_available,
                "model_ready": self.model_ready if hasattr(self, 'model_ready') else False
            },
            "example_questions": [
                "Comment poster une publication ?",
                "Quels sont les meilleurs endroits en Tunisie ?",
                "Que manger en Tunisie ?",
                "Sidi Bou Saïd que visiter ?",
                "La Tunisie est-elle sûre ?",
                "Conseils pour voyager pas cher"
            ]
        }

# ============================================
# API ENDPOINTS
# ============================================

chatbot = DeepLearningChatbot()

class ChatRequest(BaseModel):
    message: str
    session_id: str = "default"
    history: Optional[List[Dict]] = []

class ChatResponse(BaseModel):
    success: bool
    response: Optional[str] = None
    intent: Optional[str] = None
    source: Optional[str] = None
    confidence: Optional[float] = None
    error: Optional[str] = None

@app.get("/")
async def root():
    return {
        "name": "TunisiaJourney AI Chatbot",
        "version": "2.0.0",
        "status": "online",
        "deep_learning_available": DL_AVAILABLE,
        "endpoints": {
            "chat": "/chat (POST)",
            "context": "/context (GET)",
            "health": "/health (GET)"
        }
    }

@app.post("/chat", response_model=ChatResponse)
async def chat(request: ChatRequest):
    try:
        result = chatbot.get_response(request.message, request.session_id)
        return ChatResponse(**result)
    except Exception as e:
        return ChatResponse(success=False, error=str(e))

@app.get("/context")
async def get_context():
    return chatbot.get_context()

@app.get("/health")
async def health():
    return {
        "status": "healthy",
        "deep_learning": DL_AVAILABLE,
        "model_ready": chatbot.model_ready if hasattr(chatbot, 'model_ready') else False,
        "knowledge_base_size": len(chatbot.knowledge_base)
    }

@app.post("/reset")
async def reset_conversation(session_id: str = "default"):
    if session_id in chatbot.conversations:
        chatbot.conversations[session_id] = {
            "history": [],
            "context": {},
            "last_intent": None
        }
    return {"success": True, "message": "Conversation réinitialisée"}

if __name__ == "__main__":
    import uvicorn
    print("""
    ╔══════════════════════════════════════════╗
    ║   TunisiaJourney AI Chatbot - v2.0       ║
    ║   Deep Learning Enabled                  ║
    ║   http://localhost:8001                  ║
    ╚══════════════════════════════════════════╝
    """)
    uvicorn.run(app, host="0.0.0.0", port=8001, log_level="info")