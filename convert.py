import os
import subprocess
import sys

def install_and_convert():
    try:
        import PIL
        from PIL import Image
        from pillow_heif import register_heif_opener
    except ImportError:
        subprocess.check_call([sys.executable, "-m", "pip", "install", "pillow", "pillow-heif"])
        import PIL
        from PIL import Image
        from pillow_heif import register_heif_opener

    register_heif_opener()

    src_dir = r"d:\NFC OR QR MENU\ICE COFFEE-20260925T084035Z-1-001\ICE COFFEE"
    dest_dir = r"d:\NFC OR QR MENU\assets\images"
    
    if not os.path.exists(dest_dir):
        os.makedirs(dest_dir)

    for filename in os.listdir(src_dir):
        filepath = os.path.join(src_dir, filename)
        if os.path.isfile(filepath):
            try:
                image = Image.open(filepath)
                # Convert to RGB to save as JPG
                rgb_im = image.convert('RGB')
                dest_filename = filename.strip("_") + ".jpg"
                dest_path = os.path.join(dest_dir, dest_filename)
                rgb_im.save(dest_path, "JPEG")
                print(f"Converted {filename} to {dest_filename}")
            except Exception as e:
                print(f"Failed to convert {filename}: {e}")

if __name__ == "__main__":
    install_and_convert()
