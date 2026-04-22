import sys
import json
import base64
import tempfile
import os
from deepface import DeepFace

def base64_to_file(b64):
    if ',' in b64:
        b64 = b64.split(',')[1]
    data = base64.b64decode(b64)
    tmp = tempfile.NamedTemporaryFile(suffix='.jpg', delete=False)
    tmp.write(data)
    tmp.close()
    return tmp.name

def main():
    input_data = json.loads(sys.stdin.read())
    img_path = None

    try:
        if 'image_base64' in input_data:
            img_path = base64_to_file(input_data['image_base64'])
        elif 'image_url' in input_data:
            img_path = input_data['image_url']
        else:
            print(json.dumps({'error': 'image_base64 ou image_url requis'}))
            return

        result = DeepFace.represent(
            img_path=img_path,
            model_name="ArcFace",
            enforce_detection=False
        )

        print(json.dumps({'embedding': result[0]['embedding']}))

    except Exception as e:
        print(json.dumps({'error': str(e)}))

    finally:
        if img_path and os.path.exists(img_path):
            os.unlink(img_path)

main()