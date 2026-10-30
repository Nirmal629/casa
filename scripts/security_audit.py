from pathlib import Path
import json,subprocess,csv,re,collections,concurrent.futures
import argparse
parser=argparse.ArgumentParser(description='Inventory PHP/JS auth-related signals and lint PHP without running application code.')
parser.add_argument('--root',type=Path,default=Path(__file__).resolve().parents[1])
parser.add_argument('--output',type=Path,required=True)
args=parser.parse_args()
R=args.root.resolve(); O=args.output.resolve(); O.mkdir(parents=True,exist_ok=True)
skips={'vendor','system','node_modules','.git','PHPMailer','writable'}
files=[f for f in R.rglob('*') if f.is_file() and f.suffix.lower() in {'.php','.js'} and not any(p in skips for p in f.relative_to(R).parts)]
checks={'session_start':r'\bsession_start\s*\(', 'auth_signal':r'AuthGuard|requireApiUser|requireLogin|requireUserType|requireAdminSession|\$_SESSION\[.(?:user_id|logged_in)', 'csrf_signal':r'csrf|CsrfGuard', 'sql_mutation':r'\b(?:UPDATE|INSERT\s+INTO|DELETE\s+FROM)\b', 'request_input':r'\$_(?:POST|GET|REQUEST)\[', 'password_reference':r'PASSWORD|password', 'browser_storage':r'(?:localStorage|sessionStorage)', 'debug_display':r"display_errors.{0,15}[1']", 'secret_field_select':r'SELECT\s+\*\s+FROM\s+`?ca_users', 'session_type_call':r'Session::userType\('}
rows=[]
for f in files:
 s=f.read_text(encoding='utf-8-sig',errors='replace'); row={'file':f.relative_to(R).as_posix(),'folder':f.relative_to(R).parts[0] if len(f.relative_to(R).parts)>1 else '(root)','lines':len(s.splitlines())}
 for key,pat in checks.items(): row[key]=';'.join(str(s.count('\n',0,m.start())+1) for m in re.finditer(pat,s,re.I))
 row['review']='heuristic-only; manual review required' if any(row[k] for k in ['sql_mutation','request_input','password_reference']) else 'inventory; not a security clearance'
 rows.append(row)
with (O/'security-file-inventory.csv').open('w',newline='',encoding='utf-8-sig') as f:
 w=csv.DictWriter(f,fieldnames=rows[0].keys()); w.writeheader(); w.writerows(rows)
def lint(f):
 p=subprocess.run(['php','-l',str(f)],capture_output=True,text=True)
 return {'file':f.relative_to(R).as_posix(),'passed':p.returncode==0,'diagnostic':(p.stdout+p.stderr).strip() if p.returncode else ''}
with concurrent.futures.ThreadPoolExecutor(max_workers=6) as pool: results=list(pool.map(lint,[f for f in files if f.suffix.lower()=='.php']))
(O/'php-lint-results.json').write_text(json.dumps(results,indent=2),encoding='utf-8')
stats={'inventoried':len(rows),'php_linted':len(results),'lint_failed':sum(not r['passed'] for r in results),'folders':dict(collections.Counter(r['folder'] for r in rows)), 'excluded':['vendor','system','node_modules','.git','PHPMailer','writable']}
(O/'security-scan-summary.json').write_text(json.dumps(stats,indent=2)); print(json.dumps(stats)); print(json.dumps([r for r in results if not r['passed']],indent=2))

raise SystemExit(1 if stats['lint_failed'] else 0)
