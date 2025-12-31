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
# If the last run resulted in a successful generation, clear the input text area.
# This must be done before the widget is rendered.
if st.session_state.generation_complete:
    st.session_state.text_input = ""
    st.session_state.generation_complete = False

# --- HELPER FUNCTIONS (UI-specific) ---

def run_generation(text_input, signature_input, enable_web_search, show_thinking):
    """Triggers the backend to generate a response and handles the streaming display."""
    st.session_state.thinking_log = []
    thinking_placeholder = st.empty()
    
    try:
        payload = {'text': text_input, 'signature': signature_input, 'enable_web_search': enable_web_search}
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
                                'approved': None
                            })
                            st.session_state.thinking_log = []
                            thinking_placeholder.empty()
                            # Set flag to clear input on next run
                            st.session_state.generation_complete = True
                            st.rerun()
                        elif "error" in event:
                            st.error(f"Backend error: {event['error']}")
                            break
                    except json.JSONDecodeError:
                        st.warning("Received an invalid message from the backend.")
                        continue
                        
    except requests.exceptions.RequestException as e:
        st.error(f"Connection to backend failed. Is it running? Error: {e}")
    except Exception as e:
        st.error(f"An unexpected error occurred: {e}")

def handle_regenerate(original_text):
    """Callback to re-trigger the generation for a previous entry."""
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

st.title("📝 Paul's Rephraser")
st.write("I'll help you structure and clarify your notes into a professional response. I can optionally search the web for extra context, and you can rate my answers to help me improve.")



# Card: Your Input
with st.container(border=True):
    st.markdown("### ✍️ Your Input")
    with st.form(key="rephrase_form"):
        col1, col2 = st.columns(2)
        with col1:
            text_input = st.text_area("Your Notes:", height=150, key="text_input", placeholder="e.g., customer wants refund, product defective, processed via order #123")
        with col2:
            signature_input = st.text_input("Signature:", value="Paul", key="signature_input")

        col_checkbox1, col_checkbox2 = st.columns(2)
        with col_checkbox1:
            show_thinking = st.checkbox("Show thinking process", value=True, key="show_thinking")
        with col_checkbox2:
            enable_web_search = st.checkbox("Enable online research", value=True, help="Allow the AI to search the web for additional context.", key="enable_web_search")
        
        submit_button = st.form_submit_button("✨ Generate Response", use_container_width=True)

# --- RESPONSE LOGIC ---
# This block needs to be outside the columns, but still before the history card
if (submit_button or st.session_state.get('regenerate', False)) and st.session_state.text_input.strip():
    # Reset regenerate flag
    if st.session_state.get('regenerate', False):
        st.session_state.regenerate = False
    
    # Access checkbox and signature values from session state, which are now keyed
    show_thinking_val = st.session_state.get('show_thinking', True)
    enable_web_search_val = st.session_state.get('enable_web_search', True)
    signature_val = st.session_state.get('signature_input', 'Paul')
    text_input_val = st.session_state.text_input

    run_generation(text_input_val, signature_val, enable_web_search_val, show_thinking_val)


st.markdown("---") # Separator between sections

# Card: Review Responses (full width)
with st.container(border=True):
    st.markdown("### 📋 Review Responses")
    if not st.session_state.history:
        st.info("Your generated responses will appear here.")

    for i, item in enumerate(st.session_state.history):
        # Defensive check for history item structure
        if isinstance(item, dict) and 'original' in item and 'rephrased' in item:
            with st.container():
                original_text = str(item.get('original') or '')
                rephrased_text = str(item.get('rephrased') or '')

                st.markdown(f'<div class="response-card">', unsafe_allow_html=True)

                st.markdown(f"**Original Notes:**")
                st.markdown(f"> {original_text}")

                st.markdown(f"**Generated Response:**")
                st.markdown(rephrased_text)

                # --- ACTION BUTTONS ---
                st.write("") # Spacer
                cols = st.columns([1, 1, 1, 3])

                # 1. Copy Button
                cols[0].button("📋 Copy", key=f"copy_{i}", on_click=lambda t=rephrased_text: pyperclip.copy(t), use_container_width=True)

                # 2. Approve Button
                if item.get('approved') is not True:
                    cols[1].button("👍 Yes", key=f"approve_{i}", on_click=handle_approve, args=(original_text, rephrased_text, i), help="Was this response helpful?", use_container_width=True)
                else:
                    cols[1].success("Approved!", icon="✅")

                # 3. Regenerate/Disapprove Button
                if item.get('approved') is not True: # Don't show if already approved
                    cols[2].button("👎 No", key=f"regen_{i}", on_click=handle_regenerate, args=(original_text,), help="Regenerate the response for these notes.", use_container_width=True)

                st.markdown(f'</div>', unsafe_allow_html=True)
        else:
            st.warning(f"Skipping malformed history item at index {i}.")

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