"""Initialize a new local Control Center without including credentials in Git."""
from pathlib import Path
import argparse,getpass,json,os,secrets,shutil,subprocess

def main():
    parser=argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--root',type=Path,default=Path(__file__).resolve().parents[1]/'www')
    parser.add_argument('--php',default=shutil.which('php') or 'php')
    args=parser.parse_args();data=args.root.resolve()/'admin/data';data.mkdir(parents=True,exist_ok=True)
    config=data/'config.php'
    if config.exists():raise SystemExit('Configuration exists. Existing accounts were not changed.')
    username=input('Local administrator username: ').strip()
    if len(username)<3:raise SystemExit('Use at least 3 characters.')
    password=getpass.getpass('New local password (at least 12 characters): ')
    if len(password)<12 or password!=getpass.getpass('Repeat password: '):raise SystemExit('Passwords do not match or are too short.')
    code='$v=json_decode(stream_get_contents(STDIN),true,512,JSON_THROW_ON_ERROR);$c=["username"=>$v["username"],"password_hash"=>password_hash($v["password"],PASSWORD_DEFAULT),"secret"=>bin2hex(random_bytes(32)),"installed_at"=>date(DATE_ATOM),"analytics_consent"=>true,"site_name"=>"ALTIVOX"];echo "<?php\nreturn ".var_export($c,true).";\n";'
    result=subprocess.run([args.php,'-r',code],input=json.dumps({'username':username,'password':password}).encode(),stdout=subprocess.PIPE,stderr=subprocess.PIPE)
    password=None
    if result.returncode:raise SystemExit('PHP initialization failed. Check that a supported PHP CLI is available.')
    fd=os.open(config,os.O_WRONLY|os.O_CREAT|os.O_EXCL,0o600)
    with os.fdopen(fd,'wb') as target:target.write(result.stdout)
    public=Path(__file__).resolve().parents[1]/'config-examples/records.public.json'
    for name,value in {'records':json.loads(public.read_text(encoding='utf-8')) if public.exists() else [],'analytics':[],'live':[],'media':[]}.items():
        path=data/(name+'.json')
        if not path.exists():path.write_text(json.dumps(value,ensure_ascii=False,indent=2),encoding='utf-8')
    print('Local administrator initialized. Open /admin/login.php. Private state is excluded by .gitignore.')

if __name__=='__main__':main()
