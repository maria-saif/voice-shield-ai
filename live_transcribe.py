import sys
import os
import warnings

warnings.filterwarnings("ignore")
os.environ["TOKENIZERS_PARALLELISM"] = "false"

try:
    from faster_whisper import WhisperModel
except Exception:
    print("")  
    sys.exit(0)


if len(sys.argv) < 2:
    print("")
    sys.exit(0)

audio_path = sys.argv[1].strip().strip('"').strip("'")

if not os.path.exists(audio_path):
    print("")
    sys.exit(0)


MODEL_SIZE = "tiny"  
DEVICE = "cpu"
COMPUTE = "int8"      

model = WhisperModel(MODEL_SIZE, device=DEVICE, compute_type=COMPUTE)


try:
    segments, info = model.transcribe(
        audio_path,
        language="ar",      
        task="transcribe",
        beam_size=1,        
        vad_filter=True      
    )
except Exception:
    print("")
    sys.exit(0)


text_parts = []
for seg in segments:
    t = (seg.text or "").strip()
    if t:
        text_parts.append(t)

print(" ".join(text_parts).strip())
