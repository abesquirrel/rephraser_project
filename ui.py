import streamlit as st
import requests
import threading
import time
import json
import pyperclip
import socket
from app import run_app

# --- HELPER FUNCTIONS ---

def find_free_port():
    """Finds a free port on the local machine."""
    with socket.socket(socket.AF_INET, socket.SOCK_STREAM) as s:
        s.bind(('', 0))
        return s.getsockname()[1]

# --- CONFIGURATION & BACKEND STARTUP ---

if 'backend_port' not in st.session_state:
    st.session_state['backend_port'] = find_free_port()

if 'backend_started' not in st.session_state:
    port = st.session_state['backend_port']
    threading.Thread(target=run_app, args=(port,), daemon=True).start()
    st.session_state['backend_started'] = True
    time.sleep(3) # Give backend a moment to start

# Define API URLs using the dynamic port
port = st.session_state['backend_port']
FLASK_API_URL_REPHRASE = f"http://localhost:{port}/rephrase"
FLASK_API_URL_APPROVE = f"http://localhost:{port}/approve"
FLASK_API_URL_UPLOAD_KB = f"http://localhost:{port}/upload_kb"

# --- SESSION STATE INITIALIZATION ---
if 'history' not in st.session_state:
    st.session_state.history = []
if 'thinking_log' not in st.session_state:
    st.session_state.thinking_log = []
if 'regenerate' not in st.session_state:
    st.session_state.regenerate = False
# Initialize values for single entry input fields
if 'original_text_single_input_value' not in st.session_state:
    st.session_state.original_text_single_input_value = ""
if 'rephrased_text_single_input_value' not in st.session_state:
    st.session_state.rephrased_text_single_input_value = ""
if 'generation_complete' not in st.session_state:
    st.session_state.generation_complete = False

# --- SCRIPT RUN START ---
# We no longer clear the input text area after generation to allow the user to iterate.

# --- HELPER FUNCTIONS (UI-specific) ---

def run_generation(text_input, signature_input, enable_web_search, show_thinking, search_keywords, template_mode):
    """Triggers the backend to generate a response and handles the streaming display."""
    st.session_state.thinking_log = []
    thinking_placeholder = st.empty()
    
    try:
        payload = {
            'text': text_input,
            'signature': signature_input,
            'enable_web_search': enable_web_search,
            'search_keywords': search_keywords,
            'template_mode': template_mode
        }
        with requests.post(FLASK_API_URL_REPHRASE, json=payload, stream=True) as response:
            response.raise_for_status()
            for line in response.iter_lines():
                if line:
                    try:
                        event = json.loads(line.decode('utf-8'))
                        if "status" in event:
                            st.session_state.thinking_log.append(f"- {event['status']}")
                            if show_thinking:
                                thinking_placeholder.info("\n".join(st.session_state.thinking_log))
                        elif "data" in event:
                            final_response = event['data']
                            st.session_state.history.insert(0, {
                                'original': text_input,
                                'rephrased': final_response,
                                'approved': False
                            })
                            st.session_state.thinking_log = []
                            thinking_placeholder.empty()
                            st.rerun()
                        elif "error" in event:
                            st.error(f"Backend error: {event['error']}")
                            break
                    except json.JSONDecodeError:
                        continue
                        
    except requests.exceptions.RequestException as e:
        st.error(f"Frontend: Connection to backend failed. Is it running? Error: {e}")
    except Exception as e:
        st.error(f"Frontend: An unexpected error occurred: {e}")

def handle_regenerate(original_text):
    """Callback to re-trigger the generation for a previous entry."""
    # We update the session state directly. 
    # This function will be called as a callback before the widget is re-instantiated.
    st.session_state.text_input = original_text
    st.session_state.regenerate = True

def handle_approve(original, rephrased, item_index):
    """Callback to approve a response and save it."""
    try:
        payload = {'original_text': original, 'rephrased_text': rephrased}
        response = requests.post(FLASK_API_URL_APPROVE, json=payload)
        response.raise_for_status()
        st.toast("✅ Approved! The AI will learn from this.", icon="👍")
        st.session_state.history[item_index]['approved'] = True
    except Exception as e:
        st.error(f"Error saving approval: {e}")

# --- STREAMLIT UI ---
st.set_page_config(layout="centered", page_title="Paul: The Rephraser", initial_sidebar_state="collapsed")

# (The rest of the file is the same as the previous version)

