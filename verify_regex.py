import re

def extract_keywords_mock(text):
    # Brand and Model detection: common mobile brands and Alpha-numeric model codes
    brands = re.findall(r'\b(Apple|Samsung|Google|Pixel|iPhone|Galaxy|Motorola|Nokia|Xiaomi|Oppo|Vivo|Realme|OnePlus|Huawei|LG|Sony|ZTE|Alcatel|Asus|TCL)\b', text, re.IGNORECASE)
    model_codes = re.findall(r'\bSM-[A-Z0-9]{4,10}\b|\bSM[A-Z0-9]{4,10}\b|\b[A-Z]{1,2}-[A-Z0-9]{2,10}\b', text, re.IGNORECASE)

    technical_hits = brands + model_codes
    return " ".join(technical_hits[:5])

test_cases = [
    "Samsung Galaxy SM-S911B roaming issue",
    "iPhone 14 Pro Max connectivity",
    "Google Pixel 7a eSIM",
    "Motorola Edge 30 5G",
    "Got a SM-G998U device here",
    "Device model is SMG991U",
    "Using a TCL 30 XE"
]

for tc in test_cases:
    print(f"Input: {tc}")
    print(f"Keywords: {extract_keywords_mock(tc)}")
    print("-" * 20)
