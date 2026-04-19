"""
train_model.py
==============
Entraîne un modèle de sentiment analysis sur des avis en français.
Utilise un dataset intégré + dataset français public (CamemBERT-ready).
Sauvegarde le modèle dans model/sentiment_model.pkl

Usage :
    python train_model.py
"""

import os, json, pickle
import numpy as np
from sklearn.pipeline import Pipeline
from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.linear_model import LogisticRegression
from sklearn.model_selection import train_test_split, cross_val_score
from sklearn.metrics import classification_report, confusion_matrix
import re

# ─────────────────────────────────────────────
# 1. DATASET FRANÇAIS INTÉGRÉ (avis d'activités)
#    Label : 0 = négatif, 1 = neutre, 2 = positif
# ─────────────────────────────────────────────
DATASET = [
    # ── POSITIFS ──
    ("Super activité, guide très professionnel et accueillant !", 2),
    ("Expérience incroyable, je recommande vivement à tout le monde.", 2),
    ("Excellent encadrement, matériel de qualité, on reviendra !", 2),
    ("Magnifique journée, organisation parfaite du début à la fin.", 2),
    ("Très bonne ambiance, animateur sympa et ponctuel.", 2),
    ("Activité géniale pour toute la famille, enfants ravis.", 2),
    ("Prestation top, rapport qualité/prix excellent.", 2),
    ("Accueil chaleureux, tout était bien organisé.", 2),
    ("Incroyable expérience, je suis vraiment satisfait du service.", 2),
    ("Parfait ! Rien à redire, équipe formidable.", 2),
    ("Vraiment exceptionnel, à refaire sans hésiter.", 2),
    ("Guide compétent, sécurité respectée, ambiance au top.", 2),
    ("Activité bien structurée, on a passé un super moment.", 2),
    ("Très professionnel et agréable, je recommande chaudement.", 2),
    ("Bonne organisation, matériel neuf, encadrement sérieux.", 2),
    ("Merveilleux, le meilleur souvenir de notre séjour.", 2),
    ("Animateur passionné, explications claires, excellent.", 2),
    ("Expérience unique, accueil remarquable, on adore.", 2),
    ("Qualité au rendez-vous, personnel attentionné.", 2),
    ("Super bien organisé, tout s'est déroulé à merveille.", 2),
    ("Ponctualité exemplaire, guide fantastique, 5 étoiles !", 2),
    ("Activité originale et bien encadrée, très satisfait.", 2),
    ("Ambiance conviviale, prestation de qualité, bravo !", 2),
    ("Parfaitement organisé, matériel impeccable, super équipe.", 2),
    ("Génial du début à la fin, vaut vraiment le détour.", 2),
    ("Accueil professionnel, activité enrichissante et amusante.", 2),
    ("Très contente de cette expérience, personnel adorable.", 2),
    ("Organisation irréprochable, moment inoubliable.", 2),
    ("Super guide, très à l'écoute, expérience au top.", 2),
    ("Activité excellente, on se sent en sécurité, parfait.", 2),
    ("Nous avons adoré chaque instant, guide passionné et compétent.", 2),
    ("Tout était parfait, paysages magnifiques et équipe au top.", 2),
    ("Activité bien pensée, enfants et adultes ont adoré.", 2),
    ("Superbe moment en famille, on recommande sans hésiter.", 2),
    ("Guide très sympa, explications claires, expérience top.", 2),
    ("Activité fantastique, tout le monde est reparti avec le sourire.", 2),
    ("Très bien encadré, matériel de qualité, on reviendra.", 2),
    ("Journée parfaite, organisation impeccable, bravo à l'équipe.", 2),
    ("Excellente prestation, rapport qualité prix imbattable.", 2),
    ("Personnel adorable, activité très bien organisée.", 2),
    ("Moment magique, guide professionnel et très à l'écoute.", 2),
    ("Activité au top, sécurité respectée, ambiance fantastique.", 2),
    ("Très satisfait de cette expérience unique et bien encadrée.", 2),
    ("Incroyable journée, merci à toute l'équipe formidable.", 2),
    ("Parfait pour toute la famille, activité bien structurée.", 2),

    # ── NEUTRES ──
    ("Correct dans l'ensemble, sans plus.", 1),
    ("Activité convenable, rien d'exceptionnel mais sympa.", 1),
    ("Pas mal, quelques petits problèmes mais globalement ok.", 1),
    ("Expérience moyenne, peut mieux faire sur l'organisation.", 1),
    ("Honnêtement correct, correspond à la description.", 1),
    ("Guide sympathique mais activité un peu courte.", 1),
    ("Bien mais un peu cher pour ce que c'est.", 1),
    ("Satisfaisant sans être extraordinaire.", 1),
    ("Moyen, certaines parties étaient bien d'autres moins.", 1),
    ("Ça vaut le coup mais pas inoubliable non plus.", 1),
    ("Assez bien, quelques points à améliorer.", 1),
    ("Bof, ni vraiment bien ni vraiment mauvais.", 1),
    ("Correct, le guide était sympa mais le matériel vieillissant.", 1),
    ("Pas mal du tout, juste quelques petits bémols.", 1),
    ("Expérience correcte, on a passé un bon moment sans plus.", 1),
    ("Activité sympa mais sans grande originalité.", 1),
    ("Guide agréable mais l'activité manque un peu de contenu.", 1),
    ("Bien organisé mais le prix est un peu élevé.", 1),
    ("Correct, rien à signaler de particulier.", 1),
    ("Activité moyenne, certains aspects bons d'autres à revoir.", 1),
    ("Pas désagréable mais pas mémorable non plus.", 1),
    ("Quelques points positifs et quelques points négatifs.", 1),
    ("Dans l'ensemble acceptable, peut mieux faire.", 1),
    ("Activité honnête, guide sympa, mais rien d'exceptionnel.", 1),
    ("Expérience neutre, ni déçu ni enthousiaste.", 1),
    ("C'était bien sans être fantastique.", 1),
    ("Guide correct, activité un peu longue mais sympa.", 1),
    ("Bon rapport qualité prix mais manque de dynamisme.", 1),
    ("Satisfaisant globalement malgré quelques petits soucis.", 1),
    ("Activité correcte, on a passé un moment agréable sans plus.", 1),

    # ── NÉGATIFS ──
    ("Très déçu, organisation catastrophique et guide absent.", 0),
    ("Mauvaise expérience, retard de 2h sans explication.", 0),
    ("Activité annulée au dernier moment, inacceptable !", 0),
    ("Médiocre, matériel cassé, aucune sécurité respectée.", 0),
    ("À éviter absolument, personnel désorganisé et impoli.", 0),
    ("Décevant, rien ne correspond à la description.", 0),
    ("Problèmes à répétition, encadrement insuffisant.", 0),
    ("Trop cher pour une qualité aussi mauvaise.", 0),
    ("Guide incompétent, activité dangereuse, on est repartis.", 0),
    ("Expérience horrible, je ne recommande pas du tout.", 0),
    ("Désorganisé, bruyant, matériel défectueux, vraiment nul.", 0),
    ("Activité nulle, guide arrogant, arnaque totale.", 0),
    ("Très mauvais, rien ne fonctionne comme prévu.", 0),
    ("Zéro professionnalisme, retard énorme, très insatisfait.", 0),
    ("Déplorable, sécurité ignorée, je suis scandalisé.", 0),
    ("Manque de moyens évident, encadrement proche de zéro.", 0),
    ("Activité bâclée, guide peu motivé, on s'ennuie.", 0),
    ("Vraiment décevant, ne vaut pas le prix demandé.", 0),
    ("Annulation surprise le jour J, remboursement refusé.", 0),
    ("Expérience ratée, bruyant et mal organisé.", 0),
    ("Guide irrespectueux, activité dangereuse, à fuir.", 0),
    ("Terrible expérience, matériel dangereux et guide absent.", 0),
    ("Arnaque, rien ne correspond à ce qui était promis.", 0),
    ("Très mauvaise organisation, on a attendu 3h pour rien.", 0),
    ("Guide incompétent et impoli, activité à éviter.", 0),
    ("Déçu et en colère, remboursement impossible.", 0),
    ("Activité mal encadrée, enfants en danger, scandaleux.", 0),
    ("Pire expérience de ma vie, guide absent et matériel nul.", 0),
    ("Très insatisfait, promesses non tenues, arnaque.", 0),
    ("Mauvais accueil, activité ennuyeuse, prix injustifié.", 0),
]