# Custom CSS for a cleaner look that adapts to Streamlit's theme
st.markdown("""
<style>
    .stApp {
        background-color: var(--background-color);
    }
    /* Reduce top padding */
    .main .block-container {
        padding-top: 2rem;
    }
    .stTextArea, .stButton>button {
        border-radius: 0.5rem;
    }
    .response-card {
        background-color: var(--secondary-background-color);
        color: var(--text-color);
        padding: 1.5rem;
        border-radius: 0.5rem;
        border: 1px solid var(--secondary-background-color);
        margin-bottom: 1rem;
    }
    .response-card h3 {
        margin-top: 0;
        color: var(--text-color);
    }
    .response-card blockquote {
        background-color: var(--background-color);
        border-left: 5px solid #ccc;
        margin: 1rem 0;
        padding: 0.5rem 0.5rem 0.5rem 1rem;
        color: var(--text-color);
    }
    /* Action buttons in columns */
    .stButton>button {
        width: 100%;
    }
</style>
""", unsafe_allow_html=True)

# Card: Your Input
with st.container(border=True):
    st.markdown("### ✍️ Your Input")
    col1, col2 = st.columns(2)
    with col1:
        text_input = st.text_area("Your Notes:", height=150, key="text_input", placeholder="e.g., customer wants refund, product defective, processed via order #123")
    with col2:
        signature_input = st.text_input("Signature:", value="Paul", key="signature_input")

    col_checkbox1, col_checkbox2 = st.columns(2)
    with col_checkbox1:
        show_thinking = st.checkbox("Show thinking process", value=True, key="show_thinking")
    with col_checkbox2:
        # Initialize session state keys if they don't exist
        if 'template_mode' not in st.session_state:
            st.session_state.template_mode = False
        if 'enable_web_search' not in st.session_state:
            st.session_state.enable_web_search = True # Default to True as before

        # Checkboxes for user interaction
        template_mode_new = st.checkbox("Rephrase with Template", value=st.session_state.template_mode, key="template_mode_checkbox")
        enable_web_search_new = st.checkbox("Enable online research", value=st.session_state.enable_web_search, help="Allow the AI to search the web for additional context.", key="enable_web_search_checkbox")

        # Mutual exclusivity logic
        if template_mode_new != st.session_state.template_mode: # If template_mode_checkbox was just changed
            st.session_state.template_mode = template_mode_new
            if st.session_state.template_mode:
                st.rerun()
        
        if enable_web_search_new != st.session_state.enable_web_search: # If enable_web_search_checkbox was just changed
            st.session_state.enable_web_search = enable_web_search_new
            if st.session_state.enable_web_search:
                st.session_state.template_mode = False
                # Force rerun to update the other checkbox immediately
                st.rerun()

    # Get the final states from session_state for logic downstream
    template_mode = st.session_state.template_mode
    enable_web_search = st.session_state.enable_web_search

    search_keywords = "" # Initialize search_keywords
    if enable_web_search:
        search_keywords = st.text_input("Optional: Specify search keywords (comma-separated)", key="search_keywords_input", placeholder="e.g., cell phone plans, T-Mobile, rural coverage")
    
    submit_button = st.button("✨ Generate Response", use_container_width=True)

# --- RESPONSE LOGIC ---
if (submit_button or st.session_state.get('regenerate', False)) and st.session_state.text_input.strip():
    # Reset regenerate flag
    if st.session_state.get('regenerate', False):
        st.session_state.regenerate = False
    
    # Access checkbox and signature values from session state, which are now keyed
    show_thinking_val = st.session_state.get('show_thinking', True)
    # Use the 'template_mode' and 'enable_web_search' from the updated session state
    enable_web_search_val = st.session_state.enable_web_search
    template_mode_val = st.session_state.template_mode
    signature_val = st.session_state.get('signature_input', 'Paul')
    text_input_val = st.session_state.text_input
    
    # Ensure search_keywords_val is correctly retrieved based on enable_web_search_val
    search_keywords_val = st.session_state.get('search_keywords_input', '') if enable_web_search_val else ''

    run_generation(text_input_val, signature_val, enable_web_search_val, show_thinking_val, search_keywords_val, template_mode_val)

