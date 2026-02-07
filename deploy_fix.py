import ftplib
import os

FTP_HOST = "ftp.bramex.co.tz"
FTP_USER = "quizzify@quizzify.bramex.co.tz"
FTP_PASS = "Bib@2012aa++"

# Upload fix_schema_active.php to /api/
LOCAL_FILE = r"c:\xampp\htdocs\quizbooth\api\fix_schema_active.php"
REMOTE_DIR = "api"
REMOTE_FILE = "fix_schema_active.php"

def upload():
    try:
        print(f"Connecting to {FTP_HOST}...")
        ftp = ftplib.FTP(FTP_HOST)
        ftp.login(FTP_USER, FTP_PASS)
        
        ftp.cwd(REMOTE_DIR)
        print(f"Uploading {REMOTE_FILE}...")
        
        with open(LOCAL_FILE, 'rb') as f:
            ftp.storbinary(f'STOR {REMOTE_FILE}', f)
            
        print("Done.")
        ftp.quit()
    except Exception as e:
        print(f"Error: {e}")

if __name__ == "__main__":
    upload()
