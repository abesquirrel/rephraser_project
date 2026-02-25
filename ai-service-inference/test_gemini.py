import os
from google import genai
from google.genai import types

client = genai.Client(api_key=os.environ.get("GEMINI_API_KEY"))

model = "gemini-2.5-flash"
system = "You are Customer Support. PLAIN TEXT ONLY.\n\n### PROTOCOL\n1. Audience: END USER.\n2. Focus: Reassure the customer.\n3. Format: Standard professional letter.\n\n### MODE: WEB VERIFICATION\nUse 'Web Search Context' to validate claims.\n\n### REQUIRED OUTPUT FORMAT\nHello,\n\n(Rephrased content politely explaining the situation and resolution)\n\nRegards,\nCustomer Support\n\nCRITICAL: The output must start exactly with 'Hello,'. Do not include any preamble."

user = "Notes:\nThis issue was fixed already, an overaggressive rule at the Cloudflare protection rules was causing this issue\n\nWeb Search Context:\nTitle: some title\nSnippet: some snippet\nURL: http://example.com"

response = client.models.generate_content_stream(
    model=model,
    contents=user,
    config=types.GenerateContentConfig(
        system_instruction=system,
        temperature=0.4,
        max_output_tokens=300
    )
)

for chunk in response:
    print(repr(chunk.text))
    if chunk.candidates:
        print("Finish reason:", chunk.candidates[0].finish_reason)
