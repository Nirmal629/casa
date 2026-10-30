from pathlib import Path
import subprocess,urllib.request,urllib.error,http.cookiejar,json,socket,time
import argparse,tempfile
parser=argparse.ArgumentParser(description='Localhost-only security regression checks; no database mutation.')
parser.add_argument('--root',type=Path,default=Path(__file__).resolve().parents[2])
parser.add_argument('--output',type=Path,default=Path(__file__).resolve().parent/'results')
args=parser.parse_args(); R=args.root.resolve(); O=args.output.resolve(); O.mkdir(parents=True,exist_ok=True)
assert R == Path(__file__).resolve().parents[2], 'Only this app directory may be tested'
assert O.is_relative_to(R), 'Results must stay inside app'
scratch=Path(tempfile.gettempdir()).joinpath('casa-http-security-runtime').resolve(); scratch.mkdir(parents=True,exist_ok=True)
_temp=tempfile.TemporaryDirectory(prefix='casa-http-security-',dir=str(scratch)); W=Path(_temp.name).resolve(); assert W.is_relative_to(scratch); results=[]
def port():
 with socket.socket() as s: s.bind(('127.0.0.1',0)); return s.getsockname()[1]
def request(base,path,method='GET',data=None,opener=None,headers=None):
 req=urllib.request.Request(base+path,data=data,method=method,headers=headers or {})
 try:
  with (opener or urllib.request.build_opener()).open(req,timeout=8) as r: return r.status,dict(r.headers),r.read()
 except urllib.error.HTTPError as e: return e.code,dict(e.headers),e.read()
def record(name,passed,status): results.append({'test':name,'passed':bool(passed),'status':status})
def launch(root,router):
 p=port(); log=(W/('server-'+str(p)+'.log')).open('w'); proc=subprocess.Popen(['php','-d','display_errors=0','-S',f'127.0.0.1:{p}','-t',str(root),str(router)],stdout=log,stderr=log,creationflags=0x08000000)
 base=f'http://127.0.0.1:{p}'
 for _ in range(40):
  try:
   with socket.create_connection(('127.0.0.1',p),timeout=.2): break
  except OSError: time.sleep(.1)
 return proc,log,base
fixture=W/'http-fixture.php'
fixture.write_text('''<?php
function env(string $key, mixed $default = null): mixed { return $key === 'APP_ENV' ? 'testing' : $default; }
define('DISABLE_CSRF', true);
require_once '''+repr(str(R/'Helpers/Session.php')).replace('\\\\','/')+''';
require_once '''+repr(str(R/'Middleware/CsrfGuard.php')).replace('\\\\','/')+''';
\\App\\Helpers\\Session::start();
if (parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) === '/csrf') { \\App\\Middleware\\CsrfGuard::handle(); echo 'accepted'; exit; }
header('Content-Type: application/json');
echo json_encode(['csrf_token' => \\App\\Helpers\\Session::getCsrfToken(), 'id' => session_id()]);
''',encoding='utf-8')
proc,log,base=launch(W,fixture)
try:
 jar=http.cookiejar.CookieJar(); opener=urllib.request.build_opener(urllib.request.HTTPCookieProcessor(jar))
 status,h,b=request(base,'/seed',opener=opener); token=json.loads(b)['csrf_token']
 for label,payload in [('missing',b''),('invalid',b'csrf_token=wrong'),('array',b'csrf_token[]=wrong')]:
  status,_,_=request(base,'/csrf','POST',payload,opener,{'Content-Type':'application/x-www-form-urlencoded','Accept':'application/json'})
  record('HTTP CSRF '+label+' denied even with testing env and bypass constant',status==403,status)
 status,_,_=request(base,'/csrf','POST',('csrf_token='+token).encode(),opener,{'Content-Type':'application/x-www-form-urlencoded'}); record('HTTP valid form CSRF accepted',status==200,status)
 status,_,_=request(base,'/csrf','POST',json.dumps({'csrf_token':token}).encode(),opener,{'Content-Type':'application/json'}); record('HTTP valid JSON CSRF accepted',status==200,status)
 status,_,_=request(base,'/csrf','POST',b'{}',opener,{'Content-Type':'application/json','X-CSRF-Token':token}); record('HTTP valid header CSRF accepted',status==200,status)
finally: proc.terminate(); proc.wait(timeout=8); log.close()
output={'total':len(results),'failed':sum(not r['passed'] for r in results),'tests':results}
(O/'security-http-results.json').write_text(json.dumps(output,indent=2),encoding='utf-8'); print(json.dumps(output,indent=2))

_temp.cleanup()
raise SystemExit(1 if output['failed'] else 0)