# ─────────────────────────────────────────────
# 2. PRÉTRAITEMENT DU TEXTE
# ─────────────────────────────────────────────
def preprocess(text: str) -> str:
    """Nettoyage du texte : minuscules, suppression ponctuation excessive."""
    text = text.lower().strip()
    text = re.sub(r'[^\w\s\']', ' ', text)
    text = re.sub(r'\s+', ' ', text)
    return text

# ─────────────────────────────────────────────
# 3. PRÉPARATION DES DONNÉES
# ─────────────────────────────────────────────
texts  = [preprocess(t) for t, _ in DATASET]
labels = [l for _, l in DATASET]

X_train, X_test, y_train, y_test = train_test_split(
    texts, labels, test_size=0.2, random_state=42, stratify=labels
)

# ─────────────────────────────────────────────
# 4. PIPELINE ML
#    TF-IDF (trigrammes) + Régression Logistique
# ─────────────────────────────────────────────
pipeline = Pipeline([
    ('tfidf', TfidfVectorizer(
        ngram_range=(1, 3),      # unigrammes + bigrammes + trigrammes
        max_features=5000,
        min_df=1,
        sublinear_tf=True,       # log TF
        analyzer='word',
    )),
    ('clf', LogisticRegression(
        C=1.5,
        max_iter=1000,
        class_weight='balanced', # gère le déséquilibre de classes
        solver='lbfgs',
    )),
])

# ─────────────────────────────────────────────
# 5. ENTRAÎNEMENT
# ─────────────────────────────────────────────
print("🔄 Entraînement du modèle...")
pipeline.fit(X_train, y_train)

# ─────────────────────────────────────────────
# 6. ÉVALUATION
# ─────────────────────────────────────────────
y_pred = pipeline.predict(X_test)

print("\n📊 Rapport de classification :")
print(classification_report(
    y_test, y_pred,
    target_names=['Négatif', 'Neutre', 'Positif']
))

cv_scores = cross_val_score(pipeline, texts, labels, cv=5, scoring='accuracy')
print(f"✅ Accuracy moyenne (cross-validation 5-fold) : {cv_scores.mean():.2%} ± {cv_scores.std():.2%}")

print("\n🔢 Matrice de confusion :")
print(confusion_matrix(y_test, y_pred))

# ─────────────────────────────────────────────
# 7. SAUVEGARDE DU MODÈLE
# ─────────────────────────────────────────────
os.makedirs("model", exist_ok=True)
with open("model/sentiment_model.pkl", "wb") as f:
    pickle.dump(pipeline, f)

print("\n💾 Modèle sauvegardé dans model/sentiment_model.pkl")
print("✅ Entraînement terminé !")
