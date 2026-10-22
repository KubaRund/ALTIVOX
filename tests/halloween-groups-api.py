"""Integration test; optional second URL exercises independent PHP processes sharing storage."""
from urllib.request import Request, urlopen
from urllib.error import HTTPError
from concurrent.futures import ThreadPoolExecutor
import json, sys, time, uuid

bases = sys.argv[1:] or ['http://127.0.0.1:8000/halloween/']
def call(action, base=None, **data):
    request = Request((base or bases[0]) + 'api.php', data=json.dumps(dict(action=action, **data)).encode(), headers={'Content-Type':'application/json'})
    for attempt in range(4):
        try:
            with urlopen(request, timeout=15) as response: return response.status, json.load(response)
        except HTTPError as error:
            body = json.load(error)
            if error.code == 503 and action != 'create' and attempt < 3:
                time.sleep(.05 * 2 ** attempt)
                continue
            return error.code, body

code, response = call('create', version=2, name='Test skupin', gapSeconds=0)
assert code == 200, response
state = response['state']; event = state['id']; keys = state['keys']
def op(action, role='admin', **data):
    return call(action, id=event, key=keys[role], request=data.pop('request', uuid.uuid4().hex), **data)

request = uuid.uuid4().hex
c, a = op('sell', 'cashier', count=4, request=request)
c, b = op('sell', 'cashier', count=4, request=request)
assert c == 200 and a['result'] == b['result'] and b['state']['sold'] == 4
assert op('sell', 'cashier', count=5, request=request)[0] == 409
def sell(i):
    c, r = call('sell', base=bases[i % len(bases)], id=event, key=keys['cashier'], count=1, request=uuid.uuid4().hex)
    assert c == 200, r
    return r['result']['number']
with ThreadPoolExecutor(max_workers=8) as pool:
    numbers = list(pool.map(sell, range(24)))
assert sorted(numbers) == list(range(2,26)), 'Concurrent group numbering'
assert call('sell', id=event, count=1, request=uuid.uuid4().hex)[0] == 403
assert op('sell', 'cashier', count=7)[0] == 400
def next_group(i):
    return call('next', base=bases[i % len(bases)], id=event, key=keys['caller'], request=uuid.uuid4().hex)[0]
with ThreadPoolExecutor(max_workers=2) as pool:
    assert sorted(pool.map(next_group, range(2))) == [200,409]
c, r = op('read'); gid = r['state']['called'][0]['id']
assert op('enter', 'cellar', group=gid)[0] == 200
assert op('enter', 'cellar', group=gid)[0] == 409
assert op('pacing', 'cellar', gapSeconds=2)[0] == 200
assert op('next', 'caller')[0] == 409
time.sleep(2.1)
assert op('next', 'caller')[0] == 200
c, r = op('read'); revision = r['state']['revision']
c, short = call('read', id=event, key=keys['admin'], knownRevision=revision, knownRole='admin')
assert c == 200 and short['unchanged'] is True and 'state' not in short
c, public = call('read', id=event)
assert c == 200 and not any(k in public['state'] for k in ['keys','groups','lastEntry','nextAllowed'])
assert public['state']['sold'] == 28 and public['state']['soldGroups'] == 25
assert op('settings', 'cashier', capacity=1)[0] == 403
assert op('exit', 'cellar', group=gid)[0] == 200
c, r = op('read')
for group in r['state']['called']: assert op('missed', 'caller', group=group['id'])[0] == 200
assert op('close')[0] == 200
assert op('sell', 'cashier', count=1)[0] == 409
print('Group API checks passed: retry, 24 concurrent sales, competing callers, role permissions, pacing, shared state, lightweight reads and close.')
