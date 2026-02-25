import os
import json
from google import genai
from google.genai import types

with open("/app/last_payload.json", "r") as f:
    data = json.load(f)

client = genai.Client(api_key=os.environ.get("GEMINI_API_KEY"))

model = "gemini-2.5-flash"
system = data["system_instruction"]
user = data["contents"]

print(f"Testing with max_output_tokens=255...")
response = client.models.generate_content_stream(
    model=model,
    contents=user,
    config=types.GenerateContentConfig(
        system_instruction=system,
        temperature=0.4,
        max_output_tokens=255
    )
)

total_tokens = 0
for chunk in response:
    print(repr(chunk.text))
    if chunk.candidates:
        print("Finish reason:", chunk.candidates[0].finish_reason)
    if chunk.usage_metadata:
        total_tokens = getattr(chunk.usage_metadata, 'candidates_token_count', 0)

print("Final Output Tokens:", total_tokens)
