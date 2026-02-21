import ftplib
import os

FTP_HOST = "ftp.bramex.co.tz"
FTP_USER = "quizzify@quizzify.bramex.co.tz"
FTP_PASS = "Bib@2012aa++"

FILES = {
    r"c:\xampp\htdocs\quizbooth\index.php": "index.php",
    r"c:\xampp\htdocs\quizbooth\assets\app.js": "assets/app.js"
}

def upload_files():
    try:
        print(f"Connecting to {FTP_HOST}...")
        ftp = ftplib.FTP(FTP_HOST)
        ftp.login(FTP_USER, FTP_PASS)
        print("Logged in.")

        for local_path, remote_path in FILES.items():
            if not os.path.exists(local_path):
                print(f"Skipping missing file: {local_path}")
                continue

            remote_dir = os.path.dirname(remote_path).replace("\\", "/")
            filename = os.path.basename(remote_path)
            
            ftp.cwd('/') 
            
            if remote_dir:
                try:
                    ftp.cwd(remote_dir)
                except ftplib.error_perm:
                    print(f"Directory '{remote_dir}' not found. Creating...")
                    try:
                        ftp.mkd(remote_dir)
                        ftp.cwd(remote_dir)
                    except Exception as e:
                        print(f"Failed to create/enter {remote_dir}: {e}")
                        continue
            
            print(f"Uploading {filename}...")
            with open(local_path, 'rb') as file:
                ftp.storbinary(f'STOR {filename}', file)
            print(f"Success: {remote_path}")

        ftp.quit()
        print("Deployment complete.")

    except Exception as e:
        print(f"Error: {e}")

if __name__ == "__main__":
    upload_files()
