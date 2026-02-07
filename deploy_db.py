import ftplib
import os

# FTP Details
FTP_HOST = "ftp.bramex.co.tz"
FTP_USER = "quizzify@quizzify.bramex.co.tz"
FTP_PASS = "Bib@2012aa++"

# File to upload
LOCAL_FILE = r"c:\xampp\htdocs\quizbooth\api\get_questions.php"
REMOTE_DIR = "api"
REMOTE_FILE = "get_questions.php"

def upload_file():
    try:
        # Connect
        print(f"Connecting to {FTP_HOST}...")
        ftp = ftplib.FTP(FTP_HOST)
        ftp.login(FTP_USER, FTP_PASS)
        print(f"Logged in as {FTP_USER}")

        # Check current directory
        print(f"Current Directory: {ftp.pwd()}")
        
        # List files to understand structure
        print("Listing files in root:")
        ftp.dir()

        # Try to change to 'api' directory
        try:
            ftp.cwd(REMOTE_DIR)
            print(f"Changed directory to {REMOTE_DIR}")
        except ftplib.error_perm:
            print(f"Directory '{REMOTE_DIR}' not found. Trying absolute path or verification needed.")
            # Verify if we are already deep inside the path
            pass

        # Upload
        print(f"Uploading {LOCAL_FILE} to {ftp.pwd()}/{REMOTE_FILE}...")
        with open(LOCAL_FILE, 'rb') as file:
            ftp.storbinary(f'STOR {REMOTE_FILE}', file)
        
        print("Upload Successful!")
        ftp.quit()

    except Exception as e:
        print(f"Error: {e}")

if __name__ == "__main__":
    upload_file()
