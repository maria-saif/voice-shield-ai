import sys
import wave
import json
from vosk import Model, KaldiRecognizer
from pydub import AudioSegment
import os

file_name = sys.argv[1]
if "_ar" in file_name.lower():
    model_path = "../vosk_env/vosk-model-ar-mgb2-0.4"
elif "_en" in file_name.lower():
    model_path = "vosk-model-small-en-us-0.15"
else:
    print("❌ Could not detect language. Use '_ar' or '_en' in filename.")
    sys.exit(1)

model = Model(model_path)

audio = AudioSegment.from_file(file_name)
audio = audio.set_channels(1).set_frame_rate(16000)
temp_wav = "temp.wav"
audio.export(temp_wav, format="wav")
wav_path = temp_wav

try:
    wf = wave.open(wav_path, "rb")
except wave.Error:
    print("❌ Invalid WAV file. Conversion failed.")
    sys.exit(1)

if wf.getnchannels() != 1 or wf.getsampwidth() != 2 or wf.getcomptype() != "NONE":
    print("❌ WAV file must be mono, 16-bit PCM.")
    sys.exit(1)

rec = KaldiRecognizer(model, wf.getframerate())
rec.SetWords(True)
results = []

while True:
    data = wf.readframes(4000)
    if len(data) == 0:
        break
    if rec.AcceptWaveform(data):
        results.append(json.loads(rec.Result()))
results.append(json.loads(rec.FinalResult()))
wf.close()

os.remove(temp_wav)

full_text = " ".join([res.get("text", "") for res in results if res.get("text")]).strip()
print(full_text)
