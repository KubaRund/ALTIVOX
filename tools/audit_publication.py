"""Check publishable files or all reachable Git blobs; print paths, never secret values.

This is a focused guard, not a complete security or copyright audit.
"""
from pathlib import Path, PurePosixPath
import argparse
import io
import re
import struct
import subprocess
import sys
import zipfile

ROOT = Path(__file__).resolve().parents[1]
PATTERNS = {
    'GitHub credential': rb'(?:gh[pousr]_[A-Za-z0-9]{30,}|github_pat_[A-Za-z0-9_]{50,})',
    'private key': rb'-----BEGIN (?:RSA |OPENSSH |EC )?PRIVATE KEY-----',
    'password hash': rb'[$]2[aby][$][0-9]{2}[$][A-Za-z0-9./]{53}',
    'authenticated URL': rb'(?:ftp|https?)://[^\s/:]+:[^\s/@]+@',
    'cloud access identifier': rb'(?:AKIA|ASIA)[A-Z0-9]{16}',
    'local user path': rb'(?i)C:\\Users\\[^\\\s]+',
}
PRIVATE_NAMES = {'config.php', 'setup.key', 'users.json', 'login_attempts.json',
                 'CONTROL_CENTER_SETUP.txt', '.env', '.netrc', '.npmrc', 'id_rsa', 'id_ed25519'}


def jpeg_metadata(data):
    """Allow only a minimal EXIF orientation IFD; reject other metadata segments."""
    position = 2
    while position < len(data):
        if data[position] != 255:
            return True
        while position < len(data) and data[position] == 255:
            position += 1
        marker = data[position]
        position += 1
        if marker in (0xDA, 0xD9):
            return False
        length = int.from_bytes(data[position:position + 2], 'big')
        if length < 2:
            return True
        payload = data[position + 2:position + length]
        if marker in (0xE1, 0xED, 0xFE):
            if marker != 0xE1 or not payload.startswith(b'Exif\x00\x00'):
                return True
            tiff = payload[6:]
            endian = '<' if tiff[:2] == b'II' else '>'
            try:
                offset = struct.unpack_from(endian + 'I', tiff, 4)[0]
                count = struct.unpack_from(endian + 'H', tiff, offset)[0]
                tag, kind, size = struct.unpack_from(endian + 'HHI', tiff, offset + 2)
                next_ifd = struct.unpack_from(endian + 'I', tiff, offset + 2 + 12)[0]
                if count != 1 or (tag, kind, size) != (274, 3, 1) or next_ifd != 0:
                    return True
            except struct.error:
                return True
        position += length
    return True


def inspect(name, data, findings, depth=0):
    parts = PurePosixPath(name.split('!')[-1]).parts
    if parts and (parts[-1] in PRIVATE_NAMES or parts[-1].startswith('.env.')):
        findings.add((name, 'private filename'))
    normalized = name.replace('\\', '/')
    if normalized.startswith(('www/admin/data/', 'www/uploads/')) and parts[-1] not in {'.gitkeep', '.htaccess'}:
        findings.add((name, 'runtime data'))
    for label, pattern in PATTERNS.items():
        if re.search(pattern, data):
            findings.add((name, label))
    if data[:2] == b'\xff\xd8' and jpeg_metadata(data):
        findings.add((name, 'JPEG metadata beyond orientation'))
    if data[:4] == b'RIFF' and data[8:12] == b'WEBP':
        position = 12
        while position + 8 <= len(data):
            tag = data[position:position + 4]
            size = int.from_bytes(data[position + 4:position + 8], 'little')
            if tag in (b'EXIF', b'XMP '):
                findings.add((name, 'WebP metadata'))
            position += 8 + size + size % 2
    if data.startswith(b'\x89PNG\r\n\x1a\n'):
        position = 8
        while position + 12 <= len(data):
            size = int.from_bytes(data[position:position + 4], 'big')
            if data[position + 4:position + 8] in (b'eXIf', b'tEXt', b'iTXt', b'zTXt'):
                findings.add((name, 'PNG metadata requires review'))
            position += size + 12
    if name.lower().endswith('.zip'):
        if depth >= 3:
            findings.add((name, 'archive nesting limit'))
            return
        try:
            with zipfile.ZipFile(io.BytesIO(data)) as archive:
                if sum(item.file_size for item in archive.infolist()) > 100 * 1024 * 1024:
                    findings.add((name, 'expanded archive too large'))
                    return
                for item in archive.infolist():
                    if not item.is_dir():
                        inspect(name + '!' + item.filename, archive.read(item), findings, depth + 1)
        except (ValueError, RuntimeError, zipfile.BadZipFile):
            findings.add((name, 'unreadable archive'))


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--history', action='store_true', help='Scan all blobs reachable from local refs')
    args = parser.parse_args()
    findings = set()
    count = 0
    if args.history:
        entries = subprocess.check_output(['git', 'rev-list', '--objects', '--all'], cwd=ROOT).decode('utf-8').splitlines()
        for entry in entries:
            sha, _, name = entry.partition(' ')
            if not name:
                continue
            if subprocess.check_output(['git', 'cat-file', '-t', sha], cwd=ROOT).strip() != b'blob':
                continue
            inspect(name, subprocess.check_output(['git', 'cat-file', 'blob', sha], cwd=ROOT), findings)
            count += 1
    else:
        names = subprocess.check_output(['git', 'ls-files', '--cached', '--others', '--exclude-standard', '-z'], cwd=ROOT).decode('utf-8').split('\x00')
        for name in names:
            path = ROOT / name
            if name and path.is_file():
                inspect(name, path.read_bytes(), findings)
                count += 1
    for name, label in sorted(findings):
        print(f'{label}: {name}')
    print(f'Publication scan: {count} files/blobs; {len(findings)} findings. Manual review remains necessary.')
    return 1 if findings else 0


if __name__ == '__main__':
    sys.exit(main())
