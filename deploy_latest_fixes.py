import ftplib
import os

# FTP Details
FTP_HOST = "ftp.bramex.co.tz"
FTP_USER = "quizzify@quizzify.bramex.co.tz"
FTP_PASS = "Bib@2012aa++"

# Files/Folders to upload: Local Path -> Remote Path (relative to FTP root)
FILES = {
    r"c:\xampp\htdocs\quizbooth\api\finish.php": "api/finish.php",
    r"c:\xampp\htdocs\quizbooth\assets\app.js": "assets/app.js",
    r"c:\xampp\htdocs\quizbooth\index.php": "index.php",
    r"c:\xampp\htdocs\quizbooth\admin\dashboard.php": "admin/dashboard.php",
    # Add HLS (directory)
    r"c:\xampp\htdocs\quizbooth\assets\hls": "assets/hls"
}

def upload_directory(ftp, local_dir, remote_dir):
    ftp.cwd('/') # Reset to root
    try:
        if not os.path.exists(local_dir):
            print(f"Skipping directory upload (does not exist): {local_dir}")
            return

        # Ensure directory exists on FTP
        try:
            ftp.cwd(remote_dir)
        except ftplib.error_perm:
            print(f"Directory '{remote_dir}' not found. Creating...")
            # We assume the parent directory exists or we might fail.
            # However, for 'assets/hls', 'assets' exists.
            try:
                ftp.mkd(remote_dir)
                ftp.cwd(remote_dir)
            except Exception as e:
                print(f"Failed to create {remote_dir}: {e}")
                return

        # Upload files in directory
        for filename in os.listdir(local_dir):
            local_path = os.path.join(local_dir, filename)
            if os.path.isfile(local_path):
                print(f"Uploading {filename} to {remote_dir}...")
                with open(local_path, 'rb') as file:
                    ftp.storbinary(f'STOR {filename}', file)
            # Recursively upload subdirectories if needed (HLS usually flat for playlist + ts)
            elif os.path.isdir(local_path):
                # Optionally support recursive upload
                pass 
                
    except Exception as e:
        print(f"Error uploading directory {local_dir}: {e}")

    # Return to root? Or handle path state better.
    # For safety, let's reset to root after upload.
    ftp.cwd('/')


def upload_files():
    try:
        print(f"Connecting to {FTP_HOST}...")
        ftp = ftplib.FTP(FTP_HOST)
        ftp.login(FTP_USER, FTP_PASS)
        print("Logged in.")

        for local_path, remote_path in FILES.items():
            # Normalized slashes for FTP
            remote_path = remote_path.replace("\\", "/")
            
            if os.path.isdir(local_path):
                upload_directory(ftp, local_path, remote_path)
                continue

            # Standard file upload
            if not os.path.exists(local_path):
                print(f"Skipping missing file: {local_path}")
                continue

            remote_dir = os.path.dirname(remote_path)
            filename = os.path.basename(remote_path)
            
            # Reset to root
            ftp.cwd('/') 
            
            # Navigate to directory
            if remote_dir:
                try:
                    ftp.cwd(remote_dir)
                except ftplib.error_perm:
                    print(f"Directory '{remote_dir}' not found. Attempting to create...")
                    try:
                        ftp.mkd(remote_dir)
                        ftp.cwd(remote_dir)
                    except Exception as e:
                        print(f"Failed to create/enter {remote_dir}: {e}")
                        continue
            
            print(f"Uploading {filename} to {ftp.pwd()}...")
            with open(local_path, 'rb') as file:
                ftp.storbinary(f'STOR {filename}', file)
            print(f"Success: {remote_path}")

        ftp.quit()
        print("Deployment complete.")

    except Exception as e:
        print(f"Error: {e}")

if __name__ == "__main__":
    upload_files()
