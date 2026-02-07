import ftplib
import os

# FTP Details
FTP_HOST = "ftp.bramex.co.tz"
FTP_USER = "quizzify@quizzify.bramex.co.tz"
FTP_PASS = "Bib@2012aa++"

# Files to upload: Local Path -> Remote Path (relative to FTP root)
FILES = {
    r"c:\xampp\htdocs\quizbooth\admin\dashboard.php": "admin/dashboard.php",
    r"c:\xampp\htdocs\quizbooth\api\admin_actions.php": "api/admin_actions.php",
    r"c:\xampp\htdocs\quizbooth\ba\game.php": "ba/game.php",
    r"c:\xampp\htdocs\quizbooth\index.php": "index.php",
    r"c:\xampp\htdocs\quizbooth\assets\app.js": "assets/app.js",
    r"c:\xampp\htdocs\quizbooth\assets\smice2.mp4": "assets/smice2.mp4"
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
            
            # Go to root
            ftp.cwd('/') 
            
            # Change to target dir
            if remote_dir:
                try:
                    ftp.cwd(remote_dir)
                except ftplib.error_perm:
                    print(f"Directory '{remote_dir}' not found or inaccessible. Attempting to upload to root or create?")
                    # Try to create? simpler to just skip or assume root if fails.
                    # But for 'admin', 'api' etc, they should exist.
                    print(f"Failed to enter {remote_dir}, skipping {filename}")
                    continue
            
            print(f"Uploading {filename} to {ftp.pwd()}...")
            with open(local_path, 'rb') as file:
                ftp.storbinary(f'STOR {filename}', file)
            print(f"Success: {remote_path}")

        ftp.quit()
        print("All uploads complete.")

    except Exception as e:
        print(f"Error: {e}")

if __name__ == "__main__":
    upload_files()
