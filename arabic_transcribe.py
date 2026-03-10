from vosk import Model, KaldiRecognizer
import sys
import os
import wave
import json

if len(sys.argv) < 2:
    print("❌ Error: Audio file name not provided.")
    sys.exit(1)

file_name = sys.argv[1]  

file_name_lower = file_name.lower()
if "arabic" in file_name_lower or "_ar" in file_name_lower:
    model_path = "../vosk_env/vosk-model-ar-mgb2-0.4"
elif "english" in file_name_lower or "_en" in file_name_lower:
    model_path = "vosk-model-small-en-us-0.15"
else:
    print("❌ Could not detect language. Please include 'arabic' or 'english' or '_ar' / '_en' in the filename.")
    sys.exit(1)

if not os.path.exists(model_path):
    print(f"❌ Model path '{model_path}' not found.")
    sys.exit(1)

model = Model(model_path)

if not os.path.exists(file_name):
    print(f"❌ File '{file_name}' not found.")
    sys.exit(1)

wf = wave.open(file_name, "rb")
if wf.getnchannels() != 1 or wf.getsampwidth() != 2 or wf.getcomptype() != "NONE":
    print("⚠️ The audio file must be WAV format: mono, 16-bit PCM.")
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

full_text = " ".join([res.get("text", "") for res in results if res.get("text")]).strip()
lower_text = full_text.lower()

suspicious_phrases = [
    "تأكيد الحساب", "كلمة المرور", "تم إيقاف حسابك", "قم بالدفع الآن", "معلومات شخصية",
    "رقم البطاقة", "أدخل رمز التحقق", "bank", "verify", "urgent", "transfer", "password"
]

found_items = [phrase for phrase in suspicious_phrases if phrase in lower_text]

word_count = max(1, len(lower_text.split()))
risk_percent = (len(found_items) / word_count) * 100
risk_level = "🔴 High" if risk_percent > 5 else ("🟠 Medium" if risk_percent > 2 else "🟢 Low")

with open("transcript.txt", "w", encoding="utf-8") as f:
    f.write(full_text)

output = {
    "transcript": full_text,
    "found_keywords": found_items,
    "risk_level": risk_level,
    "risk_percent": round(risk_percent, 2)
}
print(json.dumps(output, ensure_ascii=False))