# --- DISPLAY HISTORY ---
for i, item in enumerate(st.session_state.history):
    with st.container(border=True):
        st.markdown(f"### 📋 Response {len(st.session_state.history) - i}")
        
        # Two columns for Original and Rephrased
        col_orig, col_reph = st.columns(2)
        with col_orig:
            st.markdown("**Original Notes:**")
            st.info(item['original'])
        with col_reph:
            st.markdown("**Rephrased Output:**")
            st.success(item['rephrased'])
        
        # Action buttons
        col_btn1, col_btn2, col_btn3 = st.columns(3)
        with col_btn1:
            if st.button("📋 Copy to Clipboard", key=f"copy_{i}", use_container_width=True):
                pyperclip.copy(item['rephrased'])
                st.toast("Copied to clipboard!", icon="✅")
        with col_btn2:
            st.button("🔄 Regenerate", key=f"regen_{i}", use_container_width=True, on_click=handle_regenerate, args=(item['original'],))
        with col_btn3:
            is_approved = bool(item.get('approved', False))
            btn_label = "✅ Approved" if is_approved else "👍 Approve & Learn"
            if st.button(btn_label, key=f"approve_{i}", disabled=is_approved, use_container_width=True):
                handle_approve(item['original'], item['rephrased'], i)
                st.rerun()

st.markdown("---") # Separator between sections
# Card: Knowledge Base Management (Collapsible)
with st.expander("📚 Manage Knowledge Base"):
    with st.container(border=True):
        st.markdown("### Knowledge Base Management")
        st.write("Manage your knowledge base by uploading CSV files or adding single entries.")

        st.subheader("Upload Knowledge Base (CSV)")
        st.write("Upload a CSV file to expand the knowledge base. The CSV should contain two columns: `original_text` and `rephrased_text`.")
        uploaded_file = st.file_uploader("Choose a CSV file", type="csv", key="kb_uploader")
        upload_button = st.button("Add CSV to Knowledge Base", key="kb_upload_button")

        if uploaded_file and upload_button:
            try:
                # Read the uploaded file content as bytes, then decode
                file_content = uploaded_file.getvalue().decode("utf-8")
                
                # Send to backend
                response = requests.post(FLASK_API_URL_UPLOAD_KB, files={'file': (uploaded_file.name, file_content, 'text/csv')})
                response.raise_for_status()
                
                result = response.json()
                if result.get("status") == "success":
                    st.success("✅ Knowledge base updated successfully! The system is now re-indexing. This may take a moment.")
                    st.toast("Knowledge base updated and re-indexing!", icon="📚")
                else:
                    st.error(f"Error updating knowledge base: {result.get('error', 'Unknown error')}")
                    
            except requests.exceptions.RequestException as e:
                st.error(f"Connection to backend failed during upload. Is it running? Error: {e}")
            except Exception as e:
                st.error(f"An unexpected error occurred during upload: {e}")

        st.subheader("Add Single Knowledge Entry")
        st.write("Manually add a single pair of original and rephrased text to the knowledge base.")

        with st.form(key="single_kb_entry_form"):
            original_text_single = st.text_area("Original Text:", height=75, key="original_text_single", value=st.session_state.original_text_single_input_value, placeholder="e.g., The customer's main concern was a bug in the new feature.")
            rephrased_text_single = st.text_area("Rephrased Text:", height=75, key="rephrased_text_single", value=st.session_state.rephrased_text_single_input_value, placeholder="e.g., The customer reported a defect impacting the functionality of the recently released feature.")
            add_single_entry_button = st.form_submit_button("Add Single Entry", use_container_width=True, help="Add this pair to the knowledge base and trigger re-indexing.")

        if add_single_entry_button and (original_text_single != st.session_state.original_text_single_input_value or rephrased_text_single != st.session_state.rephrased_text_single_input_value) and original_text_single.strip() and rephrased_text_single.strip():
            try:
                payload = {
                    'original_text': original_text_single.strip(),
                    'rephrased_text': rephrased_text_single.strip()
                }
                response = requests.post(FLASK_API_URL_UPLOAD_KB, json=payload)
                response.raise_for_status()
                
                result = response.json()
                if result.get("status") == "success":
                    st.success("✅ Single entry added! The system is now re-indexing.")
                    st.toast("Entry added and re-indexing!", icon="📚")
                    # Clear input fields after successful submission
                    st.session_state.original_text_single_input_value = ""
                    st.session_state.rephrased_text_single_input_value = ""
                    st.rerun() # Rerun to clear inputs and refresh
                else:
                    st.error(f"Error adding single entry: {result.get('error', 'Unknown error')}")
                    
            except requests.exceptions.RequestException as e:
                st.error(f"Connection to backend failed during single entry upload. Is it running? Error: {e}")
            except Exception as e:
                st.error(f"An unexpected error occurred during single entry upload: {e}")