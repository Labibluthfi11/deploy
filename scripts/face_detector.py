import json
import sys

import cv2


def has_face(image_path):
    image = cv2.imread(image_path, cv2.IMREAD_GRAYSCALE)
    if image is None:
        raise ValueError("The uploaded image could not be decoded.")

    height, width = image.shape
    longest_side = max(height, width)
    if longest_side > 1600:
        scale = 1600 / longest_side
        image = cv2.resize(
            image,
            (round(width * scale), round(height * scale)),
            interpolation=cv2.INTER_AREA,
        )

    cascade_dir = cv2.data.haarcascades
    frontal = cv2.CascadeClassifier(
        cascade_dir + "haarcascade_frontalface_default.xml"
    )
    profile = cv2.CascadeClassifier(cascade_dir + "haarcascade_profileface.xml")

    for detector in (frontal, profile):
        faces = detector.detectMultiScale(
            image,
            scaleFactor=1.1,
            minNeighbors=5,
            minSize=(40, 40),
        )
        if len(faces) > 0:
            return True

        mirrored_faces = detector.detectMultiScale(
            cv2.flip(image, 1),
            scaleFactor=1.1,
            minNeighbors=5,
            minSize=(40, 40),
        )
        if len(mirrored_faces) > 0:
            return True

    return False


def main():
    if len(sys.argv) != 2:
        raise ValueError("Expected one image path.")

    print(json.dumps({"face_detected": has_face(sys.argv[1])}))


if __name__ == "__main__":
    try:
        main()
    except (OSError, ValueError, cv2.error) as error:
        print(str(error), file=sys.stderr)
        sys.exit(1)
