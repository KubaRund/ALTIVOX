"""Fail on credentials, private runtime files, oversized files and missing app sources."""
from pathlib import Path
import re,sys,subprocess

ROOT=Path(__file__).resolve().parents[1]
REQUIRED=['www/index.html','www/.htaccess','www/admin/appearance.php','www/admin/theme-lib.php','www/api/theme.php','www/css/site.css','www/nastroje/vendor/qrcode.js','www/halloween/core.php','www/halloween/vendor/qrcode.js','www/hospody/podniky-cz.json','subdoms/zs-sokolov/index.html']
def check():
    errors=[];count=0
    for required in REQUIRED:
        if not (ROOT/required).is_file():errors.append('Missing source: '+required)
    listing=subprocess.run(['git','ls-files','--cached','--others','--exclude-standard','-z'],cwd=ROOT,stdout=subprocess.PIPE,stderr=subprocess.DEVNULL)
    paths=[ROOT/name for name in listing.stdout.decode('utf-8').split(chr(0)) if name] if listing.returncode==0 else ROOT.rglob('*')
    for path in paths:
        if not path.is_file() or '.git' in path.relative_to(ROOT).parts:continue
        relative=path.relative_to(ROOT).as_posix();count+=1
        if '__pycache__' in path.parts:continue
        if path.stat().st_size>95*1024*1024:errors.append('Oversized file: '+relative)
        if path.name in {'config.php','setup.key','CONTROL_CENTER_SETUP.txt','users.json','login_attempts.json'}:errors.append('Private file: '+relative)
        if relative.startswith(('www/admin/data/','www/uploads/')) and path.name not in {'.htaccess','.gitkeep'}:errors.append('Runtime data: '+relative)
        if path.suffix.lower() not in {'.php','.js','.css','.html','.py','.md','.json','.yml','.yaml','.txt'}:continue
        text=path.read_text(encoding='utf-8-sig',errors='replace')
        patterns=[r'gh[pousr]_[A-Za-z0-9]{30,}',r'github_pat_[A-Za-z0-9_]{50,}',r'-----BEGIN (?:RSA |OPENSSH |EC )?PRIVATE KEY-----',r'[$]2[aby][$][0-9]{2}[$][A-Za-z0-9./]{53}',r'(?:ftp|https?)://[^\s/:]+:[^\s/@]+@']
        if any(re.search(p,text) for p in patterns):errors.append('Possible credential: '+relative)
    if errors:
        print('\n'.join(errors));return 1
    print(f'Repository check passed: {count} files, required apps present, no detected credentials or runtime data.')
    return 0
if __name__=='__main__':sys.exit(check())
