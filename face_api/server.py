from flask import Flask, request, jsonify
import base64, tempfile, os, numpy as np
from deepface import DeepFace

app = Flask(__name__)
from flask_cors import CORS
CORS(app, resources={r"/*": {"origins": "*", "methods": ["GET", "POST", "OPTIONS"], "allow_headers": ["Content-Type"]}})

def base64_to_file(b64):
    if ',' in b64:
        b64 = b64.split(',')[1]
    data = base64.b64decode(b64)
    tmp = tempfile.NamedTemporaryFile(suffix='.jpg', delete=False)
    tmp.write(data)
    tmp.close()
    return tmp.name

def cosine_distance(v1, v2):
    v1, v2 = np.array(v1), np.array(v2)
    return 1 - np.dot(v1, v2) / (np.linalg.norm(v1) * np.linalg.norm(v2))

def get_image_path(data):
    if 'image_base64' in data:
        return base64_to_file(data['image_base64'])
    elif 'image_url' in data:
        return data['image_url']
    else:
        return None

# -------- EMBED --------
@app.route('/embed', methods=['POST'])
def embed():
    data = request.json
    img_path = None
    try:
        img_path = get_image_path(data)
        if not img_path:
            return jsonify({'error': 'No image provided'})
        result = DeepFace.represent(
            img_path=img_path,
            model_name="ArcFace",
            enforce_detection=False
        )
        return jsonify({'embedding': result[0]['embedding']})
    except Exception as e:
        return jsonify({'error': str(e)})
    finally:
        if img_path and os.path.exists(img_path) and not str(img_path).startswith("http"):
            os.unlink(img_path)

# -------- COMPARE --------
@app.route('/compare', methods=['POST'])
def compare():
    data = request.json
    img_path = None
    try:
        img_path = get_image_path(data)
        if not img_path:
            return jsonify({'error': 'No image provided'})
        result = DeepFace.represent(
            img_path=img_path,
            model_name="ArcFace",
            enforce_detection=False
        )
        embedding_login = result[0]['embedding']
        embedding_stored = data['embedding']
        distance = cosine_distance(embedding_login, embedding_stored)
        THRESHOLD = 0.35
        return jsonify({
            'match': bool(distance < THRESHOLD),
            'distance': round(float(distance), 4)
        })
    except Exception as e:
        return jsonify({'error': str(e)})
    finally:
        if img_path and os.path.exists(img_path) and not str(img_path).startswith("http"):
            os.unlink(img_path)

# -------- EMOTION --------
@app.route('/emotion', methods=['POST'])
def emotion():
    data = request.json
    img_path = None
    try:
        img_path = get_image_path(data)
        if not img_path:
            return jsonify({'error': 'No image provided'})

        result = DeepFace.analyze(
            img_path=img_path,
            actions=['emotion'],
            enforce_detection=False,
            detector_backend='opencv'
        )

        dominant = result[0]['dominant_emotion']
        print(f"🎭 Émotion: {dominant}")
        print(f"   Scores: { {k: round(float(v),1) for k,v in result[0]['emotion'].items()} }")

        messages = {
            'happy':    {'emoji': '😊', 'message': "Vous semblez de bonne humeur aujourd'hui !"},
            'sad':      {'emoji': '😢', 'message': 'Courage, bonne journée quand même !'},
            'angry':    {'emoji': '😠', 'message': 'Respirez, tout va bien se passer !'},
            'surprise': {'emoji': '😲', 'message': 'Quelque chose vous a surpris ?'},
            'fear':     {'emoji': '😨', 'message': "Pas d'inquiétude, vous êtes en sécurité !"},
            'disgust':  {'emoji': '😒', 'message': 'Bonne journée malgré tout !'},
            'neutral':  {'emoji': '😐', 'message': 'Bonne journée !'},
        }

        info = messages.get(dominant, {'emoji': '👋', 'message': 'Bienvenue !'})

        return jsonify({
            'emotion': dominant,
            'emoji':   info['emoji'],
            'message': info['message'],
        })

    except Exception as e:
        print(f"❌ Erreur emotion: {e}")
        return jsonify({'emotion': 'neutral', 'emoji': '😐', 'message': 'Bonne journée !'})

    finally:
        # ── Attendre que DeepFace ait fini avant de supprimer ──
        if img_path and isinstance(img_path, str) and not img_path.startswith("http"):
            try:
                os.unlink(img_path)
            except:
                pass  # fichier déjà supprimé ou encore utilisé

# -------- RUN --------
if __name__ == '__main__':
    app.run(port=5001, debug=False)