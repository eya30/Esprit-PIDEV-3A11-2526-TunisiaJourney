"""
predict.py
==========
Reçoit une liste d'avis (JSON) en argument, retourne l'analyse ML en JSON.
Appelé par Symfony via shell_exec / Process.

Usage :
    python predict.py '[{"note":5,"commentaire":"Super activité !"},...]'
"""

import sys, json, pickle, re, os

sys.stdout.reconfigure(encoding='utf-8')

MODEL_PATH = os.path.join(os.path.dirname(__file__), "model", "sentiment_model.pkl")

# ─────────────────────────────────────────────
# PRÉTRAITEMENT (identique à train_model.py)
# ─────────────────────────────────────────────
def preprocess(text: str) -> str:
    text = text.lower().strip()
    text = re.sub(r'[^\w\s\']', ' ', text)
    text = re.sub(r'\s+', ' ', text)
    return text

# ─────────────────────────────────────────────
# CHARGEMENT DU MODÈLE
# ─────────────────────────────────────────────
try:
    with open(MODEL_PATH, "rb") as f:
        model = pickle.load(f)
except FileNotFoundError:
    print(json.dumps({
        "success": False,
        "error": "Modèle introuvable. Lancez d'abord train_model.py"
    }))
    sys.exit(1)

# ─────────────────────────────────────────────
# LECTURE DES AVIS
# ─────────────────────────────────────────────
try:
    avis_list = json.loads(sys.argv[1])
except (IndexError, json.JSONDecodeError) as e:
    print(json.dumps({"success": False, "error": f"JSON invalide : {e}"}))
    sys.exit(1)

if not avis_list:
    print(json.dumps({
        "success": True,
        "has_avis": False,
        "summary": "Aucun avis pour le moment.",
        "sentiment": "neutral",
        "confidence": 0,
        "key_points": [],
        "stats": {"total": 0, "average": 0.0, "positive": 0, "negative": 0, "neutral": 0}
    }))
    sys.exit(0)

# ─────────────────────────────────────────────
# PRÉDICTION PAR AVIS
# ─────────────────────────────────────────────
LABELS = {0: "negative", 1: "neutral", 2: "positive"}
LABEL_FR = {0: "négatif", 1: "neutre", 2: "positif"}

results = []
total_note = 0
pos_count = neu_count = neg_count = 0

for avis in avis_list:
    note       = int(avis.get("note", 3))
    commentaire = avis.get("commentaire", "") or ""
    
    # Combine note + commentaire en texte pour le modèle
    # On préfixe avec la note pour aider le modèle
    note_text  = f"note {note} étoiles. " if note else ""
    full_text  = preprocess(note_text + commentaire)
    
    if full_text.strip():
        pred_label = int(model.predict([full_text])[0])
        proba      = model.predict_proba([full_text])[0]
        confidence = round(float(max(proba)) * 100, 1)
    else:
        # Fallback sur la note si pas de commentaire
        if note >= 4:
            pred_label = 2
        elif note <= 2:
            pred_label = 0
        else:
            pred_label = 1
        confidence = 70.0

    results.append({
        "note": note,
        "commentaire": commentaire,
        "sentiment": LABELS[pred_label],
        "sentiment_fr": LABEL_FR[pred_label],
        "confidence": confidence,
    })

    total_note += note
    if pred_label == 2:
        pos_count += 1
    elif pred_label == 0:
        neg_count += 1
    else:
        neu_count += 1

# ─────────────────────────────────────────────
# AGRÉGATION
# ─────────────────────────────────────────────
total   = len(results)
avg     = round(total_note / total, 1) if total else 0
pos_pct = round((pos_count / total) * 100) if total else 0
neg_pct = round((neg_count / total) * 100) if total else 0

# Sentiment global
if pos_pct >= 60:
    global_sentiment = "positive"
elif neg_pct >= 40:
    global_sentiment = "negative"
else:
    global_sentiment = "mixed"

# Score de confiance global (basé sur nb d'avis + moyenne des confidences)
avg_conf    = sum(r["confidence"] for r in results) / total if total else 0
size_bonus  = min(100, (total / 20) * 100)
confidence  = round((avg_conf * 0.7) + (size_bonus * 0.3))

# ─────────────────────────────────────────────
# POINTS CLÉS (ML-based : on cherche les avis
# très positifs/négatifs avec haute confiance)
# ─────────────────────────────────────────────
key_points = []

# Top commentaires positifs les plus confiants
top_pos = sorted(
    [r for r in results if r["sentiment"] == "positive"],
    key=lambda x: x["confidence"], reverse=True
)[:2]

# Top commentaires négatifs les plus confiants
top_neg = sorted(
    [r for r in results if r["sentiment"] == "negative"],
    key=lambda x: x["confidence"], reverse=True
)[:2]

for r in top_pos:
    if r["commentaire"]:
        key_points.append({
            "type": "positive",
            "text": r["commentaire"][:80] + ("..." if len(r["commentaire"]) > 80 else ""),
            "confidence": r["confidence"]
        })

for r in top_neg:
    if r["commentaire"]:
        key_points.append({
            "type": "negative",
            "text": r["commentaire"][:80] + ("..." if len(r["commentaire"]) > 80 else ""),
            "confidence": r["confidence"]
        })

# ─────────────────────────────────────────────
# GÉNÉRATION DU RÉSUMÉ TEXTUEL
# ─────────────────────────────────────────────
if global_sentiment == "positive":
    if avg >= 4.5:
        opening = " Excellente expérience !"
    elif avg >= 4.0:
        opening = " Très bonne expérience !"
    else:
        opening = " Les participants sont globalement satisfaits"
elif global_sentiment == "negative":
    opening = " Des points d'amélioration sont signalés"
else:
    opening = " Avis partagés"

summary = f"{opening} ({avg}/5 sur {total} avis)"
summary += f" — {pos_pct}% positifs, {neg_pct}% négatifs."

if avg >= 4.0:
    summary += "  Fortement recommandé !"
elif avg >= 3.0:
    summary += "  Recommandé avec quelques réserves."
elif avg >= 2.0:
    summary += "  À considérer selon vos attentes."
else:
    summary += "  À éviter selon les retours."

if confidence >= 70:
    summary += f" (analyse ML fiable, {total} avis)"
elif total < 5:
    summary += " (avis encore limités)"

# ─────────────────────────────────────────────
# SORTIE JSON
# ─────────────────────────────────────────────
output = {
    "success": True,
    "has_avis": True,
    "summary": summary,
    "sentiment": global_sentiment,
    "confidence": confidence,
    "key_points": key_points,
    "stats": {
        "total": total,
        "average": avg,
        "positive": pos_count,
        "negative": neg_count,
        "neutral": neu_count,
        "positive_percentage": pos_pct,
        "negative_percentage": neg_pct,
    },
    "details": results   # optionnel : détail par avis
}

print(json.dumps(output, ensure_ascii=False))
