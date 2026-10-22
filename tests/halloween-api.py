from urllib.request import Request,urlopen
from urllib.error import HTTPError,URLError
import json,uuid,concurrent.futures,sys
base=sys.argv[1] if len(sys.argv)>1 else 'http://127.0.0.1:8000/halloween/'
def call(action,**kwargs):
 req=Request(base+'api.php',data=json.dumps(dict(action=action,**kwargs)).encode(),headers={'Content-Type':'application/json'})
 for attempt in range(3):
  try:
   with urlopen(req,timeout=45) as r:return r.status,json.load(r)
  except HTTPError as e:return e.code,json.load(e)
  except (URLError,TimeoutError):
   if attempt==2 or action=='create':raise
code,res=call('create',name='Automatický test fronty',capacity=4,minutes=5,start=100)
assert code==200,(code,res);state=res['state'];event=state['id'];keys=state['keys']
def op(action,role='admin',**data):return call(action,id=event,key=keys[role],request=data.pop('request',uuid.uuid4().hex),**data)
request=uuid.uuid4().hex
c,a=op('sell','cashier',count=2,together=False,request=request);assert c==200
c,b=op('sell','cashier',count=2,together=False,request=request);assert c==200 and a['result']==b['result'] and b['state']['sold']==2
def sale(_):
 c,r=op('sell','cashier',count=1,together=True);assert c==200;return r['result']['numbers'][0]
with concurrent.futures.ThreadPoolExecutor(max_workers=6) as pool:numbers=list(pool.map(sale,range(12)))
assert sorted(numbers)==list(range(102,114)),'Concurrent sales duplicated or skipped a number'
c,r=call('next',id=event,key='',request=uuid.uuid4().hex);assert c==403
c,r=call('read',id=event);assert c==200 and r['state']['sold']==14 and 'keys' not in r['state'] and 'groups' not in r['state']
assert r['state']['nextNumber']==114
c,r=op('merge','cashier',numbers=[100,113]);assert c==200
assert r['state']['queue'][-1]['numbers']==[100,113]
for _ in range(4):assert op('next','caller')[0]==200
c,r=op('next','caller');assert c==409
c,r=op('read');called=r['state']['called'];gid=called[0]['id']
assert op('enter','cellar',group=gid)[0]==200
assert op('enter','cellar',group=gid)[0]==409
assert op('exit','cellar',group=gid)[0]==200
assert op('settings','cashier',name='No permission',capacity=10,minutes=5)[0]==403
c,r=call('read',id=event,key=keys['admin'])
for g in r['state']['called']:assert op('missed','caller',group=g['id'])[0]==200
assert op('close')[0]==200
assert op('sell','cashier',count=1)[0]==409
print('API checks passed: idempotent retry, 12 concurrent sales, FIFO merge, capacity guard, staff roles, public privacy, entry/exit and archived read-only event.')
