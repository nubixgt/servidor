import subprocess
import sys
import threading
import time
import webbrowser


def check_and_install_dependencies():
    """
    Checks if fastapi and uvicorn are installed.
    Installs them using pip if they are missing.
    """
    dependencies = ["fastapi", "uvicorn"]
    missing = []
    
    for dep in dependencies:
        try:
            __import__(dep)
        except ImportError:
            missing.append(dep)
            
    if missing:
        print(f"Missing web app dependencies: {', '.join(missing)}. Installing them now...")
        try:
            # Run pip install safely within the current python context
            subprocess.check_call([sys.executable, "-m", "pip", "install"] + missing)
            print("Successfully installed dependencies!")
        except Exception as e:
            print(f"Error installing dependencies: {e}", file=sys.stderr)
            print("Please run: pip install fastapi uvicorn", file=sys.stderr)

def open_browser():
    """
    Waits briefly for the server to spin up, then opens the browser.
    """
    time.sleep(2.0)
    url = "http://127.0.0.1:8000"
    print("\n=======================================================")
    print(f"  AI Coach Web Server Running at: {url}")
    print("  Opening web browser automatically...")
    print("=======================================================\n")
    try:
        webbrowser.open(url)
    except Exception as e:
        print(f"Could not open browser automatically: {e}")

if __name__ == "__main__":
    # Ensure dependencies are met
    check_and_install_dependencies()
    
    # Import uvicorn locally after checking installation
    import uvicorn
    
    # Launch browser thread
    threading.Thread(target=open_browser, daemon=True).start()
    
    # Start the FastAPI server
    try:
        uvicorn.run("web_app.server:app", host="127.0.0.1", port=8000, reload=False)
    except KeyboardInterrupt:
        print("\nWeb server stopped by user.")
    except Exception as e:
        print(f"Error starting web server: {e}", file=sys.stderr)
