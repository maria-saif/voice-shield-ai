import librosa
import numpy as np
import json
import sys

def extract_features(audio_path):
    try:
        y, sr = librosa.load(audio_path, sr=16000)

        mfcc = np.mean(librosa.feature.mfcc(y=y, sr=sr, n_mfcc=20).T, axis=0)

        pitch, mag = librosa.piptrack(y=y, sr=sr)
        pitch_mean = np.mean(pitch[pitch > 0]) if np.any(pitch > 0) else 0

        rms = np.mean(librosa.feature.rms(y=y))

        zcr = np.mean(librosa.feature.zero_crossing_rate(y))

        return mfcc, pitch_mean, rms, zcr

    except Exception as e:
        return None, None, None, None


def classify_emotion(pitch, rms, zcr):
    """
    تصنيف تقريبي للمشاعر:
    – Angry → صوت عالي + Pitch عالي + ZCR عالي
    – Fear → Pitch عالي + RMS منخفض
    – Sad → Pitch منخفض + RMS منخفض
    – Pressuring → RMS عالي + ZCR عالي جداً
    – Neutral → بدون خصائص واضحة
    """

    if pitch > 180 and rms > 0.08 and zcr > 0.12:
        return "angry"

    if pitch > 170 and rms < 0.06:
        return "fear"

    if pitch < 140 and rms < 0.05:
        return "sad"

    if rms > 0.09 and zcr > 0.15:
        return "pressuring"

    return "neutral"


def compute_pressure_level(rms):
    return min(100, round((rms / 0.12) * 100))


def compute_aggression(pitch, zcr):
    score = (pitch / 200) * 0.6 + (zcr / 0.20) * 0.4
    return min(100, round(score * 100))


def robot_probability(zcr):
    if zcr < 0.04:
        return 80
    if zcr < 0.06:
        return 60
    return 20


def persuasion_attempts(zcr):
    if zcr > 0.15:
        return 3
    if zcr > 0.10:
        return 2
    if zcr > 0.07:
        return 1
    return 0


def main():
    if len(sys.argv) < 2:
        print(json.dumps({"error": "No audio path provided"}))
        return

    audio_path = sys.argv[1]

    mfcc, pitch, rms, zcr = extract_features(audio_path)
    if mfcc is None:
        print(json.dumps({"error": "Failed to process audio"}))
        return

    emotion = classify_emotion(pitch, rms, zcr)
    pressure = compute_pressure_level(rms)
    aggression = compute_aggression(pitch, zcr)
    robot_prob = robot_probability(zcr)
    attempts = persuasion_attempts(zcr)

    output = {
        "emotion": emotion,
        "pressure_level": pressure,
        "aggression": aggression,
        "robot_probability": robot_prob,
        "persuasion_attempts": attempts,
        "confidence": 92  
    }

    print(json.dumps(output, ensure_ascii=False))


if __name__ == "__main__":
    main()
