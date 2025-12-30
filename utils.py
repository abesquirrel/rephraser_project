import pandas as pd

def load_and_process_file(uploaded_file):
    """
    Loads and processes an uploaded file (CSV or TXT) into a list of texts.

    Args:
        uploaded_file: The file uploaded via st.file_uploader.

    Returns:
        A list of strings if successful, otherwise None.
    """
    df = None
    if uploaded_file.name.endswith('.csv'):
        df = pd.read_csv(uploaded_file)
    elif uploaded_file.name.endswith('.txt'):
        content = uploaded_file.read().decode("utf-8")
        df = pd.DataFrame([{"text": content}])

    if df is not None and "text" in df.columns:
        return df["text"].tolist()
    return None

