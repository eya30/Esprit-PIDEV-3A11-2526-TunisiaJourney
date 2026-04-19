import sys
import json
import base64
import tempfile
import os
import numpy as np
from deepface import DeepFace

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

def main():
    input_data = json.loads(sys.stdin.read())
    img_path = None

    try:
        img_path = base64_to_file(input_data['image_base64'])

        result = DeepFace.represent(
            img_path=img_path,
            model_name="ArcFace",
            enforce_detection=False
        )

        embedding_login  = result[0]['embedding']
        embedding_stored = input_data['embedding']

        distance = cosine_distance(embedding_login, embedding_stored)
        THRESHOLD = 0.40

        print(json.dumps({
            'match':     bool(distance < THRESHOLD),
            'distance':  round(float(distance), 4),
            'threshold': THRESHOLD
        }))

    except Exception as e:
        print(json.dumps({'error': str(e)}))

    finally:
        if img_path and os.path.exists(img_path):
            os.unlink(img_path)

main()